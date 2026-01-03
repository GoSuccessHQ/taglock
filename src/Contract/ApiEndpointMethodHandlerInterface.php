<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Contract;

use GoSuccess\TagLock\Enum\HttpMethod;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * API Endpoint Method Handler Interface
 *
 * Defines the contract for API endpoint method handlers.
 */
interface ApiEndpointMethodHandlerInterface {

	/**
	 * Get the HTTP method for this handler.
	 *
	 * @return HttpMethod The HTTP method.
	 */
	public function getMethod(): HttpMethod;

	/**
	 * Handle the API request and return a response.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST API request object.
	 * @return WP_REST_Response The REST API response object.
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response;

	/**
	 * Check if the current user has permission to access this endpoint.
	 *
	 * WordPress REST API permission callbacks can return:
	 * - true: Permission granted
	 * - false: Permission denied (returns 401 or 403 based on authentication)
	 * - WP_Error: Permission denied with custom error message
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST API request object.
	 * @return bool|WP_Error True if permission granted, false or WP_Error otherwise.
	 */
	public function permissionCallback( WP_REST_Request $request ): bool|WP_Error;

	/**
	 * Get the argument schema for this handler.
	 *
	 * @return array<string, mixed> The WordPress REST API args schema.
	 */
	public function getArgs(): array;
}
