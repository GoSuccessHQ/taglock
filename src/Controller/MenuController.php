<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\TagLock\Service\AdminMenuService;

use function add_action;
use function add_filter;
use function plugin_basename;

/**
 * Registers admin menu hooks.
 */
final class MenuController {

	public function __construct(
		private readonly AdminMenuService $adminMenuService
	) {
		add_action( 'admin_menu', [ $this->adminMenuService, 'addMenu' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( TAGLOCK_FILE ), [ $this->adminMenuService, 'addPluginActionLinks' ] );
	}
}
