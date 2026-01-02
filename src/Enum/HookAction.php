<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Enum;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress hook actions used by TagLock.
 *
 * Provides type-safe access to all plugin action hooks for third-party integrations.
 */
enum HookAction: string {

	// Plugin Lifecycle
	case BEFORE_INIT = 'taglock_before_init';
	case AFTER_INIT = 'taglock_after_init';
	case BEFORE_CONTAINER_BUILD = 'taglock_before_container_build';
	case AFTER_CONTAINER_BUILD = 'taglock_after_container_build';
	case CONTAINER_PRE_CONFIGURE = 'taglock_container_pre_configure';
	case CONTAINER_PRE_COMPILE = 'taglock_container_pre_compile';
	case CONTAINER_COMPILED = 'taglock_container_compiled';
	case SERVICE_LOADED = 'taglock_service_loaded';
	case PLUGIN_INITIALIZED = 'taglock_plugin_initialized';

	// Activation
	case BEFORE_ACTIVATION = 'taglock_before_activation';
	case AFTER_ACTIVATION = 'taglock_after_activation';
	case BEFORE_DEACTIVATION = 'taglock_before_deactivation';
	case AFTER_DEACTIVATION = 'taglock_after_deactivation';

	// API Routes
	case BEFORE_REGISTER_API_ROUTES = 'taglock_before_register_api_routes';
	case AFTER_REGISTER_API_ROUTES = 'taglock_after_register_api_routes';
	case BEFORE_REGISTER_ROUTE = 'taglock_before_register_route';
	case AFTER_REGISTER_ROUTE = 'taglock_after_register_route';
	case API_EXCEPTION_CAUGHT = 'taglock_api_exception_caught';

	// Menu
	case BEFORE_ADD_MENU = 'taglock_before_add_menu';
	case AFTER_ADD_MENU = 'taglock_after_add_menu';
	case MENU_REGISTERED = 'taglock_menu_registered';
	case BEFORE_RENDER_ADMIN_PAGE = 'taglock_before_render_admin_page';
	case AFTER_RENDER_ADMIN_PAGE = 'taglock_after_render_admin_page';

	// Access Control
	case BEFORE_ACCESS_CHECK = 'taglock_before_access_check';
	case AFTER_ACCESS_CHECK = 'taglock_after_access_check';
	case ACCESS_GRANTED = 'taglock_access_granted';
	case ACCESS_DENIED = 'taglock_access_denied';

	// Shortcode
	case BEFORE_SHORTCODE_RENDER = 'taglock_before_shortcode_render';
	case AFTER_SHORTCODE_RENDER = 'taglock_after_shortcode_render';

	// CRM Provider
	case BEFORE_CRM_API_CALL = 'taglock_before_crm_api_call';
	case AFTER_CRM_API_CALL = 'taglock_after_crm_api_call';
	case CRM_API_ERROR = 'taglock_crm_api_error';
}
