<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Util;

use function array_filter;
use function array_map;
use function array_values;
use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Array Utilities
 *
 * Provides helper methods for common array operations.
 */
final class ArrayUtil {

	/**
	 * Normalize an array to contain only positive integers.
	 *
	 * Converts all values to integers, filters out non-positive values,
	 * and re-indexes the array.
	 *
	 * @param array<int, mixed> $values The input array.
	 * @return array<int, int> Array of positive integers.
	 */
	public static function normalizePositiveIntegers( array $values ): array {
		$integers = array_map( 'intval', $values );
		$positive = array_filter( $integers, static fn( int $v ): bool => $v > 0 );

		return array_values( $positive );
	}

	/**
	 * Normalize an array to contain only non-negative integers.
	 *
	 * Converts all values to integers, filters out negative values,
	 * and re-indexes the array.
	 *
	 * @param array<int, mixed> $values The input array.
	 * @return array<int, int> Array of non-negative integers.
	 */
	public static function normalizeNonNegativeIntegers( array $values ): array {
		$integers = array_map( 'intval', $values );
		$nonNegative = array_filter( $integers, static fn( int $v ): bool => $v >= 0 );

		return array_values( $nonNegative );
	}

	/**
	 * Normalize an array to contain only non-empty strings.
	 *
	 * Filters out empty strings and re-indexes the array.
	 *
	 * @param array<int, mixed> $values The input array.
	 * @return array<int, string> Array of non-empty strings.
	 */
	public static function normalizeNonEmptyStrings( array $values ): array {
		$strings = array_filter( $values, static fn( mixed $v ): bool => is_string( $v ) && $v !== '' );

		return array_values( $strings );
	}
}
