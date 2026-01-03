<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Contract;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * CRM Provider Interface
 *
 * Defines the contract for CRM providers (e.g., KlickTipp) to check subscriber tags.
 */
interface CrmProviderInterface {

	/**
	 * Check if the provider is authenticated and ready to use.
	 *
	 * @return bool True if authenticated, false otherwise.
	 */
	public function isAuthenticated(): bool;

	/**
	 * Check if a subscriber has a specific tag.
	 *
	 * @param string $subscriberId The subscriber identifier (subscriber_id) used to identify the subscriber in the CRM.
	 * @param string $tagId The tag ID to check.
	 * @return bool True if the subscriber has the tag, false otherwise.
	 */
	public function hasTag( string $subscriberId, string $tagId ): bool;

	/**
	 * Apply a tag to a subscriber.
	 *
	 * @param string $subscriberId The subscriber identifier (subscriber_id) used to identify the subscriber in the CRM.
	 * @param string $tagId The tag ID to apply.
	 * @return bool True if the tag was applied successfully, false otherwise.
	 */
	public function applyTag( string $subscriberId, string $tagId ): bool;

	/**
	 * Get available tags for the authenticated account.
	 *
	 * @return array<string, string> Associative array of tag id => tag name.
	 */
	public function getTags(): array;

	/**
	 * Get the last error message from the CRM provider.
	 *
	 * @return string The last error message, or empty string if no error.
	 */
	public function getLastError(): string;

	/**
	 * Test if the given credentials are valid without saving them.
	 *
	 * @param string $username The username to test.
	 * @param string $password The password to test.
	 * @return bool True if credentials are valid, false otherwise.
	 */
	public function testCredentials( string $username, string $password ): bool;
}
