<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Configuration;

/**
 * Plugin configuration values.
 */
final class PluginConfiguration {

	public function __construct(
		public readonly string $apiNamespace = 'taglock/v1'
	) {
	}
}
