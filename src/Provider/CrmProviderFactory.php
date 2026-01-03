<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Provider;

use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Util\HookUtil;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * CRM Provider Factory
 *
 * Creates and returns the appropriate CRM provider based on configuration.
 * Pro plugins can filter the provider class to add support for additional CRM systems.
 */
final class CrmProviderFactory {

	public function __construct(
		private readonly KlickTippProvider $klickTippProvider
	) {
	}

	/**
	 * Get the active CRM provider.
	 *
	 * Pro plugins can filter this to return a different provider implementation.
	 *
	 * @return CrmProviderInterface The CRM provider instance.
	 */
	public function getProvider(): CrmProviderInterface {
		$provider = HookUtil::applyFilter(
			HookFilter::CRM_PROVIDER,
			$this->klickTippProvider
		);

		// Ensure we always return a valid provider
		if ( ! $provider instanceof CrmProviderInterface ) {
			return $this->klickTippProvider;
		}

		return $provider;
	}
}
