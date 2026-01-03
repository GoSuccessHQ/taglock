<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Exception;

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
		return new self( 'Failed to encrypt data.' );
	}

	/**
	 * Create exception for decryption failure.
	 *
	 * @return self
	 */
	public static function decryptionFailed(): self {
		return new self( 'Failed to decrypt data.' );
	}

	/**
	 * Create exception for missing encryption key.
	 *
	 * @return self
	 */
	public static function missingKey(): self {
		return new self( 'AUTH_KEY is required for encryption/decryption.' );
	}
}
