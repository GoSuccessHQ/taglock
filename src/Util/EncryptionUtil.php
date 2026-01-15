<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Util;

use GoSuccess\TagLock\Exception\EncryptionException;

use function base64_decode;
use function base64_encode;
use function count;
use function defined;
use function explode;
use function hash;
use function hash_hmac;
use function openssl_cipher_iv_length;
use function openssl_decrypt;
use function openssl_encrypt;
use function random_bytes;
use function str_starts_with;
use function strlen;
use function substr;

defined( 'ABSPATH' ) || exit;

/**
 * Encryption Utility
 *
 * Provides encryption/decryption for sensitive data like passwords.
 * Uses WordPress AUTH_KEY as encryption key.
 */
final class EncryptionUtil {
	private const CIPHER = 'aes-256-gcm';

	/**
	 * Encrypt a string.
	 *
	 * @param string $data The data to encrypt.
	 * @return string The encrypted data (base64 encoded).
	 * @throws EncryptionException If encryption fails or AUTH_KEY is missing.
	 */
	public static function encrypt( string $data ): string {
		$key = self::getEncryptionKeyBytes();
		$iv  = random_bytes( openssl_cipher_iv_length( self::CIPHER ) );
		$tag = '';

		$ciphertext = openssl_encrypt( $data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag );
		if ( false === $ciphertext || '' === $tag ) {
			throw EncryptionException::encryptionFailed();
		}

		$payload = base64_encode( $iv ) . ':' . base64_encode( $tag ) . ':' . base64_encode( $ciphertext );

		return $payload;
	}

	/**
	 * Decrypt a string.
	 *
	 * @param string $data The encrypted data (base64 encoded).
	 * @return string|false The decrypted data or false on failure.
	 */
	public static function decrypt( string $data ): string|false {
		$parts = explode( ':', $data );

		if ( 3 !== count( $parts ) ) {
			return false;
		}

		$iv         = base64_decode( $parts[0], true );
		$tag        = base64_decode( $parts[1], true );
		$ciphertext = base64_decode( $parts[2], true );

		if ( false === $iv || false === $tag || false === $ciphertext ) {
			return false;
		}

		$key = self::getEncryptionKeyBytes();

		return openssl_decrypt( $ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag );
	}

	/**
	 * Get binary encryption key bytes for modern ciphers.
	 *
	 * @throws EncryptionException If AUTH_KEY is not defined.
	 */
	private static function getEncryptionKeyBytes(): string {
		if ( ! ( defined( 'AUTH_KEY' ) && is_string( AUTH_KEY ) && '' !== AUTH_KEY ) ) {
			throw EncryptionException::missingKey();
		}

		return hash_hmac( 'sha256', 'taglock', AUTH_KEY, true );
	}
}
