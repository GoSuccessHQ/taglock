<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Service\Api\ApiRouteRegistrationService;

/**
 * REST API Service (legacy facade)
 *
 * Kept as a thin wrapper to avoid duplicated registration logic.
 */
final class RestApiService {

	public function __construct(
		private readonly ApiRouteRegistrationService $routeRegistrationService
	) {
	}

	/**
	 * Register all API routes.
	 */
	public function registerRoutes(): void {
		$this->routeRegistrationService->registerRoutes();
	}
}
