<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\TagLock\Service\Api\ApiRouteRegistrationService;

use function add_action;
use function did_action;

/**
 * Handles the API endpoints for the TagLock plugin.
 * Orchestrates route registration.
 */
final class ApiController {

	public function __construct(
		private readonly ApiRouteRegistrationService $routeRegistrationService
	) {
		add_action( 'rest_api_init', [ $this->routeRegistrationService, 'registerRoutes' ] );

		// Safety: if instantiated after rest_api_init, register immediately.
		if ( did_action( 'rest_api_init' ) ) {
			$this->routeRegistrationService->registerRoutes();
		}
	}
}
