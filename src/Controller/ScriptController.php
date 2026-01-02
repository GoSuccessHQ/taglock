<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\TagLock\Service\AssetService;

use function add_action;

/**
 * Registers asset enqueue hooks.
 */
final class ScriptController {

	public function __construct(
		private readonly AssetService $assetService
	) {
		add_action( 'admin_enqueue_scripts', [ $this->assetService, 'enqueueAdminAssets' ] );
		add_action( 'wp_enqueue_scripts', [ $this->assetService, 'enqueueFrontendAssets' ] );
	}
}
