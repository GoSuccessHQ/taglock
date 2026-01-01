<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Service\LoggerService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function current_user_can;
use function register_rest_route;
use function rest_ensure_response;
use function sanitize_text_field;
use function update_option;

/**
 * Settings Route
 *
 * REST API endpoint to save plugin settings.
 * Endpoint: POST /wp-json/taglock/v1/settings
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
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handleRequest' ],
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
			$this->logger->warning( 'Unauthorized settings access attempt' );
			return new WP_Error(
				'rest_forbidden',
				'You do not have permission to manage settings',
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Handle the settings save request.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error The REST response.
	 */
	public function handleRequest( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$username = $request->get_param( 'klicktipp_username' );
		$password = $request->get_param( 'klicktipp_password' );

		// Save settings
		update_option( 'taglock_klicktipp_username', $username );
		update_option( 'taglock_klicktipp_password', $password ); // TODO: Encrypt password

		$this->logger->info( 'Settings saved', [ 'username' => $username ] );

		return rest_ensure_response( [
			'success' => true,
			'message' => 'Settings saved successfully',
		] );
	}
}
