<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

use function __;use function defined;
use function register_rest_route;

defined( 'ABSPATH' ) || exit;

/**
 * Registers TagLock REST API routes.
 */
final class ApiRouteRegistrationService {

	/**
	 * @param iterable<ApiRouteInterface> $routes
	 */
	public function __construct(
		private readonly PluginConfiguration $pluginConfiguration,
		private readonly iterable $routes,
		private readonly ApiExceptionService $exceptionService,
		private readonly LoggerService $logger
	) {
	}

	public function registerRoutes(): void {
		HookUtil::doAction( HookAction::BEFORE_REGISTER_API_ROUTES );

		$routeCount = 0;
		foreach ( $this->routes as $route ) {
			$routeArgs = [];
			foreach ( $route->getMethodHandlers() as $handler ) {
				$handlerArgs = [
					'methods'             => $handler->getMethod()->value,
					'callback'            => $this->exceptionService->wrapCallback( [ $handler, 'callback' ] ),
					'permission_callback' => [ $handler, 'permissionCallback' ],
				];

				$args = $handler->getArgs();
				if ( $args !== [] ) {
					$handlerArgs['args'] = $args;
				}

				$routeArgs[] = $handlerArgs;
			}

			HookUtil::doAction( HookAction::BEFORE_REGISTER_ROUTE, $route );

			register_rest_route(
				$this->pluginConfiguration->apiNamespace,
				$route->getRoute(),
				$routeArgs
			);

			$routeCount++;
			$this->logger->debug( __( 'API route registered', 'taglock' ), [
				'namespace' => $this->pluginConfiguration->apiNamespace,
				'route'     => $route->getRoute(),
			] );

			HookUtil::doAction( HookAction::AFTER_REGISTER_ROUTE, $route );
		}

		$this->logger->info( __( 'All API routes registered', 'taglock' ), [ 'count' => $routeCount ] );

		HookUtil::doAction( HookAction::AFTER_REGISTER_API_ROUTES );
	}
}
