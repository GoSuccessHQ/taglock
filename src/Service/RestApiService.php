<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

use function add_action;

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
		// Force immediate logging to verify service initialization
		error_log( 'TagLock RestApiService instantiated' );
		error_log( 'TagLock $routes type: ' . get_debug_type( $routes ) );
		error_log( 'TagLock $routes is_array: ' . ( is_array( $routes ) ? 'yes' : 'no' ) );
		error_log( 'TagLock $routes is Traversable: ' . ( $routes instanceof \Traversable ? 'yes' : 'no' ) );
		$this->registerHooks();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function registerHooks(): void {
		error_log( 'TagLock RestApiService::registerHooks() - Adding rest_api_init action' );
		add_action( 'rest_api_init', [ $this, 'registerRoutes' ] );
		
		// Also try immediate registration if rest_api_init already fired
		if ( did_action( 'rest_api_init' ) ) {
			error_log( 'TagLock rest_api_init already fired - registering routes immediately' );
			$this->registerRoutes();
		}
	}

	/**
	 * Register all API routes.
	 */
	public function registerRoutes(): void {
		error_log( 'TagLock registerRoutes() called' );
		
		HookUtil::doAction( HookAction::BEFORE_REGISTER_API_ROUTES );

		$routeCount = 0;
		foreach ( $this->routes as $route ) {
			error_log( 'TagLock registering route: ' . get_class( $route ) );
			
			HookUtil::doAction( HookAction::BEFORE_REGISTER_ROUTE, $route );

			$route->register();

			$routeCount++;
			$this->logger->debug( __( 'API route registered', 'taglock' ), [
				'namespace' => $route->getNamespace(),
				'route'     => $route->getRoute(),
			] );

			HookUtil::doAction( HookAction::AFTER_REGISTER_ROUTE, $route );
		}

		error_log( "TagLock registered {$routeCount} routes" );
		
		$this->logger->info( __( 'All API routes registered', 'taglock' ), [ 'count' => $routeCount ] );

		HookUtil::doAction( HookAction::AFTER_REGISTER_API_ROUTES );
	}
}
