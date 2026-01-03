<?php

/*
 * Plugin Name:       TagLock
 * Plugin URI:        https://gosuccess.io/taglock
 * Description:       Protect WordPress content based on KlickTipp tags - no membership required, 100% cache compatible and secure.
 * Version:           1.0.0
 * Requires at least: 6.8
 * Requires PHP:      8.3
 * Author:            GoSuccess
 * Author URI:        https://gosuccess.io
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       taglock
 */

declare(strict_types=1);

use GoSuccess\TagLock\Core\Plugin;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

defined( 'ABSPATH' ) || exit;

// Check PHP version before loading the plugin.
if ( version_compare( PHP_VERSION, '8.3.0', '<' ) ) {
	add_action( 'admin_notices', static function (): void {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__(
				'TagLock requires PHP 8.3 or higher. Please upgrade your PHP version.',
				'taglock'
			)
		);
	} );
	return;
}

define( 'TAGLOCK_FILE', __FILE__ );

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Initialize the TagLock plugin
 */
function taglock(): void {
	HookUtil::doAction( HookAction::BEFORE_INIT );

	Plugin::getInstance();

	HookUtil::doAction( HookAction::AFTER_INIT );
}

taglock();
