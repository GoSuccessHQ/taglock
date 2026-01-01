<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

use function add_action;
use function add_options_page;
use function esc_html__;

/**
 * Admin Menu Service
 *
 * Registers the TagLock settings page under Settings menu.
 */
final class AdminMenuService {

	public function __construct(
		private readonly LoggerService $logger
	) {
		$this->registerHooks();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function registerHooks(): void {
		add_action( 'admin_menu', [ $this, 'addMenu' ] );
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

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div id="taglock-admin-root"></div>
		</div>
		<?php

		HookUtil::doAction( HookAction::AFTER_RENDER_ADMIN_PAGE );
	}
}
