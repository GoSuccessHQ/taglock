<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Controller;

use GoSuccess\TagLock\Service\RestExceptionHandlerService;

use function add_filter;

/**
 * Registers REST exception handling hooks.
 */
final class RestExceptionController {

	public function __construct(
		private readonly RestExceptionHandlerService $restExceptionHandlerService
	) {
		add_filter( 'rest_request_after_callbacks', [ $this->restExceptionHandlerService, 'handleException' ], 10, 3 );
	}
}
