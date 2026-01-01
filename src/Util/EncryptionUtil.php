<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Util;

use function base64_decode;
use function base64_encode;
use function defined;
use function hash_hmac;
use function mb_strlen;
use function mb_substr;
use function openssl_decrypt;
use function openssl_encrypt;

/**
 * Encryption Utility
 *
 * Provides encryption/decryption for sensitive data like passwords.
 * Uses WordPress AUTH_KEY as encryption key.
 */
final class EncryptionUtil {

	private const CIPHER = 'aes-256-cbc';

	/**
	 * Encrypt a string.
	 *
	 * @param string $data The data to encrypt.
	 * @return string The encrypted data (base64 encoded).
	 */
	public static function encrypt( string $data ): string {
		$key = self::getEncryptionKey();
		$iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::CIPHER ) );

		$encrypted = openssl_encrypt( $data, self::CIPHER, $key, 0, $iv );

		// Prepend IV to encrypted data
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt a string.
	 *
	 * @param string $data The encrypted data (base64 encoded).
	 * @return string|false The decrypted data or false on failure.
	 */
	public static function decrypt( string $data ): string|false {
		$key  = self::getEncryptionKey();
		$data = base64_decode( $data, true );

		if ( false === $data ) {
			return false;
		}

		$ivLength = openssl_cipher_iv_length( self::CIPHER );
		$iv       = mb_substr( $data, 0, $ivLength, '8bit' );
		$encrypted = mb_substr( $data, $ivLength, null, '8bit' );

		return openssl_decrypt( $encrypted, self::CIPHER, $key, 0, $iv );
	}

	/**
	 * Get encryption key from WordPress constants.
	 *
	 * @return string The encryption key.
	 */
	private static function getEncryptionKey(): string {
		if ( defined( 'AUTH_KEY' ) && AUTH_KEY ) {
			return hash_hmac( 'sha256', 'taglock', AUTH_KEY );
		}

		// Fallback (not recommended for production)
		return hash( 'sha256', 'taglock_fallback_key' );
	}
}
