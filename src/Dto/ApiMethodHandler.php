<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Dto;

use Closure;
use GoSuccess\TagLock\Contract\ApiEndpointMethodHandlerInterface;
use GoSuccess\TagLock\Enum\HttpMethod;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Defines one REST method handler for a route.
 *
 * This is a closure-based implementation of ApiEndpointMethodHandlerInterface
 * for backwards compatibility. New handlers should extend AbstractApiHandler.
 */
final class ApiMethodHandler implements ApiEndpointMethodHandlerInterface {

	private WP_Error|null $lastPermissionError = null;

	/**
	 * @param HttpMethod $method The HTTP method.
	 * @param Closure $callback The callback closure.
	 * @param Closure $permissionCallback The permission callback closure.
	 * @param array<string, mixed> $args WordPress route args schema.
	 */
	public function __construct(
		private readonly HttpMethod $method,
		private readonly Closure $callback,
		private readonly Closure $permissionCallback,
		private readonly array $args = []
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getMethod(): HttpMethod {
		return $this->method;
	}

	/**
	 * @inheritDoc
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		return ( $this->callback )( $request );
	}

	/**
	 * Check permissions and store any WP_Error for later retrieval.
	 *
	 * Note: The interface requires bool return, but WordPress REST API
	 * actually accepts WP_Error as well. We return the original result
	 * for full WordPress compatibility.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The REST API request object.
	 * @return bool|WP_Error True if permitted, false or WP_Error otherwise.
	 */
	public function permissionCallback( WP_REST_Request $request ): bool|WP_Error {
		$result = ( $this->permissionCallback )( $request );

		if ( $result instanceof WP_Error ) {
			$this->lastPermissionError = $result;
			return $result;
		}

		$this->lastPermissionError = null;
		return (bool) $result;
	}

	/**
	 * @inheritDoc
	 */
	public function getArgs(): array {
		return $this->args;
	}

	/**
	 * Get the last permission error, if any.
	 *
	 * @return WP_Error|null The last WP_Error from permissionCallback, or null.
	 */
	public function getLastPermissionError(): ?WP_Error {
		return $this->lastPermissionError;
	}
}
