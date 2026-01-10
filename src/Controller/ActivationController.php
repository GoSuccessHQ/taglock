<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Service\DatabaseMigrationService;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\HookUtil;
use GoSuccess\TagLock\Util\PluginUtil;

use function __;
use function add_action;
use function defined;
use function get_option;
use function register_activation_hook;
use function register_deactivation_hook;
use function time;
use function update_option;
use function wp_clear_scheduled_hook;
use function wp_mkdir_p;
use function wp_next_scheduled;
use function wp_schedule_event;
use function wp_upload_dir;

defined( 'ABSPATH' ) || exit;

/**
 * Activation Controller
 *
 * Handles plugin activation/deactivation tasks.
 */
final class ActivationController {

	public function __construct(
		private readonly PluginConfiguration $config,
		private readonly DatabaseMigrationService $databaseMigrationService,
		private readonly CrmProviderInterface $provider,
		private readonly LoggerService $logger
	) {
		register_activation_hook( TAGLOCK_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( TAGLOCK_FILE, [ $this, 'deactivate' ] );

		// Ensure custom database tables exist after plugin updates.
		add_action( 'plugins_loaded', [ $this->databaseMigrationService, 'install' ] );

		// Cron: Check KlickTipp connection hourly.
		add_action( $this->config->connectionCronHook, [ $this, 'checkConnection' ] );
		add_action( 'plugins_loaded', [ $this, 'ensureConnectionCronScheduled' ] );
	}

	public function activate(): void {
		HookUtil::doAction( HookAction::BEFORE_ACTIVATION );

		// Ensure required runtime directories exist using wp_upload_dir().
		$uploadDir = wp_upload_dir();
		wp_mkdir_p( $uploadDir['basedir'] . '/taglock/logs' );

		// Ensure custom database tables exist.
		$this->databaseMigrationService->install();

		// Store installed version for future update detection.
		update_option( $this->config->installedVersionOption, PluginUtil::getPluginVersion() );

		// Schedule and run initial connection check.
		$this->ensureConnectionCronScheduled();
		$this->checkConnection();

		$this->logger->info( __( 'Plugin activated', 'taglock' ) );

		HookUtil::doAction( HookAction::AFTER_ACTIVATION );
	}

	public function deactivate(): void {
		HookUtil::doAction( HookAction::BEFORE_DEACTIVATION );

		wp_clear_scheduled_hook( $this->config->connectionCronHook );

		$this->logger->info( __( 'Plugin deactivated', 'taglock' ) );

		HookUtil::doAction( HookAction::AFTER_DEACTIVATION );
	}

	public function ensureConnectionCronScheduled(): void {
		if ( wp_next_scheduled( $this->config->connectionCronHook ) ) {
			return;
		}

		wp_schedule_event( time() + 60, 'hourly', $this->config->connectionCronHook );
	}

	public function checkConnection(): void {
		$isConnected = $this->provider->isAuthenticated();
		$error       = $isConnected ? '' : $this->provider->getLastError();
		$payload     = [
			'is_connected' => $isConnected,
			'checked_at'   => time(),
			'error'        => $error,
		];

		update_option( $this->config->connectionStatusOption, $payload );

		$this->logger->debug( __( 'Connection status updated', 'taglock' ), [
			'payload' => $payload,
		] );
	}
}
