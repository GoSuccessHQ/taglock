<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Provider;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Service\KlickTippApiService;
use GoSuccess\TagLock\Service\LoggerService;
use GoSuccess\TagLock\Util\EncryptionUtil;
use GoSuccess\TagLock\Util\HookUtil;
use Throwable;

use function __;
use function defined;
use function get_class;
use function get_option;

defined( 'ABSPATH' ) || exit;

/**
 * KlickTipp Provider
 *
 * Implements CRM provider for KlickTipp using internal API service.
 * Handles authentication and tag checking via username/password session.
 */
final class KlickTippProvider implements CrmProviderInterface {

	private ?KlickTippApiService $api = null;
	private bool $isAuthenticated = false;
	private string $lastError = '';
	/** @var array<string, array<int, string>> */
	private array $subscriberTagsCache = [];

	public function __construct(
		private readonly PluginConfiguration $config,
		private readonly LoggerService $logger
	) {}

	/**
	 * Initialize the KlickTipp API and authenticate.
	 */
	private function initialize(): void {
		if ( $this->api !== null ) {
			return;
		}

		try {
			$this->api = new KlickTippApiService();
		} catch ( Throwable $exception ) {
			$this->lastError = __( 'KlickTipp API could not be initialized. Please contact support.', 'taglock' );
			$this->logger->error( __( 'Failed to initialize KlickTipp API', 'taglock' ), [
				'exception' => get_class( $exception ),
				'message'   => $exception->getMessage(),
			] );
			return;
		}

		// Get credentials from WordPress options
		$username = get_option( $this->config->klicktippUsernameOption, '' );
		$encryptedPassword = get_option( $this->config->klicktippPasswordOption, '' );

		if ( empty( $username ) || empty( $encryptedPassword ) ) {
			$this->lastError = __( 'KlickTipp credentials not configured. Please configure them in Settings > TagLock.', 'taglock' );
			$this->logger->error( __( 'KlickTipp credentials missing', 'taglock' ) );
			return;
		}

		// Decrypt password
		try {
			$password = EncryptionUtil::decrypt( $encryptedPassword );
		} catch ( Throwable $exception ) {
			$this->lastError = __( 'KlickTipp password could not be decrypted. Please re-save your credentials.', 'taglock' );
			$this->logger->error( __( 'Failed to decrypt KlickTipp password', 'taglock' ), [
				'exception' => get_class( $exception ),
				'message'   => $exception->getMessage(),
			] );
			return;
		}

		if ( $password === false || empty( $password ) ) {
			$this->lastError = __( 'Failed to decrypt KlickTipp password. Please re-save your credentials.', 'taglock' );
			$this->logger->error( __( 'Failed to decrypt KlickTipp password', 'taglock' ) );
			return;
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'login' );

		$result = $this->api->login( $username, $password );

		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'login', $result );

