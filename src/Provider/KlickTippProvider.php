<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Provider;

use GoSuccess\TagLock\Contract\CRMProviderInterface;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\EncryptionUtil;
use GoSuccess\TagLock\Util\HookUtil;
use KlicktippConnector;

use function get_option;

/**
 * KlickTipp Provider
 *
 * Implements CRM provider for KlickTipp using the PHP connector.
 * Handles authentication and tag checking via username/password session.
 */
final class KlickTippProvider implements CRMProviderInterface {

	private ?KlicktippConnector $connector = null;
	private bool $isAuthenticated = false;
	private string $lastError = '';

	public function __construct(
		private readonly LoggerService $logger
	) {
		$connectorPath = dirname( TAGLOCK_FILE ) . '/vendor/klicktipp/php-connector/klicktipp.api.inc';
		require_once $connectorPath;
	}

	/**
	 * Initialize the KlickTipp connector and authenticate.
	 */
	private function initialize(): void {
		if ( null !== $this->connector ) {
			return;
		}

		$this->connector = new KlicktippConnector();

		// Get credentials from WordPress options
		$username = get_option( 'taglock_klicktipp_username', '' );
		$encryptedPassword = get_option( 'taglock_klicktipp_password', '' );

		if ( empty( $username ) || empty( $encryptedPassword ) ) {
			$this->lastError = 'KlickTipp credentials not configured. Please configure them in Settings > TagLock.';
			$this->logger->error( 'KlickTipp credentials missing' );
			return;
		}

		// Decrypt password
		$password = EncryptionUtil::decrypt( $encryptedPassword );

		if ( false === $password || empty( $password ) ) {
			$this->lastError = 'Failed to decrypt KlickTipp password. Please re-save your credentials.';
			$this->logger->error( 'Failed to decrypt KlickTipp password' );
			return;
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'login' );

		$result = $this->connector->login( $username, $password );

		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'login', $result );

		if ( $result ) {
			$this->isAuthenticated = true;
			$this->logger->debug( 'KlickTipp authentication successful' );
		} else {
			$this->lastError = $this->connector->get_last_error() ?: 'Login failed. Please check your credentials.';
			$this->logger->error( 'KlickTipp authentication failed', [ 'error' => $this->lastError ] );
			HookUtil::doAction( HookAction::CRM_API_ERROR, 'login', $this->lastError );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isAuthenticated(): bool {
		if ( ! $this->isAuthenticated ) {
			$this->initialize();
		}

		return $this->isAuthenticated;
	}

	/**
	 * @inheritDoc
	 */
	public function hasTag( string $subscriberId, string $tagId ): bool {
		if ( ! $this->isAuthenticated() ) {
			$this->lastError = 'Not authenticated';
			return false;
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'subscriber_get', $subscriberId );

		// Get subscriber data
		$subscriber = $this->connector->subscriber_get( $subscriberId );

		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'subscriber_get', $subscriber );

		if ( ! $subscriber ) {
			$this->lastError = $this->connector->get_last_error() ?: 'Subscriber not found';
			$this->logger->warning( 'Subscriber not found', [
				'subscriber_id' => $subscriberId,
				'error'         => $this->lastError,
			] );
			HookUtil::doAction( HookAction::CRM_API_ERROR, 'subscriber_get', $this->lastError );
			return false;
		}

		// Check if subscriber has the tag
		// KlickTipp returns tags as comma-separated string in 'tag' field
		if ( isset( $subscriber->tag ) ) {
			$tags = explode( ',', $subscriber->tag );
			$tags = array_map( 'trim', $tags );

			$hasTag = in_array( $tagId, $tags, true );

			$this->logger->debug( 'Tag check completed', [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
				'has_tag'       => $hasTag,
				'subscriber_tags' => $tags,
			] );

			return $hasTag;
		}

		$this->logger->debug( 'Subscriber has no tags', [ 'subscriber_id' => $subscriberId ] );
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function applyTag( string $subscriberId, string $tagId ): bool {
		if ( ! $this->isAuthenticated() ) {
			$this->lastError = 'Not authenticated';
			return false;
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'tag', $subscriberId, $tagId );

		// Get subscriber email first (required by KlickTipp API)
		$subscriber = $this->connector->subscriber_get( $subscriberId );

		if ( ! $subscriber || empty( $subscriber->email ) ) {
			$this->lastError = $this->connector->get_last_error() ?: 'Subscriber not found';
			$this->logger->error( 'Cannot apply tag: Subscriber not found', [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
			] );
			return false;
		}

		// Apply tag using email
		$result = $this->connector->tag( $subscriber->email, [ $tagId ] );

		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'tag', $result );

		if ( $result ) {
			$this->logger->info( 'Tag applied successfully', [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
			] );
			return true;
		}

		$this->lastError = $this->connector->get_last_error() ?: 'Failed to apply tag';
		$this->logger->error( 'Failed to apply tag', [
			'subscriber_id' => $subscriberId,
			'tag_id'        => $tagId,
			'error'         => $this->lastError,
		] );
		HookUtil::doAction( HookAction::CRM_API_ERROR, 'tag', $this->lastError );

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getLastError(): string {
		return $this->lastError;
	}

	/**
	 * Destructor - logout when object is destroyed
	 */
	public function __destruct() {
		if ( $this->isAuthenticated && null !== $this->connector ) {
			$this->connector->logout();
			$this->logger->debug( 'KlickTipp session logged out' );
		}
	}
}
