<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\DTO\ApiResponse;
use Throwable;
use WP_REST_Request;

use function add_filter;

/**
 * REST Exception Handler Service
 *
 * Catches unhandled exceptions in REST API requests and returns standardized error responses.
 */
final class RestExceptionHandlerService {

	public function __construct(
		private readonly LoggerService $logger
	) {
		$this->registerHooks();
	}

	/**
	 * Register WordPress hooks.
	 */
	private function registerHooks(): void {
		add_filter( 'rest_request_after_callbacks', [ $this, 'handleException' ], 10, 3 );
	}

	/**
	 * Handle exceptions thrown during REST API requests.
	 *
	 * @param mixed $response The REST response.
	 * @param array<string, mixed> $handler Route handler information.
	 * @param WP_REST_Request $request The REST request.
	 * @return mixed The modified response or original response.
	 */
	public function handleException( mixed $response, array $handler, WP_REST_Request $request ): mixed {
		// Only handle our plugin's routes
		if ( ! str_starts_with( $request->get_route(), '/taglock/' ) ) {
			return $response;
		}

		// If response is already an error, return it
		if ( is_wp_error( $response ) ) {
			// Convert WP_Error to standardized ApiResponse
			return ApiResponse::error(
				$response->get_error_message(),
				$response->get_error_code(),
				$response->get_error_data()['status'] ?? 400
			);
		}

		return $response;
	}

	/**
	 * Exception handler for uncaught exceptions.
	 * This is registered globally for any REST API exception.
	 *
	 * @param Throwable $exception The thrown exception.
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response Standardized error response.
	 */
	public function handleUncaughtException( Throwable $exception, WP_REST_Request $request ): WP_REST_Response {
		$this->logger->error( __( 'Uncaught exception in REST API', 'taglock' ), [
			'exception' => get_class( $exception ),
			'message'   => $exception->getMessage(),
			'file'      => $exception->getFile(),
			'line'      => $exception->getLine(),
			'route'     => $request->get_route(),
		] );

		// Don't expose sensitive error details in production
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return ApiResponse::error(
				sprintf(
					'%s: %s in %s:%d',
					get_class( $exception ),
					$exception->getMessage(),
					$exception->getFile(),
					$exception->getLine()
				),
				'internal_error',
				500
			);
		}

		return ApiResponse::error(
			__( 'An internal error occurred. Please try again later.', 'taglock' ),
			'internal_error',
			500
		);
	}
}
