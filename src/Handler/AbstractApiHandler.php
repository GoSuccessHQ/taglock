<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Handler;

use GoSuccess\TagLock\Contract\ApiEndpointMethodHandlerInterface;
use WP_Error;
use WP_REST_Request;

use function current_user_can;

/**
 * Abstract API Handler
 *
 * Provides default implementation for common handler functionality.
 * By default, requires 'manage_options' capability (admin access).
 *
 * Public endpoints should override permissionCallback() to return true.
 */
abstract class AbstractApiHandler implements ApiEndpointMethodHandlerInterface {

	/**
	 * Default permission check - requires admin capabilities.
	 *
	 * Override this method in subclasses for public endpoints.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST API request object.
	 * @return bool|WP_Error True if the user has permission, false or WP_Error otherwise.
	 */
	public function permissionCallback( WP_REST_Request $request ): bool|WP_Error {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the argument schema for this handler.
	 *
	 * Override this method to provide custom arguments.
	 *
	 * @return array<string, mixed> The WordPress REST API args schema.
	 */
	public function getArgs(): array {
		return [];
	}

	/**
	 * Get the nonce action for this handler.
	 *
	 * Override this method for nonce-protected endpoints.
	 *
	 * @return string|null The nonce action or null if not used.
	 */
	protected function getNonceAction(): ?string {
		return null;
	}
}
