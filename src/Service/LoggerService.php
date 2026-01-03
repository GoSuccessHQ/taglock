<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use DateTimeZone;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use WP_Filesystem_Base;

use function defined;
use function function_exists;
use function get_option;
use function is_numeric;
use function wp_normalize_path;

defined( 'ABSPATH' ) || exit;

/**
 * Logger Service
 *
 * Provides structured logging using Monolog for debugging and monitoring.
 */
final class LoggerService {

	private LoggerInterface $logger;
	private bool $initialized = false;

	/**
	 * Constructor
	 *
	 * Initializes the logger with file and debug handlers.
	 */
	public function __construct() {
		$this->logger = $this->createLogger();
	}

	/**
	 * Ensure log directory exists (lazy initialization).
	 * Called before first log write to ensure WordPress functions are available.
	 *
	 * @return void
	 */
	private function ensureLogDirectoryExists(): void {
		if ( $this->initialized ) {
			return;
		}

		$filesystem = $this->getFilesystem();
		$logDir = wp_normalize_path( WP_CONTENT_DIR . '/uploads/taglock/logs' );
		$logFile = "{$logDir}/taglock.log";

		if ( ! $filesystem->is_dir( $logDir ) ) {
			$filesystem->mkdir( $logDir );
		}

		$indexFile = "{$logDir}/index.php";
		if ( ! $filesystem->exists( $indexFile ) ) {
			$filesystem->put_contents( $indexFile, "<?php\n// Silence is golden.\n" );
		}

		$htaccessFile = "{$logDir}/.htaccess";
		if ( ! $filesystem->exists( $htaccessFile ) ) {
			$filesystem->put_contents(
				$htaccessFile,
				"# Deny access to log files\n" .
				"<Files *.log>\n" .
				"    Order allow,deny\n" .
				"    Deny from all\n" .
				"</Files>\n"
			);
		}

		if ( ! $filesystem->exists( $logFile ) ) {
			$filesystem->put_contents( $logFile, '' );
		}

		$this->initialized = true;
	}

	private function getFilesystem(): WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		\WP_Filesystem();

		if ( ! isset( $wp_filesystem ) || ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			throw new RuntimeException( 'TagLock filesystem is not available.' );
		}

		return $wp_filesystem;
	}

	/**
	 * Create and configure the Monolog logger.
	 *
	 * @return LoggerInterface The configured logger instance.
	 */
	private function createLogger(): LoggerInterface {
		$this->ensureLogDirectoryExists();

		$logger = new Logger( 'taglock' );
		$timezone = $this->getWordPressTimezone();

		// Ensure Monolog timestamps match the WordPress timezone (helps debugging).
		$logger->pushProcessor(
			static function ( LogRecord $record ) use ( $timezone ): LogRecord {
				return $record->with( datetime: $record->datetime->setTimezone( $timezone ) );
			}
		);

		// Get log directory
		$logDir  = wp_normalize_path( WP_CONTENT_DIR . '/uploads/taglock/logs' );
		$logFile = "{$logDir}/taglock.log";

		// Determine log level based on WP_DEBUG
		$isDebug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$level   = $isDebug ? Logger::DEBUG : Logger::WARNING;

		// Add file handler
		$handler = new StreamHandler( $logFile, $level );
		$handler->setFormatter( new LineFormatter( null, null, true, true ) );
		$logger->pushHandler( $handler );

		return $logger;
	}

	private function getWordPressTimezone(): DateTimeZone {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}

		$timezoneString = function_exists( 'get_option' ) ? (string) get_option( 'timezone_string', '' ) : '';
		if ( '' !== $timezoneString ) {
			try {
				return new DateTimeZone( $timezoneString );
			} catch ( \Throwable ) {
				// Fall through to offset/UTC.
			}
		}

		$gmtOffset = function_exists( 'get_option' ) ? get_option( 'gmt_offset', 0 ) : 0;
		if ( is_numeric( $gmtOffset ) ) {
			$offsetHours = (float) $gmtOffset;
			$sign = $offsetHours < 0 ? '-' : '+';
			$abs = abs( $offsetHours );
			$hours = (int) floor( $abs );
			$minutes = (int) round( ( $abs - $hours ) * 60 );
			$offset = sprintf( '%s%02d:%02d', $sign, $hours, $minutes );

			try {
				return new DateTimeZone( $offset );
			} catch ( \Throwable ) {
				// Fall through to UTC.
			}
		}

		return new DateTimeZone( 'UTC' );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function debug( string $message, array $context = [] ): void {
		$this->ensureLogDirectoryExists();
		$this->logger->debug( $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function info( string $message, array $context = [] ): void {
		$this->ensureLogDirectoryExists();
		$this->logger->info( $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function warning( string $message, array $context = [] ): void {
		$this->ensureLogDirectoryExists();
		$this->logger->warning( $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function error( string $message, array $context = [] ): void {
		$this->ensureLogDirectoryExists();
		$this->logger->error( $message, $context );
	}

	/**
	 * Get the underlying PSR-3 logger instance.
	 *
	 * @return LoggerInterface The logger instance.
	 */
	public function getLogger(): LoggerInterface {
		return $this->logger;
	}
}