		if ( $result ) {
			$this->isAuthenticated = true;
			$this->logger->debug( __( 'KlickTipp authentication successful', 'taglock' ) );
		} else {
			$this->lastError = $this->api->getLastError() ?: __( 'Login failed. Please check your credentials.', 'taglock' );
			$this->logger->error( __( 'KlickTipp authentication failed', 'taglock' ), [ 'error' => $this->lastError ] );
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
	public function testCredentials( string $username, string $password ): bool {
		try {
			$api = new KlickTippApiService();
		} catch ( Throwable $exception ) {
			$this->lastError = __( 'KlickTipp API could not be initialized.', 'taglock' );
			$this->logger->error( __( 'Failed to initialize KlickTipp API for credential test', 'taglock' ), [
				'exception' => get_class( $exception ),
				'message'   => $exception->getMessage(),
			] );
			return false;
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'login_test' );

		$result = $api->login( $username, $password );

		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'login_test', $result );

		if ( ! $result ) {
			$this->lastError = $api->getLastError() ?: __( 'Login failed. Please check your credentials.', 'taglock' );
			$this->logger->warning( __( 'KlickTipp credential test failed', 'taglock' ), [ 'error' => $this->lastError ] );
			return false;
		}

		// Logout the test connection
		$api->logout();

		$this->logger->debug( __( 'KlickTipp credential test successful', 'taglock' ) );
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function hasTag( string $subscriberId, string $tagId ): bool {
		if ( isset( $this->subscriberTagsCache[ $subscriberId ] ) ) {
			$this->lastError = '';
			return in_array( $tagId, $this->subscriberTagsCache[ $subscriberId ], true );
		}

		if ( ! $this->isAuthenticated() ) {
			$this->lastError = __( 'Not authenticated', 'taglock' );
			return false;
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'subscriber_get', $subscriberId );

		// Get subscriber data
		$subscriber = $this->api->subscriberGet( $subscriberId );

		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'subscriber_get', $subscriber );

		if ( ! $subscriber ) {
			$this->lastError = $this->api->getLastError() ?: __( 'Subscriber not found', 'taglock' );
			$this->logger->warning( __( 'Subscriber not found', 'taglock' ), [
				'subscriber_id' => $subscriberId,
				'error'         => $this->lastError,
			] );
			HookUtil::doAction( HookAction::CRM_API_ERROR, 'subscriber_get', $this->lastError );
			return false;
		}

		// Check if subscriber has the tag
		// KlickTipp returns tags as comma-separated string in 'tag' field
		$tags = [];
		if ( isset( $subscriber->tag ) ) {
			$tags = explode( ',', $subscriber->tag );
			$tags = array_map( 'trim', $tags );
		}

		$this->subscriberTagsCache[ $subscriberId ] = $tags;
		$this->lastError = '';

		if ( [] !== $tags ) {
			$hasTag = in_array( $tagId, $tags, true );

			$this->logger->debug( __( 'Tag check completed', 'taglock' ), [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
				'has_tag'       => $hasTag,
				'subscriber_tags' => $tags,
			] );

			return $hasTag;
		}

		$this->logger->debug( __( 'Subscriber has no tags', 'taglock' ), [ 'subscriber_id' => $subscriberId ] );
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function applyTag( string $subscriberId, string $tagId ): bool {
		if ( ! $this->isAuthenticated() ) {
			$this->lastError = __( 'Not authenticated', 'taglock' );
			return false;
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'tag', $subscriberId, $tagId );

		// Get subscriber email first (required by KlickTipp API)
		$subscriber = $this->api->subscriberGet( $subscriberId );

		if ( ! $subscriber || empty( $subscriber->email ) ) {
			$this->lastError = $this->api->getLastError() ?: __( 'Subscriber not found', 'taglock' );
			$this->logger->error( __( 'Cannot apply tag: Subscriber not found', 'taglock' ), [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
			] );
			return false;
		}

		// Apply tag using email
		$result = $this->api->tag( $subscriber->email, [ $tagId ] );

		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'tag', $result );

		if ( $result ) {
			$this->logger->info( __( 'Tag applied successfully', 'taglock' ), [
				'subscriber_id' => $subscriberId,
				'tag_id'        => $tagId,
			] );
			return true;
		}

		$this->lastError = $this->api->getLastError() ?: __( 'Failed to apply tag', 'taglock' );
		$this->logger->error( __( 'Failed to apply tag', 'taglock' ), [
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
	public function getTags(): array {
		if ( ! $this->isAuthenticated() ) {
			$this->lastError = __( 'Not authenticated', 'taglock' );
			return [];
		}

		HookUtil::doAction( HookAction::BEFORE_CRM_API_CALL, 'tag_index' );
		$result = $this->api->tagIndex();
		HookUtil::doAction( HookAction::AFTER_CRM_API_CALL, 'tag_index', $result );

		if ( $result === false || $result === null ) {
			$this->lastError = $this->api->getLastError() ?: __( 'Failed to load tags', 'taglock' );
			$this->logger->error( __( 'Failed to load KlickTipp tags', 'taglock' ), [ 'error' => $this->lastError ] );
			HookUtil::doAction( HookAction::CRM_API_ERROR, 'tag_index', $this->lastError );
			return [];
		}

		$tags = [];
		foreach ( $result as $id => $name ) {
			if ( ! is_string( $name ) ) {
				continue;
			}
			$tags[ (string) $id ] = $name;
		}

		$this->lastError = '';
		return $tags;
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
		if ( $this->isAuthenticated && $this->api !== null ) {
			$this->api->logout();
			$this->logger->debug( __( 'KlickTipp session logged out', 'taglock' ) );
		}
	}
}
