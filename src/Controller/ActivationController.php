<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Contract\CRMProviderInterface;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Database\RuleTableInstaller;
use GoSuccess\TagLock\Util\HookUtil;

use function add_action;
use function register_activation_hook;
use function register_deactivation_hook;
use function update_option;
use function get_option;
use function wp_next_scheduled;
use function wp_schedule_event;
use function wp_clear_scheduled_hook;
use function wp_mkdir_p;
use function time;

/**
 * Activation Controller
 *
 * Handles plugin activation/deactivation tasks.
 */
final class ActivationController {

	private const string CONNECTION_STATUS_OPTION = 'taglock_connection_status';
	private const string CONNECTION_CRON_HOOK      = 'taglock_check_connection';

	public function __construct(
		private readonly RuleTableInstaller $ruleTableInstaller,
		private readonly CRMProviderInterface $provider,
		private readonly LoggerService $logger
	) {
		register_activation_hook( TAGLOCK_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( TAGLOCK_FILE, [ $this, 'deactivate' ] );

		// Ensure custom database tables exist after plugin updates.
		add_action( 'plugins_loaded', [ $this->ruleTableInstaller, 'install' ] );

		// Cron: Check KlickTipp connection hourly.
		add_action( self::CONNECTION_CRON_HOOK, [ $this, 'checkConnection' ] );
		add_action( 'plugins_loaded', [ $this, 'ensureConnectionCronScheduled' ] );
	}

	public function activate(): void {
		HookUtil::doAction( HookAction::BEFORE_ACTIVATION );

		// Ensure required runtime directories exist.
		wp_mkdir_p( WP_CONTENT_DIR . '/cache/taglock' );
		wp_mkdir_p( WP_CONTENT_DIR . '/uploads/taglock/logs' );

		// Ensure custom database tables exist.
		$this->ruleTableInstaller->install();

		// Store installed version for future update detection.
		update_option( 'taglock_installed_version', $this->getPluginVersion() );

		// Schedule and run initial connection check.
		$this->ensureConnectionCronScheduled();
		$this->checkConnection();

		$this->logger->info( __( 'Plugin activated', 'taglock' ) );

		HookUtil::doAction( HookAction::AFTER_ACTIVATION );
	}

	public function deactivate(): void {
		HookUtil::doAction( HookAction::BEFORE_DEACTIVATION );

		wp_clear_scheduled_hook( self::CONNECTION_CRON_HOOK );

		$this->logger->info( __( 'Plugin deactivated', 'taglock' ) );

		HookUtil::doAction( HookAction::AFTER_DEACTIVATION );
	}

	public function ensureConnectionCronScheduled(): void {
		if ( wp_next_scheduled( self::CONNECTION_CRON_HOOK ) ) {
			return;
		}

		wp_schedule_event( time() + 60, 'hourly', self::CONNECTION_CRON_HOOK );
	}

	public function checkConnection(): void {
		$isConnected = $this->provider->isAuthenticated();
		$error       = $isConnected ? '' : $this->provider->getLastError();
		$payload     = [
			'is_connected' => $isConnected,
			'checked_at'   => time(),
			'error'        => $error,
		];

		update_option( self::CONNECTION_STATUS_OPTION, $payload );

		$this->logger->debug( __( 'Connection status updated', 'taglock' ), [
			'payload' => $payload,
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
