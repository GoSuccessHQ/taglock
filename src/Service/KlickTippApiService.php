<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use stdClass;
use WP_Error;

use function defined;
use function is_array;
use function is_wp_error;
use function unserialize;
use function urlencode;
use function wp_remote_get;
use function wp_remote_post;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_response_code;

defined( 'ABSPATH' ) || exit;

/**
 * KlickTipp API Service
 *
 * WordPress HTTP API based client for KlickTipp REST API.
 * Implements only the methods needed by TagLock Plugin.
 */
final class KlickTippApiService {

	private string $apiUrl = 'https://api.klicktipp.com';
	private ?string $sessionId = null;
	private ?string $sessionName = null;
	private string $lastError = '';

	/**
	 * Login to KlickTipp API
	 *
	 * @param string $username KlickTipp username
	 * @param string $password KlickTipp password
	 * @return bool True on success
	 */
	public function login( string $username, string $password ): bool {
		if ( empty( $username ) || empty( $password ) ) {
			$this->lastError = 'Invalid credentials';
			return false;
		}

		$response = $this->post(
			'/account/login',
			[
				'username' => $username,
				'password' => $password,
			],
			false
		);

		if ( $response && ! empty( $response->data ) ) {
			if ( isset( $response->data->sessid, $response->data->session_name ) ) {
				$this->sessionId = $response->data->sessid;
				$this->sessionName = $response->data->session_name;
			}
			return true;
		}

		return false;
	}

	/**
	 * Logout from KlickTipp API
	 *
	 * @return bool True on success
	 */
	public function logout(): bool {
		$response = $this->post( '/account/logout' );

		if ( $response ) {
			$this->sessionId = null;
			$this->sessionName = null;
			return true;
		}

		return false;
	}

	/**
	 * Get subscriber by ID
	 *
	 * @param string $subscriberId Subscriber ID
	 * @return stdClass|false Subscriber object or false on error
	 */
	public function subscriberGet( string $subscriberId ): stdClass|false {
		if ( empty( $subscriberId ) ) {
			$this->lastError = 'Invalid subscriber ID';
			return false;
		}

		$response = $this->get( '/subscriber/' . urlencode( $subscriberId ) );

		return $response->data ?? false;
	}

	/**
	 * Apply tag(s) to subscriber
	 *
	 * @param string $email Subscriber email
	 * @param array<int|string> $tagIds Tag ID(s)
	 * @return stdClass|false Response data or false on error
	 */
	public function tag( string $email, array $tagIds ): stdClass|false {
		if ( empty( $email ) || empty( $tagIds ) ) {
			$this->lastError = 'Invalid arguments';
			return false;
		}

		$response = $this->post(
			'/subscriber/tag',
			[
				'email'  => $email,
				'tagids' => $tagIds,
			]
		);

		return $response ? ( $response->data ?? (object) [] ) : false;
	}

	/**
	 * Get all tags
	 *
	 * @return array<int|string, string>|false Associative array [tag_id => tag_name] or false on error
	 */
	public function tagIndex(): array|false {
		$response = $this->get( '/tag' );

		if ( $response && isset( $response->data ) ) {
			return is_array( $response->data ) ? $response->data : [];
		}

		return false;
	}

	/**
	 * Get last error message
	 *
	 * @return string Error message
	 */
	public function getLastError(): string {
		return $this->lastError;
	}

	/**
	 * Execute GET request to KlickTipp API
	 *
	 * @param string $path API endpoint path
	 * @return stdClass|false Response object or false on error
	 */
	private function get( string $path ): stdClass|false {
		$url = $this->apiUrl . $path;
		$args = [
			'headers' => [
				'Accept' => 'application/vnd.php.serialized',
			],
			'timeout' => 20,
		];

		// Add session cookie if authenticated
		if ( $this->sessionName !== null && $this->sessionId !== null ) {
			$args['cookies'] = [
				$this->sessionName => $this->sessionId,
			];
		}

		$response = wp_remote_get( $url, $args );

		return $this->parseResponse( $response );
	}

	/**
	 * Execute POST request to KlickTipp API
	 *
	 * @param string $path API endpoint path
	 * @param array<string, mixed> $data Request data
	 * @param bool $useSession Whether to send session cookie
	 * @return stdClass|false Response object or false on error
	 */
	private function post( string $path, array $data = [], bool $useSession = true ): stdClass|false {
		$url = $this->apiUrl . $path;
		$args = [
			'body'    => $data,
			'headers' => [
				'Accept' => 'application/vnd.php.serialized',
			],
			'timeout' => 20,
		];

		// Add session cookie if authenticated
		if ( $useSession && $this->sessionName !== null && $this->sessionId !== null ) {
			$args['cookies'] = [
				$this->sessionName => $this->sessionId,
			];
		}

		$response = wp_remote_post( $url, $args );

		return $this->parseResponse( $response );
	}

	/**
	 * Parse WordPress HTTP API response
	 *
	 * @param array<mixed>|WP_Error $response WordPress HTTP response
	 * @return stdClass|false Parsed response or false on error
	 */
	private function parseResponse( array|WP_Error $response ): stdClass|false {
		if ( is_wp_error( $response ) ) {
			$this->lastError = $response->get_error_message();
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			$this->lastError = 'HTTP ' . $code;
			return false;
		}

		$result = new stdClass();
		$result->data = ! empty( $body ) ? @unserialize( $body ) : null;

		return $result;
	}
}
