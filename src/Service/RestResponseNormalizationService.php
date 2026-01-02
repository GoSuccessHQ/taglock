<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\DTO\ApiResponse;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

use function is_wp_error;

/**
 * Normalizes REST API responses for TagLock.
 *
 * Ensures WP_Error responses are converted into the plugin's ApiResponse schema.
 */
final class RestResponseNormalizationService {

	/**
	 * @param mixed $result
	 * @return mixed
	 */
	public function normalize( mixed $result, WP_REST_Server $server, WP_REST_Request $request ): mixed {
		// Only handle our plugin's routes
		if ( ! str_starts_with( $request->get_route(), '/taglock/' ) ) {
			return $result;
		}

		if ( ! is_wp_error( $result ) ) {
			return $result;
		}

		/** @var WP_Error $result */
		$status = 400;
		$errorData = $result->get_error_data();
		if ( is_array( $errorData ) && isset( $errorData['status'] ) ) {
			$status = (int) $errorData['status'];
		}

		return ApiResponse::error(
			$result->get_error_message(),
			(string) $result->get_error_code(),
			$status
		);
	}
}
