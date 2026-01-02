<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service\Api;

use GoSuccess\TagLock\DTO\ApiResponse;
use GoSuccess\TagLock\Service\LoggerService;
use Throwable;
use WP_REST_Request;

/**
 * Wraps REST callbacks to convert uncaught exceptions into standardized responses.
 */
final class ApiExceptionService {

	public function __construct(
		private readonly LoggerService $logger
	) {
	}

	/**
	 * @param callable(WP_REST_Request): mixed $callback
	 * @return callable(WP_REST_Request): mixed
	 */
	public function wrapCallback( callable $callback ): callable {
		return function ( WP_REST_Request $request ) use ( $callback ) {
			try {
				return $callback( $request );
			} catch ( Throwable $exception ) {
				$this->logger->error( __( 'Uncaught exception in REST API', 'taglock' ), [
					'exception' => get_class( $exception ),
					'message'   => $exception->getMessage(),
					'file'      => $exception->getFile(),
					'line'      => $exception->getLine(),
					'route'     => $request->get_route(),
				] );

				return ApiResponse::error(
					__( 'An internal error occurred. Please try again later.', 'taglock' ),
					'internal_error',
					500
				);
			}
		};
	}
}
