<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Dto\ApiMethodHandler;
use GoSuccess\TagLock\Dto\ApiResponse;
use GoSuccess\TagLock\Enum\HttpMethod;
use GoSuccess\TagLock\Service\LoggerService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

use function current_user_can;
use function is_string;

/**
 * Tags endpoint.
 *
 * GET /wp-json/taglock/v1/tags
 */
#[AutoconfigureTag( 'taglock.api_route' )]
final class TagsRoute implements ApiRouteInterface {

	private const string ROUTE = '/tags';

	public function __construct(
		private readonly CrmProviderInterface $provider,
		private readonly LoggerService $logger
	) {}

	public function getRoute(): string {
		return self::ROUTE;
	}

	public function getMethodHandlers(): array {
		return [
			new ApiMethodHandler(
				HttpMethod::GET,
				fn( WP_REST_Request $request ) => $this->listTags( $request ),
				fn( WP_REST_Request $request ) => $this->checkPermissions( $request )
			),
		];
	}

	public function checkPermissions( WP_REST_Request $request ): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view tags', 'taglock' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	public function listTags( WP_REST_Request $request ): WP_REST_Response {
		if ( ! $this->provider->isAuthenticated() ) {
			return ApiResponse::error(
				$this->provider->getLastError() ?: __( 'Not authenticated', 'taglock' ),
				'not_authenticated',
				401
			);
		}

		$tags = $this->provider->getTags();

		if ( $tags === [] && $this->provider->getLastError() !== '' ) {
			$this->logger->error( __( 'Failed to load tags', 'taglock' ), [
				'error' => $this->provider->getLastError(),
			] );
			return ApiResponse::error(
				$this->provider->getLastError(),
				'tags_load_failed',
				500
			);
		}

		$items = [];
		foreach ( $tags as $id => $name ) {
			if ( ! is_string( $name ) ) {
				continue;
			}
			$items[] = [
				'id'   => (string) $id,
				'name' => $name,
			];
		}

		usort(
			$items,
			static fn( array $a, array $b ) => strcmp( $a['name'] ?? '', $b['name'] ?? '' )
		);

		return ApiResponse::success( [ 'items' => $items ] );
	}
}
