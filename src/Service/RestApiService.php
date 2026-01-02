<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

/**
 * REST API Service
 *
 * Registers all REST API routes for TagLock.
 *
 * @param iterable<\GoSuccess\TagLock\Contract\ApiRouteInterface> $routes
 */
final class RestApiService {

	/**
	 * @param iterable<\GoSuccess\TagLock\Contract\ApiRouteInterface> $routes All API routes.
	 * @param LoggerService $logger Logger service.
	 */
	public function __construct(
		private readonly iterable $routes,
		private readonly LoggerService $logger
	) {
	}

	/**
	 * Register all API routes.
	 */
	public function registerRoutes(): void {
		HookUtil::doAction( HookAction::BEFORE_REGISTER_API_ROUTES );

		$routeCount = 0;
		foreach ( $this->routes as $route ) {
			HookUtil::doAction( HookAction::BEFORE_REGISTER_ROUTE, $route );

			$route->register();

			$routeCount++;
			$this->logger->debug( __( 'API route registered', 'taglock' ), [
				'namespace' => $route->getNamespace(),
				'route'     => $route->getRoute(),
			] );

			HookUtil::doAction( HookAction::AFTER_REGISTER_ROUTE, $route );
		}

		$this->logger->info( __( 'All API routes registered', 'taglock' ), [ 'count' => $routeCount ] );

		HookUtil::doAction( HookAction::AFTER_REGISTER_API_ROUTES );
	}
}
