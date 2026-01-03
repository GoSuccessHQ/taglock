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

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Util\HookUtil;

defined( 'ABSPATH' ) || exit;

define( 'TAGLOCK_FILE', __FILE__ );

require_once __DIR__ . '/vendor/autoload.php';

add_filter( 'plugin_action_links_' . plugin_basename( TAGLOCK_FILE ), static function ( array $links ): array {
	$settingsUrl = admin_url( 'options-general.php?page=taglock-settings' );
	$proUrl = 'https://gosuccess.io/taglock';

	$actionLinks = [
		'settings' => '<a href="' . esc_url( $settingsUrl ) . '">' . esc_html__( 'Settings', 'taglock' ) . '</a>',
		'upgrade'  => '<a href="' . esc_url( $proUrl ) . '" target="_blank" rel="noopener noreferrer"><strong>' . esc_html__( 'Upgrade to Pro', 'taglock' ) . '</strong></a>',
	];

	return array_merge( $actionLinks, $links );
} );

/**
 * Initialize the TagLock plugin
 */
function taglock(): void {
	HookUtil::doAction( HookAction::BEFORE_INIT );

	\GoSuccess\TagLock\Core\Plugin::getInstance();

	HookUtil::doAction( HookAction::AFTER_INIT );
}

taglock();
