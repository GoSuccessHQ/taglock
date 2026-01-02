<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Database;

use function add_action;

/**
 * Ensures TagLock custom tables exist even after plugin updates.
 */
final class RuleSchemaService {

	public function __construct(
		private readonly RuleTableInstaller $ruleTableInstaller
	) {
		add_action( 'plugins_loaded', [ $this, 'maybeInstall' ] );
	}

	public function maybeInstall(): void {
		$this->ruleTableInstaller->install();
	}
}
