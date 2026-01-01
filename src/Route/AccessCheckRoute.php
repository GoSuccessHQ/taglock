<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Contract\CRMProviderInterface;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\HookUtil;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function get_transient;
use function register_rest_route;
use function rest_ensure_response;
use function wp_verify_nonce;

/**
 * Access Check Route
 *
 * REST API endpoint to verify subscriber access based on KlickTipp tags.
 * Endpoint: POST /wp-json/taglock/v1/check-access
 */
final class AccessCheckRoute implements ApiRouteInterface {

	private const string NAMESPACE = 'taglock/v1';
	private const string ROUTE     = '/check-access';

	public function __construct(
		private readonly CRMProviderInterface $crmProvider,
		private readonly LoggerService $logger
	) {}

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handleRequest' ],
				'permission_callback' => [ $this, 'checkPermissions' ],
				'args'                => [
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
				],
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getNamespace(): string {
		return self::NAMESPACE;
	}

	/**
	 * @inheritDoc
	 */
	public function getRoute(): string {
		return self::ROUTE;
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
	 * @return WP_REST_Response|WP_Error The REST response.
	 */
	public function handleRequest( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$subscriberId = $request->get_param( 'subscriber_id' );
		$tagId        = $request->get_param( 'tag' );
		$contentId    = $request->get_param( 'content_id' );

		// Additional validation
		if ( empty( $subscriberId ) || ! ctype_digit( $subscriberId ) ) {
			$this->logger->warning( __( 'Invalid subscriber ID', 'taglock' ), [ 'subscriber_id' => $subscriberId ] );
			return new WP_Error(
				'invalid_subscriber_id',
				__( 'Invalid subscriber ID. Please use the link from your email.', 'taglock' ),
				[ 'status' => 400 ]
			);
		}

		if ( empty( $tagId ) || ! ctype_digit( $tagId ) ) {
			$this->logger->warning( __( 'Invalid tag ID', 'taglock' ), [ 'tag_id' => $tagId ] );
			return new WP_Error(
				'invalid_tag_id',
				__( 'Invalid tag configuration.', 'taglock' ),
				[ 'status' => 400 ]
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

			return new WP_Error(
				'authentication_failed',
				__( 'Service temporarily unavailable. Please try again later or contact support.', 'taglock' ),
				[ 'status' => 503 ]
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

				return new WP_Error(
					'content_not_found',
					__( 'This content has expired. Please refresh the page and try again.', 'taglock' ),
					[ 'status' => 410 ]
				);
			}

			// Apply Pro filter for additional content processing
			$content = HookUtil::applyFilter( HookFilter::PROTECTED_CONTENT, $content, $subscriberId, $tagId );

			HookUtil::doAction( HookAction::ACCESS_GRANTED, $subscriberId, $tagId, $content );

			$this->logger->info( __( 'Access granted', 'taglock' ), [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
			] );

			$response = [
				'success' => true,
				'content' => $content,
				'message' => __( 'Access granted', 'taglock' ),
			];

			// Allow Pro to modify response
			$response = HookUtil::applyFilter( HookFilter::ACCESS_GRANTED_RESPONSE, $response, $subscriberId, $tagId );

			return rest_ensure_response( $response );
		}

		// Access denied
		HookUtil::doAction( HookAction::ACCESS_DENIED, $subscriberId, $tagId );

		$this->logger->info( __( 'Access denied', 'taglock' ), [
			'subscriber_id' => $subscriberId,
			'tag_id'        => $tagId,
		] );

		$response = [
			'success' => false,
			'message' => __( 'You do not have access to this content. Please contact support if you believe this is an error.', 'taglock' ),
		];

		// CRITICAL: Pro filter for redirect URL and other features
		// Pro version can add 'redirect_url' to response
		$response = HookUtil::applyFilter( HookFilter::ACCESS_DENIED_RESPONSE, $response, $subscriberId, $tagId );

		return rest_ensure_response( $response );
	}
}
