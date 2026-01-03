<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Database;

use GoSuccess\TagLock\Service\LoggerService;

use function current_time;
use function defined;
use function function_exists;
use function get_option;
use function is_string;
use function update_option;

defined( 'ABSPATH' ) || exit;

/**
 * Installs and migrates TagLock custom tables.
 */
final class RuleTableInstaller {

	private const string DB_VERSION_OPTION = 'taglock_db_version';
	private const string DB_VERSION = '2';

	public function __construct(
		private readonly LoggerService $logger
	) {}

	public function install(): void {
		$installed = get_option( self::DB_VERSION_OPTION, '' );
		$installed = is_string( $installed ) ? $installed : '';

		if ( $installed === self::DB_VERSION ) {
			return;
		}

		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charsetCollate = $wpdb->get_charset_collate();
		$rulesTable = $wpdb->prefix . 'taglock_rule';
		$requiredTagsTable = $wpdb->prefix . 'taglock_rule_required_tag';
		$engagementTagsTable = $wpdb->prefix . 'taglock_rule_engagement_tag';

		$sqlRules = "CREATE TABLE {$rulesTable} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(190) NOT NULL,
			provider varchar(32) NOT NULL DEFAULT 'klicktipp',
			is_active tinyint(1) NOT NULL DEFAULT 1,
			access_mode varchar(20) NOT NULL DEFAULT 'tag_any',
			deny_mode varchar(20) NOT NULL DEFAULT 'message',
			deny_message text NULL,
			teaser_html longtext NULL,
			redirect_post_id bigint(20) unsigned NULL,
			redirect_post_type varchar(20) NULL,
			admin_bypass_enabled tinyint(1) NOT NULL DEFAULT 0,
			engagement_tagging_enabled tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY provider (provider),
			KEY is_active (is_active),
			KEY name (name),
			KEY deny_mode (deny_mode),
			KEY redirect_post_id (redirect_post_id),
			KEY updated_at (updated_at)
		) {$charsetCollate};";

		$sqlRequiredTags = "CREATE TABLE {$requiredTagsTable} (
			rule_id bigint(20) unsigned NOT NULL,
			tag_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (rule_id, tag_id),
			KEY tag_id (tag_id)
		) {$charsetCollate};";

		$sqlEngagementTags = "CREATE TABLE {$engagementTagsTable} (
			rule_id bigint(20) unsigned NOT NULL,
			tag_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (rule_id, tag_id),
			KEY tag_id (tag_id)
		) {$charsetCollate};";

		dbDelta( $sqlRules );
		dbDelta( $sqlRequiredTags );
		dbDelta( $sqlEngagementTags );

		// Seed timestamps if the table is new and empty.
		$now = current_time( 'mysql' );
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'UPDATE %i SET created_at = %s WHERE created_at = %s',
				$rulesTable,
				$now,
				'0000-00-00 00:00:00'
			)
		);
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				'UPDATE %i SET updated_at = %s WHERE updated_at = %s',
				$rulesTable,
				$now,
				'0000-00-00 00:00:00'
			)
		);

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		$this->logger->info( __( 'Database tables installed/updated', 'taglock' ), [ 'version' => self::DB_VERSION ] );
	}
}
