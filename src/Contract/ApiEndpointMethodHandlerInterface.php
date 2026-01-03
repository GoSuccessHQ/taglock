<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Contract;

use GoSuccess\TagLock\Enum\HttpMethod;
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
	 * @param WP_REST_Request<array<string, mixed>> $request The REST API request object.
	 * @return bool True if the user has permission, false otherwise.
	 */
	public function permissionCallback( WP_REST_Request $request ): bool;

	/**
	 * Get the argument schema for this handler.
	 *
	 * @return array<string, mixed> The WordPress REST API args schema.
	 */
	public function getArgs(): array;
}
