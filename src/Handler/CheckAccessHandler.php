<?php

/**
 * Check Access Handler
 *
 * Handles the POST request for access checking.
 */

declare(strict_types=1);

namespace GoSuccess\TagLock\Handler;

use GoSuccess\TagLock\Dto\ApiResponse;
use GoSuccess\TagLock\Enum\HttpMethod;
use GoSuccess\TagLock\Handler\AbstractApiHandler;
use GoSuccess\TagLock\Service\AccessValidationService;
use GoSuccess\TagLock\Service\LoggerService;
use WP_REST_Request;
use WP_REST_Response;

use function __;use function count;
use function ctype_digit;
use function defined;
use function is_array;
use function is_numeric;
use function is_string;
use function sanitize_text_field;
use function wp_verify_nonce;

defined( 'ABSPATH' ) || exit;

/**
 * Handler for processing access check requests.
 */
final class CheckAccessHandler extends AbstractApiHandler {

	public function __construct(
		private readonly AccessValidationService $accessValidation,
		private readonly LoggerService $logger
	) {}

	/**
	 * @inheritDoc
	 */
	public function getMethod(): HttpMethod {
		return HttpMethod::POST;
	}

	/**
	 * @inheritDoc
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		return $this->handle( $request );
	}

	/**
	 * @inheritDoc
	 */
	public function permissionCallback( WP_REST_Request $request ): bool {
		$nonce = $request->get_param( 'nonce' );

		if ( ! wp_verify_nonce( $nonce, $this->getNonceAction() ) ) {
			$this->logger->warning( __( 'Invalid nonce for access check', 'taglock' ) );
			return false;
		}

		return true;
	}

	/**
	 * Handle the access check request.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response The response.
	 */
	private function handle( WP_REST_Request $request ): WP_REST_Response {
		$subscriberId = (string) $request->get_param( 'subscriber_id' );
		$items        = $request->get_param( 'items' );

		$canBypass = $this->accessValidation->canUserBypass();

		// Validate subscriber ID
		$subscriberValidation = $this->validateSubscriberId( $subscriberId, $canBypass );
		if ( $subscriberValidation !== null ) {
			return $subscriberValidation;
		}

		// Validate items array
		if ( ! is_array( $items ) || $items === [] ) {
			return ApiResponse::error(
				__( 'Invalid request items.', 'taglock' ),
				'invalid_items',
				400
			);
		}

		$this->logger->info( __( 'Access check requested', 'taglock' ), [
			'subscriber_id' => $subscriberId,
			'count'         => count( $items ),
		] );

		// Reset auth state for new request
		$this->accessValidation->resetAuthState();

		$results = $this->processItems( $items, $subscriberId, $canBypass );

		// Check if CRM auth failed during processing
		if ( $results === null ) {
			return $this->buildCrmAuthFailureResponse();
		}

		return ApiResponse::success( [ 'results' => $results ] );
	}

	/**
	 * Validate the subscriber ID.
	 *
	 * @param string $subscriberId The subscriber ID.
	 * @param bool $canBypass Whether the user can bypass validation.
	 * @return WP_REST_Response|null Error response or null if valid.
	 */
	private function validateSubscriberId( string $subscriberId, bool $canBypass ): ?WP_REST_Response {
		// KlickTipp subscriber IDs are numeric
		$isValidId = $subscriberId !== '' && ctype_digit( $subscriberId );

		if ( ! $isValidId && ! $canBypass ) {
			$this->logger->warning( __( 'Invalid identifier', 'taglock' ), [ 'subscriber_id' => $subscriberId ] );
			return ApiResponse::error(
				__( 'Invalid identifier. Please use the link from your email.', 'taglock' ),
				'invalid_subscriber_id',
				400
			);
		}

		if ( $subscriberId !== '' && ! ctype_digit( $subscriberId ) ) {
			$this->logger->warning( __( 'Invalid identifier', 'taglock' ), [ 'subscriber_id' => $subscriberId ] );
			return ApiResponse::error(
				__( 'Invalid identifier. Please use the link from your email.', 'taglock' ),
				'invalid_subscriber_id',
				400
			);
		}

		return null;
	}

	/**
	 * Process all items in the request.
	 *
	 * @param array<int, mixed> $items The items to process.
	 * @param string $subscriberId The subscriber ID.
	 * @param bool $canBypass Whether the user can bypass.
	 * @return array<string, array<string, mixed>>|null Results or null on CRM auth failure.
	 */
	private function processItems( array $items, string $subscriberId, bool $canBypass ): ?array {
		$results = [];

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				$this->logger->warning( __( 'Access check item is invalid (expected array)', 'taglock' ), [
					'index' => $index,
				] );
				continue;
			}

			$contentId = isset( $item['content_id'] ) ? sanitize_text_field( (string) $item['content_id'] ) : '';
			if ( $contentId === '' ) {
				$this->logger->warning( __( 'Access check item is missing content_id', 'taglock' ), [
					'index' => $index,
				] );
				continue;
			}

			$sanitizedItem = [
				'rule_id'    => isset( $item['rule_id'] ) ? sanitize_text_field( (string) $item['rule_id'] ) : '',
				'content_id' => $contentId,
			];

			$result = $this->accessValidation->validateItem( $sanitizedItem, $subscriberId, $canBypass );

			// CRM auth failure - stop processing
			if ( $result === null && $this->accessValidation->isCrmAuthFailed() ) {
				return null;
			}

			if ( $result !== null ) {
				$results[ $contentId ] = $result;
			}
		}

		return $results;
	}

	/**
	 * Build the response for CRM authentication failure.
	 *
	 * @return WP_REST_Response The error response.
	 */
	private function buildCrmAuthFailureResponse(): WP_REST_Response {
		$data = [];
		$error = $this->accessValidation->getCrmAuthError();

		if ( $error !== null ) {
			$data['details'] = $error;
		}

		return ApiResponse::error(
			__( 'TagLock is currently unavailable (CRM connection failed or is not configured). Please contact the site administrator.', 'taglock' ),
			'authentication_failed',
			503,
			$data
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getArgs(): array {
		return [
			'subscriber_id' => [
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => static fn( $param ) => is_string( $param ) || is_numeric( $param ) || $param === null,
				'sanitize_callback' => 'sanitize_text_field',
			],
			'items'         => [
				'required'          => true,
				'type'              => 'array',
				'validate_callback' => static fn( $param ) => is_array( $param ) && ! empty( $param ),
				'sanitize_callback' => static fn( $value ) => is_array( $value ) ? $value : [],
			],
			'nonce'         => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getNonceAction(): string {
		return 'taglock_access_check';
	}
}
