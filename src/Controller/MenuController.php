<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\TagLock\Service\AdminMenuService;

use function add_action;

/**
 * Registers admin menu hooks.
 */
final class MenuController {

	public function __construct(
		private readonly AdminMenuService $adminMenuService
	) {
		add_action( 'admin_menu', [ $this->adminMenuService, 'addMenu' ] );
	}
}
