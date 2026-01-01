<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\DTO;

use WP_REST_Response;

use function rest_ensure_response;

/**
 * Standardized API Response
 *
 * Provides a consistent response structure for all REST API endpoints.
 */
final class ApiResponse {

	/**
	 * Create a success response.
	 *
	 * @param mixed $data The response data.
	 * @param string $message Optional success message.
	 * @param int $status HTTP status code (default: 200).
	 * @return WP_REST_Response The WordPress REST response.
	 */
	public static function success( mixed $data = null, string $message = '', int $status = 200 ): WP_REST_Response {
		$response = [
			'success' => true,
		];

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		$wpResponse = rest_ensure_response( $response );
		$wpResponse->set_status( $status );
		return $wpResponse;
	}

	/**
	 * Create an error response.
	 *
	 * @param string $message Error message.
	 * @param string $code Error code.
	 * @param int $status HTTP status code (default: 400).
	 * @param array<string, mixed> $data Additional error data.
	 * @return WP_REST_Response The WordPress REST response.
	 */
	public static function error( string $message, string $code = 'error', int $status = 400, array $data = [] ): WP_REST_Response {
		$response = [
			'success' => false,
			'message' => $message,
			'code'    => $code,
		];

		if ( ! empty( $data ) ) {
			$response['data'] = $data;
		}

		$wpResponse = rest_ensure_response( $response );
		$wpResponse->set_status( $status );
		return $wpResponse;
	}

	/**
	 * Create a response with custom structure.
	 *
	 * @param array<string, mixed> $data Response data.
	 * @param int $status HTTP status code (default: 200).
	 * @return WP_REST_Response The WordPress REST response.
	 */
	public static function custom( array $data, int $status = 200 ): WP_REST_Response {
		$wpResponse = rest_ensure_response( $data );
		$wpResponse->set_status( $status );
		return $wpResponse;
	}
}
