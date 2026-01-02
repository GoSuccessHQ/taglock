<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Enum;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress hook filters used by TagLock.
 *
 * Provides type-safe access to all plugin filter hooks for third-party integrations.
 */
enum HookFilter: string {

	// Configuration
	case CONFIG_PATHS = 'taglock_config_paths';
	case CONFIG_FILES = 'taglock_config_files';

	// Access Control
	case ACCESS_DENIED_RESPONSE = 'taglock_access_denied_response';
	case ACCESS_GRANTED_RESPONSE = 'taglock_access_granted_response';
	case ADMIN_BYPASS_ENABLED = 'taglock_admin_bypass_enabled';
	case PROTECTED_CONTENT = 'taglock_protected_content';

	// Settings
	case SETTINGS_FIELDS = 'taglock_settings_fields';
	case SETTINGS_SECTIONS = 'taglock_settings_sections';

	// Shortcode
	case SHORTCODE_ATTRIBUTES = 'taglock_shortcode_attributes';
	case SHORTCODE_CONTAINER_HTML = 'taglock_shortcode_container_html';

	// CRM Provider
	case CRM_PROVIDER = 'taglock_crm_provider';
	case API_CREDENTIALS = 'taglock_api_credentials';
}
