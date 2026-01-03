<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Exception;

use Exception;
use Throwable;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Base exception for TagLock plugin.
 *
 * All custom TagLock exceptions should extend this class.
 */
abstract class TagLockException extends Exception {

	/**
	 * Create a new TagLock exception.
	 *
	 * @param string $message The exception message.
	 * @param int $code The exception code.
	 * @param \Throwable|null $previous The previous exception.
	 */
	public function __construct( string $message = '', int $code = 0, ?\Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
