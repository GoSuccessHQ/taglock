<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Core;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Initializes the TagLock plugin and registers services using a simple DI container.
 */
final class Plugin {

	private static ?self $instance = null;
	public readonly Container $container;

	/**
	 * Initializes the plugin and registers services.
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		HookUtil::doAction( HookAction::BEFORE_CONTAINER_BUILD );

		$this->container = new Container();

		HookUtil::doAction( HookAction::AFTER_CONTAINER_BUILD, $this->container );

		// Initialize core services (they register WordPress hooks in their constructors)
		$this->initializeServices();

		HookUtil::doAction( HookAction::PLUGIN_INITIALIZED, $this );
	}

	/**
	 * Initialize all controller services.
	 * Controllers register their WordPress hooks in their constructors.
	 */
	private function initializeServices(): void {
		$serviceIds = $this->container->getServiceIds();

		foreach ( $serviceIds as $serviceId ) {
			$this->container->get( $serviceId );
		}
	}

	/**
	 * Get the singleton instance of the plugin.
	 *
	 * @return self The plugin instance.
	 */
	public static function getInstance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
