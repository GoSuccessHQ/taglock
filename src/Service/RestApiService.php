<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Service\Api\ApiExceptionService;
use GoSuccess\TagLock\Util\HookUtil;
use Throwable;
use WP_REST_Request;

use function method_exists;
use function register_rest_route;

/**
 * REST API Service (compatibility layer)
 *
 * This service may still be referenced by an older cached DI container in some environments.
 * It registers routes using the new handler-based ApiRouteInterface contract when available.
 */
final class RestApiService {

	/**
	 * @param iterable<mixed> $routes
	 */
	public function __construct(
		private readonly iterable $routes,
		private readonly LoggerService $logger,
		private readonly ?PluginConfiguration $pluginConfiguration = null,
		private readonly ?ApiExceptionService $exceptionService = null
	) {
	}

	public function registerRoutes(): void {
		HookUtil::doAction( HookAction::BEFORE_REGISTER_API_ROUTES );

		$routeCount = 0;
		$namespace  = $this->pluginConfiguration?->apiNamespace ?? 'taglock/v1';

		foreach ( $this->routes as $route ) {
			HookUtil::doAction( HookAction::BEFORE_REGISTER_ROUTE, $route );

			// New contract: handler-based
			if ( method_exists( $route, 'getMethodHandlers' ) && method_exists( $route, 'getRoute' ) ) {
				$routeArgs = [];
				foreach ( $route->getMethodHandlers() as $handler ) {
					$callback = [ $handler, 'callback' ];
					if ( $this->exceptionService !== null ) {
						$callback = $this->exceptionService->wrapCallback( $callback );
					} else {
						$callback = static function ( WP_REST_Request $request ) use ( $callback ) {
							try {
								return $callback( $request );
							} catch ( Throwable $exception ) {
								return new \WP_Error(
									'internal_error',
									__( 'An internal error occurred. Please try again later.', 'taglock' ),
									[ 'status' => 500 ]
								);
							}
						};
					}

					$handlerArgs = [
						'methods'             => $handler->method->value,
						'callback'            => $callback,
						'permission_callback' => [ $handler, 'permissionCallback' ],
					];

					if ( $handler->args !== [] ) {
						$handlerArgs['args'] = $handler->args;
					}

					$routeArgs[] = $handlerArgs;
				}

				register_rest_route( $namespace, $route->getRoute(), $routeArgs );
				$routeCount++;

				$this->logger->debug( __( 'API route registered', 'taglock' ), [
					'namespace' => $namespace,
					'route'     => $route->getRoute(),
				] );

				HookUtil::doAction( HookAction::AFTER_REGISTER_ROUTE, $route );
				continue;
			}

			// Old contract: self-registering
			if ( method_exists( $route, 'register' ) ) {
				$route->register();
				$routeCount++;
				HookUtil::doAction( HookAction::AFTER_REGISTER_ROUTE, $route );
				continue;
			}

			$this->logger->error( __( 'Invalid API route instance provided', 'taglock' ), [
				'type' => is_object( $route ) ? $route::class : gettype( $route ),
			] );
		}

		$this->logger->info( __( 'All API routes registered', 'taglock' ), [ 'count' => $routeCount ] );

		HookUtil::doAction( HookAction::AFTER_REGISTER_API_ROUTES );
	}
}
