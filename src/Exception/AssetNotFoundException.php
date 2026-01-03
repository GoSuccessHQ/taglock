<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Exception;

use function defined;
use function sprintf;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when an asset file is not found.
 */
final class AssetNotFoundException extends TagLockException {

	/**
	 * Create exception for missing asset file.
	 *
	 * @param string $filename The missing filename.
	 * @return self
	 */
	public static function forFile( string $filename ): self {
		return new self(
			sprintf( 'TagLock asset file is missing: %s', $filename )
		);
	}

	/**
	 * Create exception for missing asset metadata file.
	 *
	 * @param string $filename The missing metadata filename.
	 * @return self
	 */
	public static function forMetadata( string $filename ): self {
		return new self(
			sprintf( 'TagLock asset metadata file is missing: %s', $filename )
		);
	}
}
