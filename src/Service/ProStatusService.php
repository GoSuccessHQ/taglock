<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use function apply_filters;
use function array_keys;
use function defined;
use function function_exists;
use function get_plugins;
use function is_plugin_active;

defined( 'ABSPATH' ) || exit;

/**
 * Determines whether the TagLock Pro addon is installed/active.
 */
final class ProStatusService {
	/**
	 * @var string|null
	 */
	private static ?string $detectedBasename = null;

	/**
	 * @return bool True when a Pro plugin is installed (active or inactive).
	 */
	public function isProInstalled(): bool {
		return $this->getProPluginBasename() !== null;
	}

	/**
	 * @return bool True when a Pro plugin is active.
	 */
	public function isProActive(): bool {
		$basename = $this->getProPluginBasename();
		if ( $basename === null ) {
			return false;
		}

		$this->ensurePluginFunctionsLoaded();
		return function_exists( 'is_plugin_active' ) && is_plugin_active( $basename );
	}

	/**
	 * @return string|null Plugin basename like "taglock-pro/taglock-pro.php".
	 */
	public function getProPluginBasename(): ?string {
		if ( self::$detectedBasename !== null ) {
			return self::$detectedBasename;
		}

		$this->ensurePluginFunctionsLoaded();
		if ( ! function_exists( 'get_plugins' ) ) {
			self::$detectedBasename = null;
			return null;
		}

		$plugins = get_plugins();
		$keys = array_keys( $plugins );

		$candidates = [
			'taglock-pro/taglock-pro.php',
			'taglock-pro/taglock.php',
			'taglock-pro.php',
		];

		if ( function_exists( 'apply_filters' ) ) {
			/** @var array<int, string> $filtered */
			$filtered = apply_filters( 'taglock_pro_plugin_basenames', $candidates );
			$candidates = is_array( $filtered ) ? $filtered : $candidates;
		}

		foreach ( $candidates as $candidate ) {
			if ( is_string( $candidate ) && $candidate !== '' && isset( $plugins[ $candidate ] ) ) {
				self::$detectedBasename = $candidate;
				return self::$detectedBasename;
			}
		}

		foreach ( $keys as $basename ) {
			if ( ! is_string( $basename ) ) {
				continue;
			}

			if ( str_contains( $basename, 'taglock-pro/' ) || preg_match( '#(^|/)taglock-pro\\.php$#i', $basename ) ) {
				self::$detectedBasename = $basename;
				return self::$detectedBasename;
			}
		}

		foreach ( $plugins as $basename => $data ) {
			if ( ! is_string( $basename ) || ! is_array( $data ) ) {
				continue;
			}

			$name = isset( $data['Name'] ) && is_string( $data['Name'] ) ? $data['Name'] : '';
			if ( $name !== '' && stripos( $name, 'TagLock Pro' ) !== false ) {
				self::$detectedBasename = $basename;
				return self::$detectedBasename;
			}
		}

		self::$detectedBasename = null;
		return null;
	}

	private function ensurePluginFunctionsLoaded(): void {
		if ( function_exists( 'get_plugins' ) && function_exists( 'is_plugin_active' ) ) {
			return;
		}

		if ( defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}
}
