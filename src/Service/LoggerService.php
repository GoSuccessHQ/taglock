<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function defined;
use function is_dir;
use function wp_mkdir_p;

/**
 * Logger Service
 *
 * Provides structured logging using Monolog for debugging and monitoring.
 */
final class LoggerService {

	private LoggerInterface $logger;

	/**
	 * Constructor
	 *
	 * Initializes the logger with file and debug handlers.
	 */
	public function __construct() {
		$this->logger = $this->createLogger();
	}

	/**
	 * Create and configure the Monolog logger.
	 *
	 * @return LoggerInterface The configured logger instance.
	 */
	private function createLogger(): LoggerInterface {
		$logger = new Logger( 'taglock' );

		// Get log directory
		$logDir  = WP_CONTENT_DIR . '/uploads/taglock/logs';
		$logFile = "{$logDir}/taglock.log";

		// Create log directory if it doesn't exist
		if ( ! is_dir( $logDir ) ) {
			wp_mkdir_p( $logDir );
			
			// Create index.php to prevent directory listing
			file_put_contents( 
				"{$logDir}/index.php", 
				"<?php\n// Silence is golden.\n" 
			);
			
			// Create .htaccess to deny direct access
			file_put_contents(
				"{$logDir}/.htaccess",
				"# Deny access to log files\n" .
				"<Files *.log>\n" .
				"    Order allow,deny\n" .
				"    Deny from all\n" .
				"</Files>\n"
			);
		}

		// Determine log level based on WP_DEBUG
		$isDebug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$level   = $isDebug ? Logger::DEBUG : Logger::WARNING;

		// Add file handler
		$logger->pushHandler( new StreamHandler( $logFile, $level ) );

		return $logger;
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function debug( string $message, array $context = [] ): void {
		$this->logger->debug( $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function info( string $message, array $context = [] ): void {
		$this->logger->info( $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function warning( string $message, array $context = [] ): void {
		$this->logger->warning( $message, $context );
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message The log message.
	 * @param array<string, mixed> $context Additional context data.
	 */
	public function error( string $message, array $context = [] ): void {
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
