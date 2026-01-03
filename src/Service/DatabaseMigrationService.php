<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Configuration\PluginConfiguration;

use function __;
use function current_time;
use function dbDelta;
use function defined;
use function function_exists;
use function get_option;
use function is_string;
use function update_option;

defined( 'ABSPATH' ) || exit;

/**
 * Database Migration Service
 *
 * Manages installation and migration of TagLock custom database tables.
 */
final class DatabaseMigrationService {

	public function __construct(
		private readonly PluginConfiguration $config,
		private readonly LoggerService $logger
	) {}

	/**
	 * Install or migrate database tables.
	 *
	 * Runs on plugin activation and on plugins_loaded to handle updates.
	 */
	public function install(): void {
		$installed = get_option( $this->config->dbVersionOption, '' );
		$installed = is_string( $installed ) ? $installed : '';

		if ( $installed === $this->config->dbVersion ) {
			return;
		}

		global $wpdb;

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$charsetCollate = $wpdb->get_charset_collate();
		$rulesTable = $wpdb->prefix . $this->config->ruleTableName;
		$requiredTagsTable = $wpdb->prefix . $this->config->requiredTagTableName;
		$engagementTagsTable = $wpdb->prefix . $this->config->engagementTagTableName;

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

		update_option( $this->config->dbVersionOption, $this->config->dbVersion );
		$this->logger->info( __( 'Database tables installed/updated', 'taglock' ), [ 'version' => $this->config->dbVersion ] );
	}
}
