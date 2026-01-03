<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Exception;

use function __;use function defined;
use function sprintf;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when asset data format is invalid.
 */
final class InvalidAssetFormatException extends TagLockException {

	/**
	 * Create exception for invalid asset metadata format.
	 *
	 * @param string $filename The filename with invalid format.
	 * @return self
	 */
	public static function forMetadata( string $filename ): self {
		return new self(
			sprintf(
				/* translators: %s: metadata filename */
				__( 'TagLock asset metadata file is invalid: %s', 'taglock' ),
				$filename
			)
		);
	}
}
