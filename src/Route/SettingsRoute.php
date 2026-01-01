<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\EncryptionUtil;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function current_user_can;
use function get_option;
use function register_rest_route;
use function rest_ensure_response;
use function sanitize_text_field;
use function update_option;

/**
 * Settings Route
 *
 * REST API endpoints for plugin settings.
 * GET /wp-json/taglock/v1/settings - Retrieve settings
 * POST /wp-json/taglock/v1/settings - Save settings
 */
final class SettingsRoute implements ApiRouteInterface {

	private const NAMESPACE = 'taglock/v1';
	private const ROUTE     = '/settings';

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
						'sanitize_callback' => 'sanitize_text_field',
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

		return rest_ensure_response( [
			'success' => true,
			'data'    => $settings,
		] );
	}

	/**
	 * Handle the settings save request.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error The REST response.
	 */
	public function saveSettings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$username = $request->get_param( 'klicktipp_username' );
		$password = $request->get_param( 'klicktipp_password' );

		// Validate
		if ( empty( $username ) ) {
			$this->logger->warning( __( 'Settings save failed: empty username', 'taglock' ) );
			return new WP_Error(
				'invalid_username',
				__( 'Username cannot be empty', 'taglock' ),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $password ) ) {
			$this->logger->warning( __( 'Settings save failed: empty password', 'taglock' ) );
			return new WP_Error(
				'invalid_password',
				__( 'Password cannot be empty', 'taglock' ),
				[ 'status' => 400 ]
			);
		}

		// Encrypt password before saving
		$encryptedPassword = EncryptionUtil::encrypt( $password );

		// Save settings
		update_option( 'taglock_klicktipp_username', sanitize_text_field( $username ) );
		update_option( 'taglock_klicktipp_password', $encryptedPassword );

		$this->logger->info( __( 'Settings saved successfully', 'taglock' ), [ 'username' => $username ] );

		return rest_ensure_response( [
			'success' => true,
			'message' => __( 'Settings saved successfully', 'taglock' ),
		] );
	}
}
