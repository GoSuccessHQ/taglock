<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Util;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;

use function apply_filters;
use function do_action;

/**
 * WordPress Hook Utilities
 *
 * Type-safe wrapper functions for WordPress hooks using enums.
 */
final class HookUtil {

	/**
	 * Execute a WordPress action hook with enum support.
	 *
	 * @param HookAction $action The action hook to execute.
	 * @param mixed      ...$args Optional arguments to pass to the action.
	 */
	public static function doAction( HookAction $action, mixed ...$args ): void {
		do_action( $action->value, ...$args );
	}

	/**
	 * Apply a WordPress filter hook with enum support.
	 *
	 * @template T
	 * @param HookFilter $filter The filter hook to apply.
	 * @param T          $value The value to filter.
	 * @param mixed      ...$args Optional additional arguments.
	 * @return T The filtered value.
	 */
	public static function applyFilter( HookFilter $filter, mixed $value, mixed ...$args ): mixed {
		return apply_filters( $filter->value, $value, ...$args );
	}
}
