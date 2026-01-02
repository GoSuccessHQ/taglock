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
use GoSuccess\TagLock\Service\Rule\RuleRepository;
use GoSuccess\TagLock\Util\HookUtil;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function array_filter;
use function array_map;
use function array_values;
use function ctype_digit;
use function current_user_can;
use function do_shortcode;
use function function_exists;
use function get_permalink;
use function get_transient;
use function is_array;
use function is_numeric;
use function is_string;
use function sanitize_text_field;
use function wp_kses_post;
use function wp_verify_nonce;

/**
 * Access Check Route
 *
 * REST API endpoint to verify access based on CRM tags.
 * Endpoint: POST /wp-json/taglock/v1/check-access
 */
#[AutoconfigureTag( 'taglock.api_route' )]
final class AccessCheckRoute implements ApiRouteInterface {

	private const string ROUTE     = '/check-access';

	public function __construct(
		private readonly CRMProviderInterface $crmProvider,
		private readonly RuleRepository $ruleRepository,
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
					'subscriber_id'  => [
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
		$subscriberId = (string) $request->get_param( 'subscriber_id' );
		$items        = $request->get_param( 'items' );

		$canBypassForSomeRule = function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );

		// Currently KlickTipp subscriber IDs are numeric; keep strict validation.
		if ( ( $subscriberId === '' || ! ctype_digit( $subscriberId ) ) && ! $canBypassForSomeRule ) {
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

		if ( ! is_array( $items ) || $items === [] ) {
			return ApiResponse::error(
				__( 'Invalid request items.', 'taglock' ),
				'invalid_items',
				400
			);
		}

		$this->logger->info( __( 'Access check requested', 'taglock' ), [
			'subscriber_id' => $subscriberId,
			'count'        => count( $items ),
		] );

		$crmAuthChecked   = false;
		$crmAuthenticated = false;

		$results = [];

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) ) {
				$this->logger->warning( __( 'Access check item is invalid (expected array)', 'taglock' ), [
					'index' => $index,
				] );
				continue;
			}

			$ruleId = isset( $item['rule_id'] ) ? sanitize_text_field( (string) $item['rule_id'] ) : '';
			$contentId = isset( $item['content_id'] ) ? sanitize_text_field( (string) $item['content_id'] ) : '';

			if ( $contentId === '' ) {
				$this->logger->warning( __( 'Access check item is missing content_id', 'taglock' ), [
					'index' => $index,
				] );
				continue;
			}

			if ( $ruleId === '' || ! ctype_digit( $ruleId ) ) {
				$results[ $contentId ] = [
					'success' => false,
					'code'    => 'invalid_rule_id',
					'status'  => 400,
					'message' => __( 'Invalid rule configuration.', 'taglock' ),
				];
				continue;
			}

			$rule = $this->ruleRepository->getRule( (int) $ruleId );
			if ( $rule === null || empty( $rule['is_active'] ) ) {
				$results[ $contentId ] = [
					'success' => false,
					'code'    => 'rule_not_found',
					'status'  => 404,
					'message' => __( 'This TagLock configuration is not available.', 'taglock' ),
				];
				continue;
			}

			$requiredTagIds = isset( $rule['required_tag_ids'] ) && is_array( $rule['required_tag_ids'] ) ? $rule['required_tag_ids'] : [];
			$requiredTagIds = array_values( array_filter( array_map( 'intval', $requiredTagIds ), static fn( int $v ) => $v > 0 ) );
			if ( $requiredTagIds === [] ) {
				$results[ $contentId ] = [
					'success' => false,
					'code'    => 'invalid_rule',
					'status'  => 400,
					'message' => __( 'This TagLock configuration is invalid.', 'taglock' ),
				];
				continue;
			}

			$adminBypassEnabled = false;
			if ( $canBypassForSomeRule ) {
				$adminBypassEnabled = ! empty( $rule['admin_bypass_enabled'] );
			}

			if ( ! $adminBypassEnabled && ( $subscriberId === '' || ! ctype_digit( $subscriberId ) ) ) {
				$results[ $contentId ] = [
					'success' => false,
					'code'    => 'invalid_subscriber_id',
					'status'  => 400,
					'message' => __( 'Invalid identifier. Please use the link from your email.', 'taglock' ),
				];
				continue;
			}

			HookUtil::doAction( HookAction::BEFORE_ACCESS_CHECK, $subscriberId, (int) $ruleId, $requiredTagIds );

