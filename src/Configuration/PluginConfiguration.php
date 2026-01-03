<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Configuration;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin configuration values.
 */
final class PluginConfiguration {

	public function __construct(
		public readonly string $apiNamespace = 'taglock/v1',
		public readonly string $proLandingUrl = 'https://gosuccess.io/taglock'
	) {
	}
}
