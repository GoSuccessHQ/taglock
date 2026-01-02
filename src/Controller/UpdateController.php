<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

use GoSuccess\TagLock\Service\LoggerService;

use function add_action;
use function basename;
use function file_exists;
use function get_option;
use function glob;
use function is_dir;
use function unlink;
use function update_option;

/**
 * Update Controller
 *
 * Performs lightweight update tasks when the plugin version changes.
 */
final class UpdateController {

	public function __construct(
		private readonly LoggerService $logger
	) {
		add_action( 'plugins_loaded', [ $this, 'maybeUpdate' ] );
	}

	public function maybeUpdate(): void {
		$currentVersion  = $this->getPluginVersion();
		$installedVersion = (string) get_option( 'taglock_installed_version', '' );

		if ( $installedVersion === $currentVersion ) {
			return;
		}

		// Prevent stale cached containers after updates.
		$this->cleanupContainerCache( WP_CONTENT_DIR . '/cache/taglock' );

		update_option( 'taglock_installed_version', $currentVersion );

		$this->logger->info( __( 'Plugin updated', 'taglock' ), [
			'from' => $installedVersion,
			'to'   => $currentVersion,
		] );
	}

	private function cleanupContainerCache( string $cacheDir ): void {
		if ( ! is_dir( $cacheDir ) ) {
			return;
		}

		$files = glob( $cacheDir . '/container-*.php*' );
		if ( $files === false ) {
			return;
		}

		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				@unlink( $file );
			}
		}

		$this->logger->debug( __( 'Container cache cleaned after update', 'taglock' ), [
			'dir' => $cacheDir,
			'count' => count( $files ),
		] );
	}

	private function getPluginVersion(): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pluginData = get_plugin_data( TAGLOCK_FILE, false, false );

		return $pluginData['Version'] ?? '0.0.0';
	}
}
