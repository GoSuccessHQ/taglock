<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

use GoSuccess\TagLock\Service\ShortcodeService;

use function add_shortcode;

/**
 * Registers the TagLock shortcode.
 */
final class ShortcodeController {

	public function __construct(
		private readonly ShortcodeService $shortcodeService
	) {
		add_shortcode( 'taglock', [ $this->shortcodeService, 'renderShortcode' ] );
	}
}
