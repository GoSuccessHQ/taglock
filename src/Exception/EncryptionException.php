<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Exception;

use function __;use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when encryption or decryption fails.
 */
final class EncryptionException extends TagLockException {

	/**
	 * Create exception for encryption failure.
	 *
	 * @return self
	 */
	public static function encryptionFailed(): self {
		return new self( __( 'Failed to encrypt data.', 'taglock' ) );
	}

	/**
	 * Create exception for decryption failure.
	 *
	 * @return self
	 */
	public static function decryptionFailed(): self {
		return new self( __( 'Failed to decrypt data.', 'taglock' ) );
	}

	/**
	 * Create exception for missing encryption key.
	 *
	 * @return self
	 */
	public static function missingKey(): self {
		return new self( __( 'AUTH_KEY is required for encryption/decryption.', 'taglock' ) );
	}
}
