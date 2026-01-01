<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Contract;

/**
 * API Route Interface
 *
 * Defines the contract for REST API routes.
 */
interface ApiRouteInterface {

	/**
	 * Register the REST API route.
	 */
	public function register(): void;

	/**
	 * Get the route namespace.
	 *
	 * @return string The namespace (e.g., 'taglock/v1').
	 */
	public function getNamespace(): string;

	/**
	 * Get the route path.
	 *
	 * @return string The route path (e.g., '/check-access').
	 */
	public function getRoute(): string;
}
