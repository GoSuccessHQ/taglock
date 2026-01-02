<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Contract;

use GoSuccess\TagLock\DTO\ApiMethodHandler;

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
	 * @return array<int, ApiMethodHandler>
	 */
	public function getMethodHandlers(): array;
}
