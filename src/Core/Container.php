<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Core;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Controller\ActivationController;
use GoSuccess\TagLock\Controller\ApiController;
use GoSuccess\TagLock\Controller\MenuController;
use GoSuccess\TagLock\Controller\RestResponseController;
use GoSuccess\TagLock\Controller\ScriptController;
use GoSuccess\TagLock\Controller\ShortcodeController;
use GoSuccess\TagLock\Handler\CheckAccessHandler;
use GoSuccess\TagLock\Provider\CrmProviderFactory;
use GoSuccess\TagLock\Provider\KlickTippProvider;
use GoSuccess\TagLock\Repository\RuleRepository;
use GoSuccess\TagLock\Route\AccessCheckRoute;
use GoSuccess\TagLock\Route\RuleRoute;
use GoSuccess\TagLock\Route\RulesRoute;
use GoSuccess\TagLock\Route\SettingsRoute;
use GoSuccess\TagLock\Route\TagsRoute;
use GoSuccess\TagLock\Service\AccessValidationService;
use GoSuccess\TagLock\Service\AdminMenuService;
use GoSuccess\TagLock\Service\ApiExceptionService;
use GoSuccess\TagLock\Service\ApiRouteRegistrationService;
use GoSuccess\TagLock\Service\AssetService;
use GoSuccess\TagLock\Service\DatabaseMigrationService;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Service\ProStatusService;
use GoSuccess\TagLock\Service\RestResponseNormalizationService;
use GoSuccess\TagLock\Service\ShortcodeService;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Simple Dependency Injection Container
 *
 * A lightweight DI container that replaces Symfony DI.
 * All services are lazily instantiated and cached as singletons.
 */
final class Container {

	/** @var array<string, object> */
	private array $services = [];

	/** @var array<string, callable> */
	private array $factories = [];

	public function __construct() {
		$this->registerFactories();
	}

	/**
	 * Get a service by class name.
	 *
	 * @template T of object
	 * @param class-string<T> $id
	 * @return T
	 */
	public function get( string $id ): object {
		if ( ! isset( $this->services[ $id ] ) ) {
			if ( ! isset( $this->factories[ $id ] ) ) {
				throw new \RuntimeException( "Service not found: {$id}" );
			}
			$this->services[ $id ] = ( $this->factories[ $id ] )();
		}

		return $this->services[ $id ];
	}

	/**
	 * Check if a service exists.
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] );
	}

	/**
	 * Get all services tagged as 'taglock.service' (controllers that register hooks).
	 *
	 * @return array<string>
	 */
	public function getServiceIds(): array {
		return [
			ActivationController::class,
			ApiController::class,
			MenuController::class,
			RestResponseController::class,
			ScriptController::class,
			ShortcodeController::class,
		];
	}

	/**
	 * Get all API route instances.
	 *
	 * @return array<ApiRouteInterface>
	 */
	public function getRoutes(): array {
		return [
			$this->get( AccessCheckRoute::class ),
			$this->get( RuleRoute::class ),
			$this->get( RulesRoute::class ),
			$this->get( SettingsRoute::class ),
			$this->get( TagsRoute::class ),
		];
	}

	/**
	 * Register all service factories.
	 */
	private function registerFactories(): void {
		// === Leaf services (no dependencies) ===

		$this->factories[ LoggerService::class ] = fn() => new LoggerService();

		$this->factories[ ProStatusService::class ] = fn() => new ProStatusService();

		$this->factories[ PluginConfiguration::class ] = fn() => new PluginConfiguration();

		$this->factories[ RestResponseNormalizationService::class ] = fn() => new RestResponseNormalizationService();

		// === Services with dependencies ===

		$this->factories[ RuleRepository::class ] = fn() => new RuleRepository(
			$this->get( PluginConfiguration::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ DatabaseMigrationService::class ] = fn() => new DatabaseMigrationService(
			$this->get( PluginConfiguration::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ AssetService::class ] = fn() => new AssetService(
			$this->get( PluginConfiguration::class ),
			$this->get( ProStatusService::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ AdminMenuService::class ] = fn() => new AdminMenuService(
			$this->get( LoggerService::class ),
			$this->get( PluginConfiguration::class ),
			$this->get( ProStatusService::class )
		);

		$this->factories[ ApiExceptionService::class ] = fn() => new ApiExceptionService(
			$this->get( LoggerService::class )
		);

		// === Providers ===

		$this->factories[ KlickTippProvider::class ] = fn() => new KlickTippProvider(
			$this->get( PluginConfiguration::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ CrmProviderFactory::class ] = fn() => new CrmProviderFactory(
			$this->get( KlickTippProvider::class )
		);

		$this->factories[ CrmProviderInterface::class ] = fn() => $this->get( CrmProviderFactory::class )->getProvider();

		// === Services depending on CRM ===

		$this->factories[ AccessValidationService::class ] = fn() => new AccessValidationService(
			$this->get( CrmProviderInterface::class ),
			$this->get( RuleRepository::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ ShortcodeService::class ] = fn() => new ShortcodeService(
			$this->get( PluginConfiguration::class ),
			$this->get( RuleRepository::class ),
			$this->get( LoggerService::class ),
			$this->get( AssetService::class )
		);

		// === Handlers ===

		$this->factories[ CheckAccessHandler::class ] = fn() => new CheckAccessHandler(
			$this->get( PluginConfiguration::class ),
			$this->get( AccessValidationService::class ),
			$this->get( LoggerService::class )
		);

		// === Routes ===

		$this->factories[ AccessCheckRoute::class ] = fn() => new AccessCheckRoute(
			$this->get( CheckAccessHandler::class )
		);

		$this->factories[ RuleRoute::class ] = fn() => new RuleRoute(
			$this->get( RuleRepository::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ RulesRoute::class ] = fn() => new RulesRoute(
			$this->get( RuleRepository::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ SettingsRoute::class ] = fn() => new SettingsRoute(
			$this->get( PluginConfiguration::class ),
			$this->get( LoggerService::class ),
			$this->get( CrmProviderInterface::class )
		);

		$this->factories[ TagsRoute::class ] = fn() => new TagsRoute(
			$this->get( CrmProviderInterface::class ),
			$this->get( LoggerService::class )
		);

		// === API Route Registration (needs all routes) ===

		$this->factories[ ApiRouteRegistrationService::class ] = fn() => new ApiRouteRegistrationService(
			$this->get( PluginConfiguration::class ),
			$this->getRoutes(),
			$this->get( ApiExceptionService::class ),
			$this->get( LoggerService::class )
		);

		// === Controllers ===

		$this->factories[ ActivationController::class ] = fn() => new ActivationController(
			$this->get( PluginConfiguration::class ),
			$this->get( DatabaseMigrationService::class ),
			$this->get( CrmProviderInterface::class ),
			$this->get( LoggerService::class )
		);

		$this->factories[ ApiController::class ] = fn() => new ApiController(
			$this->get( ApiRouteRegistrationService::class )
		);

		$this->factories[ MenuController::class ] = fn() => new MenuController(
			$this->get( AdminMenuService::class )
		);

		$this->factories[ RestResponseController::class ] = fn() => new RestResponseController(
			$this->get( RestResponseNormalizationService::class )
		);

		$this->factories[ ScriptController::class ] = fn() => new ScriptController(
			$this->get( AssetService::class )
		);

		$this->factories[ ShortcodeController::class ] = fn() => new ShortcodeController(
			$this->get( ShortcodeService::class )
		);
	}
}