			$hasAccess = false;
			if ( $adminBypassEnabled ) {
				$hasAccess = true;
			} else {
				if ( ! $crmAuthChecked ) {
					$crmAuthChecked   = true;
					$crmAuthenticated = $this->crmProvider->isAuthenticated();
					if ( ! $crmAuthenticated ) {
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
				}

				$accessMode = isset( $rule['access_mode'] ) ? (string) $rule['access_mode'] : 'tag_any';
				if ( $accessMode === 'tag_all' ) {
					$hasAccess = true;
					foreach ( $requiredTagIds as $tagId ) {
						if ( ! $this->crmProvider->hasTag( $subscriberId, (string) $tagId ) ) {
							$hasAccess = false;
							break;
						}
					}
				} else {
					foreach ( $requiredTagIds as $tagId ) {
						if ( $this->crmProvider->hasTag( $subscriberId, (string) $tagId ) ) {
							$hasAccess = true;
							break;
						}
					}
				}
			}

			HookUtil::doAction( HookAction::AFTER_ACCESS_CHECK, $subscriberId, (int) $ruleId, $requiredTagIds, $hasAccess );

			if ( $hasAccess ) {
				$content = get_transient( $contentId );

				if ( $content === false ) {
					$results[ $contentId ] = [
						'success' => false,
						'code'    => 'content_not_found',
						'status'  => 410,
						'message' => __( 'This content has expired. Please refresh the page and try again.', 'taglock' ),
					];
					continue;
				}

				$content = HookUtil::applyFilter( HookFilter::PROTECTED_CONTENT, $content, $subscriberId, (int) $ruleId, $requiredTagIds );

				HookUtil::doAction( HookAction::ACCESS_GRANTED, $subscriberId, (int) $ruleId, $content );

				if ( ! $adminBypassEnabled && ! empty( $rule['engagement_tagging_enabled'] ) ) {
					$engagementTagIds = isset( $rule['engagement_tag_ids'] ) && is_array( $rule['engagement_tag_ids'] ) ? $rule['engagement_tag_ids'] : [];
					$engagementTagIds = array_values( array_filter( array_map( 'intval', $engagementTagIds ), static fn( int $v ) => $v > 0 ) );
					foreach ( $engagementTagIds as $engagementTagId ) {
						$this->crmProvider->applyTag( $subscriberId, (string) $engagementTagId );
					}
				}

				$data = [
					'content' => $content,
					'message' => __( 'Access granted', 'taglock' ),
				];

				$data = HookUtil::applyFilter( HookFilter::ACCESS_GRANTED_RESPONSE, $data, $subscriberId, (int) $ruleId, $requiredTagIds );

				$results[ $contentId ] = [
					'success' => true,
					'content' => $data['content'] ?? $content,
					'message' => $data['message'] ?? __( 'Access granted', 'taglock' ),
				];

				continue;
			}

			HookUtil::doAction( HookAction::ACCESS_DENIED, $subscriberId, (int) $ruleId, $requiredTagIds );

			$data = [
				'success' => false,
				'message' => __( 'You do not have access to this content. Please contact support if you believe this is an error.', 'taglock' ),
			];

			$denyMode = isset( $rule['deny_mode'] ) ? (string) $rule['deny_mode'] : 'message';
			$redirectUrl = null;
			$teaserHtml = null;
			$denyMessage = isset( $rule['deny_message'] ) && is_string( $rule['deny_message'] ) ? $rule['deny_message'] : '';
			$denyMessage = $denyMessage !== '' ? $denyMessage : $data['message'];

			if ( $denyMode === 'redirect' ) {
				$postId = isset( $rule['redirect_post_id'] ) ? (int) $rule['redirect_post_id'] : 0;
				if ( $postId > 0 ) {
					$redirectUrl = get_permalink( $postId );
				}
			}

			if ( $denyMode === 'teaser' ) {
				$rawTeaser = isset( $rule['teaser_html'] ) && is_string( $rule['teaser_html'] ) ? $rule['teaser_html'] : '';
				$rawTeaser = $rawTeaser !== '' ? do_shortcode( $rawTeaser ) : '';
				$rawTeaser = $rawTeaser !== '' ? wp_kses_post( $rawTeaser ) : '';
				$teaserHtml = $rawTeaser !== '' ? $rawTeaser : null;
			}

			$data = HookUtil::applyFilter( HookFilter::ACCESS_DENIED_RESPONSE, $data, $subscriberId, (int) $ruleId, $requiredTagIds, $rule );

			$results[ $contentId ] = [
				'success'      => false,
				'status'       => 403,
				'message'      => $denyMessage,
				'redirect_url' => $redirectUrl,
				'teaser_html'  => $teaserHtml,
			];
		}

		return ApiResponse::success( [ 'results' => $results ] );
	}
}
