<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Contract;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * API Route Interface
 *
 * Defines the contract for REST API routes.
 */
interface ApiRouteInterface {

	/**
	 * Get the route path (e.g., '/check-access').
	 */
	public function getRoute(): string;

	/**
	 * Get the method handlers for this route.
	 *
	 * @return array<int, ApiEndpointMethodHandlerInterface>
	 */
	public function getMethodHandlers(): array;
}
