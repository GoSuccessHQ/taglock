<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Enum;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Supported CRM providers.
 *
 * Stored in the database to associate rules with a provider.
 */
enum CrmProvider: string {
	case KLICKTIPP = 'klicktipp';

	public static function default(): self {
		return self::KLICKTIPP;
	}
}
