<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use function add_action;
use function plugin_dir_url;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_localize_script;
use function wp_register_script;
use function wp_register_style;

/**
 * Asset Service
 *
 * Handles loading of JavaScript and CSS assets for admin and frontend.
 */
final class AssetService {

	public function __construct(
		private readonly LoggerService $logger
	) {
		$this->registerHooks();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function registerHooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontendAssets' ] );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueueAdminAssets( string $hook ): void {
		// Only load on TagLock settings page
		if ( 'settings_page_taglock-settings' !== $hook ) {
			return;
		}

		$pluginUrl = plugin_dir_url( TAGLOCK_FILE );
		$version   = $this->getPluginVersion();

		// Register admin script (will be built later)
		wp_register_script(
			'taglock-admin',
			$pluginUrl . 'assets/build/admin/index.js',
			[ 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch' ],
			$version,
			true
		);

		// Localize script with REST API data
		wp_localize_script(
			'taglock-admin',
			'taglockAdmin',
			[
				'apiUrl'        => rest_url( 'taglock/v1' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'pluginVersion' => $version,
				'settings'      => [
					'klicktipp_username' => get_option( 'taglock_klicktipp_username', '' ),
					'klicktipp_password' => '', // Never send password to frontend
				],
			]
		);

		wp_enqueue_script( 'taglock-admin' );

		// Register admin styles
		wp_register_style(
			'taglock-admin',
			$pluginUrl . 'assets/build/admin/style-index.css',
			[],
			$version
		);

		wp_enqueue_style( 'taglock-admin' );

		$this->logger->debug( 'Admin assets enqueued' );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueueFrontendAssets(): void {
		// Only enqueue if shortcode is present
		if ( ! $this->hasShortcode() ) {
			return;
		}

		$pluginUrl = plugin_dir_url( TAGLOCK_FILE );
		$version   = $this->getPluginVersion();

		// Register frontend script
		wp_register_script(
			'taglock-frontend',
			$pluginUrl . 'assets/build/frontend/index.js',
			[ 'wp-element', 'wp-api-fetch' ],
			$version,
			true
		);

		// Localize script with REST API data
		wp_localize_script(
			'taglock-frontend',
			'taglockFrontend',
			[
				'apiUrl' => rest_url( 'taglock/v1' ),
			]
		);

		wp_enqueue_script( 'taglock-frontend' );

		// Register frontend styles
		wp_register_style(
			'taglock-frontend',
			$pluginUrl . 'assets/build/frontend/style-index.css',
			[],
			$version
		);

		wp_enqueue_style( 'taglock-frontend' );

		$this->logger->debug( 'Frontend assets enqueued' );
	}

	/**
	 * Check if the current post/page has the taglock shortcode.
	 *
	 * @return bool True if shortcode is present.
	 */
	private function hasShortcode(): bool {
		global $post;

		if ( ! $post ) {
			return false;
		}

		return has_shortcode( $post->post_content, 'taglock' );
	}

	/**
	 * Get the plugin version.
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
}
