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

use function __;use function current_user_can;
use function defined;
use function is_array;
use function is_numeric;
use function is_string;
use function sanitize_text_field;

defined( 'ABSPATH' ) || exit;

/**
 * Rule item endpoint.
 *
 * GET    /wp-json/taglock/v1/rules/{id}
 * PUT    /wp-json/taglock/v1/rules/{id}
 * DELETE /wp-json/taglock/v1/rules/{id}
 */
#[AutoconfigureTag( 'taglock.api_route' )]
final class RuleRoute implements ApiRouteInterface {

	private const ROUTE = '/rules/(?P<id>\\d+)';

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
				fn( WP_REST_Request $request ) => $this->getRule( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request )
			),
			new ApiMethodHandler(
				HttpMethod::PUT,
				fn( WP_REST_Request $request ) => $this->updateRule( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request ),
				[
					'provider' => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				]
			),
			new ApiMethodHandler(
				HttpMethod::DELETE,
				fn( WP_REST_Request $request ) => $this->deleteRule( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request )
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

	private function getRule( WP_REST_Request $request ): WP_REST_Response {
		$id = (int) $request->get_param( 'id' );
		$rule = $this->ruleRepository->getRule( $id );
		if ( $rule === null ) {
			return ApiResponse::error( __( 'Rule not found.', 'taglock' ), 'not_found', 404 );
		}

		return ApiResponse::success( $rule );
	}

	private function updateRule( WP_REST_Request $request ): WP_REST_Response {
		$id = (int) $request->get_param( 'id' );
		$payload = $request->get_json_params();
		$payload = is_array( $payload ) ? $payload : [];

		$existing = $this->ruleRepository->getRule( $id );
		if ( $existing === null ) {
			return ApiResponse::error( __( 'Rule not found.', 'taglock' ), 'not_found', 404 );
		}

		$ok = $this->ruleRepository->updateRule( $id, $payload );
		if ( ! $ok ) {
			return ApiResponse::error( __( 'Failed to update rule.', 'taglock' ), 'update_failed', 500 );
		}

		$rule = $this->ruleRepository->getRule( $id );
		return ApiResponse::success( $rule, __( 'Rule updated.', 'taglock' ) );
	}

	private function deleteRule( WP_REST_Request $request ): WP_REST_Response {
		$id = (int) $request->get_param( 'id' );
		$existing = $this->ruleRepository->getRule( $id );
		if ( $existing === null ) {
			return ApiResponse::error( __( 'Rule not found.', 'taglock' ), 'not_found', 404 );
		}

		$ok = $this->ruleRepository->deleteRule( $id );
		if ( ! $ok ) {
			return ApiResponse::error( __( 'Failed to delete rule.', 'taglock' ), 'delete_failed', 500 );
		}

		return ApiResponse::success( null, __( 'Rule deleted.', 'taglock' ) );
	}
}
