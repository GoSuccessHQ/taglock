<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Dto;

use WP_REST_Response;

use function defined;
use function rest_ensure_response;

defined( 'ABSPATH' ) || exit;

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
	 * Create a paginated success response.
	 *
	 * @param array<int, mixed> $items The page items.
	 * @param int $page Current page (1-based).
	 * @param int $perPage Items per page.
	 * @param int $total Total number of items.
	 * @param string $message Optional success message.
	 * @param int $status HTTP status code (default: 200).
	 * @return WP_REST_Response The WordPress REST response.
	 */
	public static function paginatedSuccess(
		array $items,
		int $page,
		int $perPage,
		int $total,
		string $message = '',
		int $status = 200
	): WP_REST_Response {
		$totalPages = $perPage > 0 ? (int) ceil( $total / $perPage ) : 0;

		return self::success(
			[
				'items'      => $items,
				'pagination' => [
					'page'        => $page,
					'per_page'    => $perPage,
					'total'       => $total,
					'total_pages' => $totalPages,
				],
			],
			$message,
			$status
		);
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
