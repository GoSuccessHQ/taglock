<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use GoSuccess\TagLock\DTO\ApiResponse;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\EncryptionUtil;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function current_user_can;
use function get_option;
use function register_rest_route;
use function sanitize_text_field;
use function update_option;
use function wp_unslash;

/**
 * Settings Route
 *
 * REST API endpoints for managing TagLock settings (KlickTipp credentials).
 * GET /wp-json/taglock/v1/settings - Retrieve current settings
 * POST /wp-json/taglock/v1/settings - Update settings
 */
#[AutoconfigureTag( 'taglock.api_route' )]
final class SettingsRoute implements ApiRouteInterface {

	private const string NAMESPACE = 'taglock/v1';
	private const string ROUTE     = '/settings';

	public function __construct(
		private readonly LoggerService $logger
	) {}

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		// GET endpoint - retrieve settings
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'getSettings' ],
				'permission_callback' => [ $this, 'checkPermissions' ],
			]
		);

		// POST endpoint - save settings
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'saveSettings' ],
				'permission_callback' => [ $this, 'checkPermissions' ],
				'args'                => [
					'klicktipp_username' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'klicktipp_password' => [
						'required'          => true,
						'type'              => 'string',
						// Do not sanitize like a normal text field; passwords must remain intact.
						'sanitize_callback' => static fn( $value ) => is_string( $value ) ? $value : '',
					],
				],
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getNamespace(): string {
		return self::NAMESPACE;
	}

	/**
	 * @inheritDoc
	 */
	public function getRoute(): string {
		return self::ROUTE;
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
		$settings = [
			'klicktipp_username' => get_option( 'taglock_klicktipp_username', '' ),
			'has_password'       => ! empty( get_option( 'taglock_klicktipp_password', '' ) ),
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

		// Validate
		if ( empty( $username ) ) {
			$this->logger->warning( __( 'Settings save failed: empty username', 'taglock' ) );
			return ApiResponse::error(
				__( 'Username cannot be empty', 'taglock' ),
				'invalid_username',
				400
			);
		}

		if ( empty( $password ) ) {
			$this->logger->warning( __( 'Settings save failed: empty password', 'taglock' ) );
			return ApiResponse::error(
				__( 'Password cannot be empty', 'taglock' ),
				'invalid_password',
				400
			);
		}

		// Encrypt password before saving
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

		// Save settings
		update_option( 'taglock_klicktipp_username', sanitize_text_field( $username ) );
		update_option( 'taglock_klicktipp_password', $encryptedPassword );

		$this->logger->info( __( 'Settings saved successfully', 'taglock' ), [ 'username' => $username ] );

		return ApiResponse::success( null, __( 'Settings saved successfully', 'taglock' ) );
	}
}
