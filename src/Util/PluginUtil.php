<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Util;

use function function_exists;
use function get_plugin_data;

/**
 * Plugin Utilities
 *
 * Provides helper methods for plugin-related information.
 */
final class PluginUtil {

	/**
	 * Get the plugin version from the main plugin file.
	 *
	 * @return string The plugin version.
	 */
	public static function getPluginVersion(): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pluginData = get_plugin_data( TAGLOCK_FILE, false, false );

		return $pluginData['Version'] ?? '0.0.0';
	}

	/**
	 * Get the plugin directory path.
	 *
	 * @return string The plugin directory path.
	 */
	public static function getPluginDir(): string {
		return dirname( TAGLOCK_FILE );
	}

	/**
	 * Get the plugin directory URL.
	 *
	 * @return string The plugin directory URL.
	 */
	public static function getPluginUrl(): string {
		return rtrim( plugin_dir_url( TAGLOCK_FILE ), '/' );
	}
}
