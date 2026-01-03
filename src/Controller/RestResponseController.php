<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

use GoSuccess\TagLock\Service\RestResponseNormalizationService;

use function add_filter;
use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Registers REST response normalization hooks.
 */
final class RestResponseController {

	public function __construct(
		private readonly RestResponseNormalizationService $restResponseNormalizationService
	) {
		add_filter( 'rest_post_dispatch', [ $this->restResponseNormalizationService, 'normalize' ], 10, 3 );
	}
}
