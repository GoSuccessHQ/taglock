<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Contract\CRMProviderInterface;
use GoSuccess\TagLock\DTO\ApiMethodHandler;
use GoSuccess\TagLock\DTO\ApiResponse;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Enum\HttpMethod;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\HookUtil;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function get_transient;
use function wp_verify_nonce;

/**
 * Access Check Route
 *
 * REST API endpoint to verify subscriber access based on KlickTipp tags.
 * Endpoint: POST /wp-json/taglock/v1/check-access
 */
#[AutoconfigureTag( 'taglock.api_route' )]
final class AccessCheckRoute implements ApiRouteInterface {

	private const string ROUTE     = '/check-access';

	public function __construct(
		private readonly CRMProviderInterface $crmProvider,
		private readonly LoggerService $logger
	) {}

	/**
	 * @inheritDoc
	 */
	public function getRoute(): string {
		return self::ROUTE;
	}

	/**
	 * @inheritDoc
	 */
	public function getMethodHandlers(): array {
		return [
			new ApiMethodHandler(
				HttpMethod::POST,
				fn( WP_REST_Request $request ) => $this->handleRequest( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request ),
				[
					'subscriber_id' => [
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => fn( $param ) => ! empty( $param ),
						'sanitize_callback' => 'sanitize_text_field',
					],
					'tag'           => [
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => fn( $param ) => ! empty( $param ),
						'sanitize_callback' => 'sanitize_text_field',
					],
					'content_id'    => [
						'required'          => true,
						'type'              => 'string',
						'validate_callback' => fn( $param ) => ! empty( $param ),
						'sanitize_callback' => 'sanitize_text_field',
					],
					'nonce'         => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				]
			),
		];
	}

	/**
	 * Check permissions for the endpoint.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function checkPermissions( WP_REST_Request $request ): bool|WP_Error {
		$nonce = $request->get_param( 'nonce' );

		if ( ! wp_verify_nonce( $nonce, 'taglock_access_check' ) ) {
			$this->logger->warning( __( 'Invalid nonce for access check', 'taglock' ) );
			return new WP_Error(
				'invalid_nonce',
				__( 'Security verification failed', 'taglock' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Handle the access check request.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response The API response.
	 */
	public function handleRequest( WP_REST_Request $request ): WP_REST_Response {
		$subscriberId = $request->get_param( 'subscriber_id' );
		$tagId        = $request->get_param( 'tag' );
		$contentId    = $request->get_param( 'content_id' );

		// Additional validation
		if ( empty( $subscriberId ) || ! ctype_digit( $subscriberId ) ) {
			$this->logger->warning( __( 'Invalid subscriber ID', 'taglock' ), [ 'subscriber_id' => $subscriberId ] );
			return ApiResponse::error(
				__( 'Invalid subscriber ID. Please use the link from your email.', 'taglock' ),
				'invalid_subscriber_id',
				400
			);
		}

		if ( empty( $tagId ) || ! ctype_digit( $tagId ) ) {
			$this->logger->warning( __( 'Invalid tag ID', 'taglock' ), [ 'tag_id' => $tagId ] );
			return ApiResponse::error(
				__( 'Invalid tag configuration.', 'taglock' ),
				'invalid_tag_id',
				400
			);
		}

		HookUtil::doAction( HookAction::BEFORE_ACCESS_CHECK, $subscriberId, $tagId );

		$this->logger->info( __( 'Access check requested', 'taglock' ), [
			'subscriber_id' => $subscriberId,
			'tag_id'        => $tagId,
		] );

		// Check if CRM provider is authenticated
		if ( ! $this->crmProvider->isAuthenticated() ) {
			$error = $this->crmProvider->getLastError();
			$this->logger->error( __( 'CRM authentication failed', 'taglock' ), [ 'error' => $error ] );

			HookUtil::doAction( HookAction::API_EXCEPTION_CAUGHT, 'authentication_failed', $error );

			$data = [];
			if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) && ! empty( $error ) ) {
				$data['details'] = $error;
			}

			return ApiResponse::error(
				__( 'TagLock is currently unavailable (CRM connection failed or is not configured). Please contact the site administrator.', 'taglock' ),
				'authentication_failed',
				503,
				$data
			);
		}

		// Check if subscriber has the required tag
		$hasAccess = $this->crmProvider->hasTag( $subscriberId, $tagId );

		HookUtil::doAction( HookAction::AFTER_ACCESS_CHECK, $subscriberId, $tagId, $hasAccess );

		if ( $hasAccess ) {
			// Access granted - retrieve protected content
			$content = get_transient( $contentId );

			if ( false === $content ) {
				$this->logger->error( __( 'Content not found or expired', 'taglock' ), [ 'content_id' => $contentId ] );

				return ApiResponse::error(
					__( 'This content has expired. Please refresh the page and try again.', 'taglock' ),
					'content_not_found',
					410
				);
			}

			// Apply Pro filter for additional content processing
			$content = HookUtil::applyFilter( HookFilter::PROTECTED_CONTENT, $content, $subscriberId, $tagId );

			HookUtil::doAction( HookAction::ACCESS_GRANTED, $subscriberId, $tagId, $content );

			$this->logger->info( __( 'Access granted', 'taglock' ), [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
			] );

			$data = [
				'content' => $content,
				'message' => __( 'Access granted', 'taglock' ),
			];

			// Allow Pro to modify response
			$data = HookUtil::applyFilter( HookFilter::ACCESS_GRANTED_RESPONSE, $data, $subscriberId, $tagId );

			return ApiResponse::success( $data );
		}

		// Access denied
		HookUtil::doAction( HookAction::ACCESS_DENIED, $subscriberId, $tagId );

		$this->logger->info( __( 'Access denied', 'taglock' ), [
			'subscriber_id' => $subscriberId,
			'tag_id'        => $tagId,
		] );

		$data = [
			'success' => false,
			'message' => __( 'You do not have access to this content. Please contact support if you believe this is an error.', 'taglock' ),
		];

		// CRITICAL: Pro filter for redirect URL and other features
		// Pro version can add 'redirect_url' to response
		$data = HookUtil::applyFilter( HookFilter::ACCESS_DENIED_RESPONSE, $data, $subscriberId, $tagId );

		return ApiResponse::custom( $data, 403 );
	}
}
