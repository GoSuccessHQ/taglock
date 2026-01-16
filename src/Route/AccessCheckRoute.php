<?php

/**
 * Access Check Route
 *
 * REST API endpoint to verify access based on CRM tags.
 * Endpoint: POST /wp-json/taglock/v1/check-access
 */

declare(strict_types=1);

namespace GoSuccess\TagLock\Route;

use GoSuccess\TagLock\Contract\ApiRouteInterface;
use GoSuccess\TagLock\Handler\CheckAccessHandler;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Route definition for access checking endpoint.
 */
final class AccessCheckRoute implements ApiRouteInterface {

	private const ROUTE = '/check-access';

	public function __construct(
		private readonly CheckAccessHandler $checkAccessHandler
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
		return [ $this->checkAccessHandler ];
	}
}
