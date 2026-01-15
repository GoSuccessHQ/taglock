<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Dto\ApiMethodHandler;
use GoSuccess\TagLock\Dto\ApiResponse;
use GoSuccess\TagLock\Enum\HttpMethod;
use GoSuccess\TagLock\Repository\RuleRepository;
use GoSuccess\TagLock\Service\LoggerService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function __;
use function apply_filters;
use function current_user_can;
use function defined;
use function is_array;
use function is_numeric;
use function is_string;
use function sanitize_text_field;

defined( 'ABSPATH' ) || exit;

/**
 * Rules collection endpoint.
 *
 * GET  /wp-json/taglock/v1/rules
 * POST /wp-json/taglock/v1/rules
 */
#[AutoconfigureTag( 'taglock.api_route' )]
final class RulesRoute implements ApiRouteInterface {

	private const ROUTE = '/rules';

	public function __construct(
		private readonly RuleRepository $ruleRepository,
		private readonly LoggerService $logger
	) {}

	public function getRoute(): string {
		return self::ROUTE;
	}

	public function getMethodHandlers(): array {
		return [
			new ApiMethodHandler(
				HttpMethod::GET,
				fn( WP_REST_Request $request ) => $this->listRules( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request ),
				[
					'search'   => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'page'     => [
						'required'          => false,
						'type'              => 'integer',
						'validate_callback' => static fn( $param ) => is_numeric( $param ),
					],
					'per_page' => [
						'required'          => false,
						'type'              => 'integer',
						'validate_callback' => static fn( $param ) => is_numeric( $param ),
					],
				]
			),
			new ApiMethodHandler(
				HttpMethod::POST,
				fn( WP_REST_Request $request ) => $this->createRule( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request ),
				[
					'provider' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'name' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'is_active' => [
						'required'          => false,
						'type'              => 'boolean',
					],
					'access_mode' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'deny_mode' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'deny_message' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => static fn( $v ) => is_string( $v ) ? $v : '',
					],
					'teaser_html' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => static fn( $v ) => is_string( $v ) ? $v : '',
					],
					'redirect_post_id' => [
						'required'          => false,
						'type'              => [ 'integer', 'null' ],
						'sanitize_callback' => static fn( $v ) => is_numeric( $v ) ? (int) $v : null,
					],
					'redirect_post_type' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'required_tag_ids' => [
						'required'          => false,
						'type'              => 'array',
						'sanitize_callback' => static fn( $v ) => is_array( $v ) ? $v : [],
					],
					'engagement_tagging_enabled' => [
						'required'          => false,
						'type'              => 'boolean',
					],
					'engagement_tag_ids' => [
						'required'          => false,
						'type'              => 'array',
						'sanitize_callback' => static fn( $v ) => is_array( $v ) ? $v : [],
					],
					'admin_bypass_enabled' => [
						'required'          => false,
						'type'              => 'boolean',
					],
				]
			),
		];
	}

	public function checkPermissions( WP_REST_Request $request ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->logger->warning( __( 'Unauthorized rules access attempt', 'taglock' ) );
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to manage TagLock rules', 'taglock' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	private function listRules( WP_REST_Request $request ): WP_REST_Response {
		$search = (string) $request->get_param( 'search' );
		$page = (int) ( $request->get_param( 'page' ) ?: 1 );
		$perPage = (int) ( $request->get_param( 'per_page' ) ?: 20 );

		$result = $this->ruleRepository->listRules( $search, $page, $perPage );
		$items = $result['items'];
		$total = (int) $result['total'];

		return ApiResponse::paginatedSuccess( $items, $page, $perPage, $total );
	}

	private function createRule( WP_REST_Request $request ): WP_REST_Response {
		$payload = $request->get_json_params();
		$payload = is_array( $payload ) ? $payload : [];

		$ruleId = $this->ruleRepository->createRule( $payload );
		if ( $ruleId <= 0 ) {
			return ApiResponse::error( __( 'Failed to create rule.', 'taglock' ), 'create_failed', 500 );
		}

		$rule = $this->ruleRepository->getRule( $ruleId );
		return ApiResponse::success( $rule, __( 'Rule created.', 'taglock' ), 201 );
	}
}
