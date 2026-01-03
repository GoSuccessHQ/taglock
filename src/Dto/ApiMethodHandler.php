<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Dto;

use GoSuccess\TagLock\Enum\HttpMethod;
use WP_Error;
use WP_REST_Request;

/**
 * Defines one REST method handler for a route.
 */
final class ApiMethodHandler {

	/**
	 * @param array<string, mixed> $args WordPress route args schema.
	 */
	public function __construct(
		public readonly HttpMethod $method,
		private readonly \Closure $callback,
		private readonly \Closure $permissionCallback,
		public readonly array $args = []
	) {
	}

	public function callback( WP_REST_Request $request ): mixed {
		return ( $this->callback )( $request );
	}

	public function permissionCallback( WP_REST_Request $request ): bool|WP_Error {
		return ( $this->permissionCallback )( $request );
	}
}
