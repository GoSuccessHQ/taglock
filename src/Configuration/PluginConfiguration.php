<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Configuration;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin configuration values.
 *
 * Centralizes all plugin-wide configuration constants.
 */
final class PluginConfiguration {

	public function __construct(
		// API Configuration
		public readonly string $apiNamespace = 'taglock/v1',

		// External URLs
		public readonly string $proLandingUrl = 'https://gosuccess.io/taglock',

		// Database Configuration
		public readonly string $dbVersion = '2',
		public readonly string $dbVersionOption = 'taglock_db_version',
		public readonly string $ruleTableName = 'taglock_rule',
		public readonly string $requiredTagTableName = 'taglock_rule_required_tag',
		public readonly string $engagementTagTableName = 'taglock_rule_engagement_tag',

		// Option Names
		public readonly string $connectionStatusOption = 'taglock_connection_status',
		public readonly string $installedVersionOption = 'taglock_installed_version',
		public readonly string $klicktippUsernameOption = 'taglock_klicktipp_username',
		public readonly string $klicktippPasswordOption = 'taglock_klicktipp_password',

		// Cron Configuration
		public readonly string $connectionCronHook = 'taglock_check_connection',

		// Cache Configuration
		public readonly string $cacheGroup = 'taglock',

		// Nonce Actions
		public readonly string $accessCheckNonce = 'taglock_access_check'
	) {
	}
}
