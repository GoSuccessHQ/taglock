<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\HookUtil;

use function register_activation_hook;
use function register_deactivation_hook;
use function update_option;
use function wp_mkdir_p;

/**
 * Activation Controller
 *
 * Handles plugin activation/deactivation tasks.
 */
final class ActivationController {

	public function __construct(
		private readonly LoggerService $logger
	) {
		register_activation_hook( TAGLOCK_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( TAGLOCK_FILE, [ $this, 'deactivate' ] );
	}

	public function activate(): void {
		HookUtil::doAction( HookAction::BEFORE_ACTIVATION );

		// Ensure required runtime directories exist.
		wp_mkdir_p( WP_CONTENT_DIR . '/cache/taglock' );
		wp_mkdir_p( WP_CONTENT_DIR . '/uploads/taglock/logs' );

		// Store installed version for future update detection.
		update_option( 'taglock_installed_version', $this->getPluginVersion() );

		$this->logger->info( __( 'Plugin activated', 'taglock' ) );

		HookUtil::doAction( HookAction::AFTER_ACTIVATION );
	}

	public function deactivate(): void {
		HookUtil::doAction( HookAction::BEFORE_DEACTIVATION );

		$this->logger->info( __( 'Plugin deactivated', 'taglock' ) );

		HookUtil::doAction( HookAction::AFTER_DEACTIVATION );
	}

	private function getPluginVersion(): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$pluginData = get_plugin_data( TAGLOCK_FILE, false, false );

		return $pluginData['Version'] ?? '0.0.0';
	}
}
