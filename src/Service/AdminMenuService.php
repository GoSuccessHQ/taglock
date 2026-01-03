<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

use function add_options_page;
use function admin_url;
use function array_merge;
use function esc_html__;
use function esc_url;

/**
 * Admin Menu Service
 *
 * Registers the TagLock settings page under Settings menu.
 */
final class AdminMenuService {

	public function __construct(
		private readonly LoggerService $logger,
		private readonly PluginConfiguration $pluginConfiguration
	) {}

	/**
	 * Adds plugin action links on the Plugins screen.
	 *
	 * @param array<string, string> $links
	 * @return array<string, string>
	 */
	public function addPluginActionLinks( array $links ): array {
		$settingsUrl = admin_url( 'options-general.php?page=taglock-settings' );
		$proUrl = $this->pluginConfiguration->proLandingUrl;

		$actionLinks = [
			'settings' => '<a href="' . esc_url( $settingsUrl ) . '">' . esc_html__( 'Settings', 'taglock' ) . '</a>',
			'upgrade'  => '<a href="' . esc_url( $proUrl ) . '" target="_blank" rel="noopener noreferrer"><strong>' . esc_html__( 'Upgrade to Pro', 'taglock' ) . '</strong></a>',
		];

		return array_merge( $actionLinks, $links );
	}

	/**
	 * Add the settings page to WordPress admin.
	 */
	public function addMenu(): void {
		HookUtil::doAction( HookAction::BEFORE_ADD_MENU );

		add_options_page(
			esc_html__( 'TagLock Settings', 'taglock' ),
			esc_html__( 'TagLock', 'taglock' ),
			'manage_options',
			'taglock-settings',
			[ $this, 'renderSettingsPage' ]
		);

		$this->logger->debug( __( 'Admin menu registered', 'taglock' ) );

		HookUtil::doAction( HookAction::AFTER_ADD_MENU );
	}

	/**
	 * Render the settings page.
	 */
	public function renderSettingsPage(): void {
		HookUtil::doAction( HookAction::BEFORE_RENDER_ADMIN_PAGE );

		echo '<div id="taglock-admin-root"></div>';

		HookUtil::doAction( HookAction::AFTER_RENDER_ADMIN_PAGE );
	}
}
