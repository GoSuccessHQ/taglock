<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Core;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Util\HookUtil;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

use function array_keys;
use function basename;
use function class_exists;
use function defined;
use function file_exists;
use function function_exists;
use function glob;
use function is_dir;
use function is_string;
use function str_replace;
use function unlink;
use function wp_mkdir_p;

/**
 * Class Plugin
 *
 * Initializes the TagLock plugin and registers services using Symfony's Dependency Injection component.
 */
final class Plugin {

	private static ?self $instance = null;
	public readonly ContainerInterface $container;

	/**
	 * Initializes the plugin and registers services.
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {
		HookUtil::doAction( HookAction::BEFORE_CONTAINER_BUILD );

		$this->container = $this->buildContainer();

		HookUtil::doAction( HookAction::AFTER_CONTAINER_BUILD, $this->container );

		// Initialize core services (they register WordPress hooks in their constructors)
		$this->initializeServices();

		HookUtil::doAction( HookAction::PLUGIN_INITIALIZED, $this );
	}

	/**
	 * Initialize all services tagged with 'taglock.service'.
	 * Services register their WordPress hooks in their constructors.
	 */
	private function initializeServices(): void {
		/** @var array<string> $serviceIds */
		$serviceIds = $this->container->getParameter( 'taglock.service_ids' );

		foreach ( $serviceIds as $serviceId ) {
			$this->container->get( $serviceId );
		}
	}

	/**
	 * Builds and returns the DI container.
	 * Loads service configuration and compiles the container.
	 * Uses cached container in production for better performance.
	 * Cache is automatically invalidated when plugin version changes.
	 *
	 * @return ContainerInterface The compiled dependency injection container.
	 */
	private function buildContainer(): ContainerInterface {
		$version    = $this->getPluginVersion();
		$cacheDir   = WP_CONTENT_DIR . '/cache/taglock';
		$cacheFile  = "{$cacheDir}/container-{$version}.php";
		$isDebug    = defined( 'WP_DEBUG' ) && WP_DEBUG;

		// Clean up old container cache files when version changes
		$this->cleanupOldContainerCaches( $cacheDir, $version );

		// Use ConfigCache to handle cache validation
		$containerConfigCache = new ConfigCache( $cacheFile, $isDebug );
		$containerClass       = $this->getContainerClassName( $version );

		// We check on every request whether the cached container is still usable.
		// In production this stays fast; in development you can bump the plugin version to force rebuild.
		$needsRebuild = ! file_exists( $cacheFile ) || ! $containerConfigCache->isFresh();

		if ( ! $needsRebuild ) {
			// Check if cached container has the correct class
			require_once $cacheFile;
			if ( ! class_exists( $containerClass, false ) ) {
				// Cached container has wrong class (from before versioning was added)
				$needsRebuild = true;
			}
		}

		if ( $needsRebuild ) {
			$container = new ContainerBuilder();

			// Allow Pro version to modify container before configuration
			HookUtil::doAction( HookAction::CONTAINER_PRE_CONFIGURE, $container );

			$configPaths = HookUtil::applyFilter( HookFilter::CONFIG_PATHS, [ __DIR__ . '/../Configuration' ] );
			$loader      = new PhpFileLoader(
				$container,
				new FileLocator( $configPaths )
			);

			$configFiles = HookUtil::applyFilter( HookFilter::CONFIG_FILES, [ 'ServiceConfiguration.php' ] );

			foreach ( $configFiles as $configFile ) {
				$loader->load( $configFile );
			}

			// Allow Pro version to modify container before compilation
			HookUtil::doAction( HookAction::CONTAINER_PRE_COMPILE, $container );

			// Collect service IDs before compilation (findTaggedServiceIds not available after)
			$serviceIds = array_keys( $container->findTaggedServiceIds( 'taglock.service' ) );
			$container->setParameter( 'taglock.service_ids', $serviceIds );

			// Compile the container
			$container->compile();

			// Create cache directory if it doesn't exist
			if ( ! is_dir( $cacheDir ) ) {
				wp_mkdir_p( $cacheDir );
			}

			// Dump the container to cache
			$dumper = new PhpDumper( $container );
			$dumpedContainer = $dumper->dump( [ 'class' => $containerClass ] );
			if ( is_string( $dumpedContainer ) ) {
				$containerConfigCache->write( $dumpedContainer, $container->getResources() );
			}
		}

		// Load the cached container (may already be loaded from class check above)
		if ( ! class_exists( $containerClass, false ) ) {
			require_once $cacheFile;
		}

		/** @var ContainerInterface $runtimeContainer Runtime-generated by Symfony PhpDumper */
		$runtimeContainer = new $containerClass();

		HookUtil::doAction( HookAction::CONTAINER_COMPILED, $runtimeContainer );

		return $runtimeContainer;
	}

	/**
	 * Get the plugin version from the main plugin file.
	 *
	 * @return string The plugin version.
	 */
	private function getPluginVersion(): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pluginData = get_plugin_data( TAGLOCK_FILE );

		return $pluginData['Version'] ?? '1.0.0';
	}

	/**
	 * Get the container class name based on version.
	 *
	 * @param string $version The plugin version.
	 * @return string The container class name.
	 */
	private function getContainerClassName( string $version ): string {
		$cleanVersion = str_replace( '.', '_', $version );

		return "TagLockContainer_{$cleanVersion}";
	}

	/**
	 * Clean up old container cache files from previous versions.
	 *
	 * @param string $cacheDir The cache directory.
	 * @param string $currentVersion The current plugin version.
	 */
	private function cleanupOldContainerCaches( string $cacheDir, string $currentVersion ): void {
		if ( ! is_dir( $cacheDir ) ) {
			return;
		}

		$currentCacheFile = "container-{$currentVersion}.php";
		$files            = glob( "{$cacheDir}/container-*.php" );
		if ( $files !== false ) {
			foreach ( $files as $file ) {
				if ( basename( $file ) !== $currentCacheFile && file_exists( $file ) ) {
					@unlink( $file );
				}
			}
		}

		$metaFiles = glob( "{$cacheDir}/container-*.php.meta*" );
		if ( $metaFiles !== false ) {
			$currentMetaFiles = [
				"{$currentCacheFile}.meta",
				"{$currentCacheFile}.meta.json",
			];

			foreach ( $metaFiles as $metaFile ) {
				if ( ! in_array( basename( $metaFile ), $currentMetaFiles, true ) && file_exists( $metaFile ) ) {
					@unlink( $metaFile );
				}
			}
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
