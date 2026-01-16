<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Dto\ApiMethodHandler;
use GoSuccess\TagLock\Dto\ApiResponse;
use GoSuccess\TagLock\Enum\HttpMethod;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\EncryptionUtil;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function __;
use function current_user_can;
use function defined;
use function get_class;
use function get_option;
use function is_string;
use function sanitize_text_field;
use function time;
use function update_option;
use function wp_unslash;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Route
 *
 * REST API endpoints for managing TagLock settings (KlickTipp credentials).
 * GET /wp-json/taglock/v1/settings - Retrieve current settings
 * POST /wp-json/taglock/v1/settings - Update settings
 */
final class SettingsRoute implements ApiRouteInterface {

	private const ROUTE = '/settings';

	public function __construct(
		private readonly PluginConfiguration $config,
		private readonly LoggerService $logger,
		private readonly CrmProviderInterface $provider
	) {}

	/**
	 * @inheritDoc
	 */
	public function getRoute(): string {
		return self::ROUTE;
	}

	/**
	 * @inheritDoc
	 */
	public function getMethodHandlers(): array {
		return [
			new ApiMethodHandler(
				HttpMethod::GET,
				fn( WP_REST_Request $request ) => $this->getSettings( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request )
			),
			new ApiMethodHandler(
				HttpMethod::POST,
				fn( WP_REST_Request $request ) => $this->saveSettings( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request ),
				[
					'klicktipp_username' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'klicktipp_password' => [
						'required'          => false,
						'type'              => 'string',
						// Do not sanitize like a normal text field; passwords must remain intact.
						'sanitize_callback' => static fn( $value ) => is_string( $value ) ? $value : '',
					],
				]
			),
		];
	}

	/**
	 * Check permissions for the endpoint.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function checkPermissions( WP_REST_Request $request ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->logger->warning( __( 'Unauthorized settings access attempt', 'taglock' ) );
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage settings', 'taglock' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get current settings.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response The REST response.
	 */
	public function getSettings( WP_REST_Request $request ): WP_REST_Response {
		$connectionStatus = get_option( $this->config->connectionStatusOption, [
			'is_connected' => false,
			'checked_at'   => 0,
			'error'        => '',
		] );

		$settings = [
			'klicktipp_username' => get_option( $this->config->klicktippUsernameOption, '' ),
			'has_password'       => ! empty( get_option( $this->config->klicktippPasswordOption, '' ) ),
			'connection_status'  => $connectionStatus,
		];

		return ApiResponse::success( $settings );
	}

	/**
	 * Handle the settings save request.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error The REST response.
	 */
	public function saveSettings( WP_REST_Request $request ): WP_REST_Response {
		$username = (string) $request->get_param( 'klicktipp_username' );
		$password = wp_unslash( (string) $request->get_param( 'klicktipp_password' ) );
		$existingEncryptedPassword = (string) get_option( $this->config->klicktippPasswordOption, '' );

		// Validate username
		if ( empty( $username ) ) {
			$this->logger->warning( __( 'Settings save failed: empty username', 'taglock' ) );
			return ApiResponse::error(
				__( 'Username cannot be empty', 'taglock' ),
				'invalid_username',
				400
			);
		}

		// Determine the password to use for testing
		$testPassword = $password;
		if ( $password === '' ) {
			if ( $existingEncryptedPassword === '' ) {
				$this->logger->warning( __( 'Settings save failed: empty password', 'taglock' ) );
				return ApiResponse::error(
					__( 'Password cannot be empty', 'taglock' ),
					'invalid_password',
					400
				);
			}

			// Use existing password for test
			try {
				$testPassword = EncryptionUtil::decrypt( $existingEncryptedPassword );
			} catch ( Throwable $exception ) {
				$this->logger->error( __( 'Failed to decrypt existing password for test', 'taglock' ), [
					'exception' => get_class( $exception ),
					'message'   => $exception->getMessage(),
				] );
				return ApiResponse::error(
					__( 'Failed to verify existing password. Please enter your password again.', 'taglock' ),
					'decryption_failed',
					500
				);
			}
		}

		// Test credentials before saving
		if ( ! $this->provider->testCredentials( $username, $testPassword ) ) {
			$error = $this->provider->getLastError() ?: __( 'Connection failed. Please check your credentials.', 'taglock' );
			$this->logger->warning( __( 'Settings save failed: invalid credentials', 'taglock' ), [ 'error' => $error ] );

			// Update connection status to reflect the failed test
			update_option( $this->config->connectionStatusOption, [
				'is_connected' => false,
				'checked_at'   => time(),
				'error'        => $error,
			] );

			return ApiResponse::error( $error, 'invalid_credentials', 401 );
		}

		// Credentials are valid, now save them
		update_option( $this->config->klicktippUsernameOption, sanitize_text_field( $username ) );

		// Only update password if a new one was provided
		if ( $password !== '' ) {
			try {
				$encryptedPassword = EncryptionUtil::encrypt( $password );
			} catch ( Throwable $exception ) {
				$this->logger->error( __( 'Failed to encrypt password', 'taglock' ), [
					'exception' => get_class( $exception ),
					'message'   => $exception->getMessage(),
				] );

				return ApiResponse::error(
					__( 'Failed to save credentials. Please try again.', 'taglock' ),
					'encryption_failed',
					500
				);
			}

			update_option( $this->config->klicktippPasswordOption, $encryptedPassword );
		}

		// Update connection status to reflect successful connection
		update_option( $this->config->connectionStatusOption, [
			'is_connected' => true,
			'checked_at'   => time(),
			'error'        => '',
		] );

		$this->logger->info( __( 'Settings saved successfully', 'taglock' ), [ 'username' => $username ] );

		return ApiResponse::success( null, __( 'Settings saved successfully', 'taglock' ) );
	}
}
