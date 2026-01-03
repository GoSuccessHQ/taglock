<?php

/**
 * Access Validation Service
 *
 * Handles the business logic for validating access based on CRM tags.
 */

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Contract\CrmProviderInterface;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Repository\RuleRepository;
use GoSuccess\TagLock\Util\ArrayUtil;
use GoSuccess\TagLock\Util\HookUtil;

use function __;use function ctype_digit;
use function current_user_can;
use function defined;
use function do_shortcode;
use function function_exists;
use function get_permalink;
use function get_transient;
use function is_array;
use function is_string;
use function wp_kses_post;

defined( 'ABSPATH' ) || exit;

/**
 * Service for validating access to protected content based on CRM tags.
 */
final class AccessValidationService {

	private bool $crmAuthChecked = false;
	private bool $crmAuthenticated = false;

	public function __construct(
		private readonly CrmProviderInterface $crmProvider,
		private readonly RuleRepository $ruleRepository,
		private readonly LoggerService $logger
	) {}

	/**
	 * Reset the CRM authentication state for a new request cycle.
	 */
	public function resetAuthState(): void {
		$this->crmAuthChecked   = false;
		$this->crmAuthenticated = false;
	}

	/**
	 * Validate a single access check item.
	 *
	 * @param array<string, mixed> $item The item to validate.
	 * @param string $subscriberId The subscriber ID.
	 * @param bool $canBypass Whether the user can bypass for admin rules.
	 * @return array<string, mixed>|null The result or null if validation should continue.
	 */
	public function validateItem( array $item, string $subscriberId, bool $canBypass ): ?array {
		$ruleId    = isset( $item['rule_id'] ) ? (string) $item['rule_id'] : '';
		$contentId = isset( $item['content_id'] ) ? (string) $item['content_id'] : '';

		if ( $contentId === '' ) {
			return null; // Skip invalid items
		}

		if ( $ruleId === '' || ! ctype_digit( $ruleId ) ) {
			return $this->errorResult( 'invalid_rule_id', __( 'Invalid rule configuration.', 'taglock' ), 400 );
		}

		$rule = $this->ruleRepository->getRule( (int) $ruleId );
		if ( $rule === null || empty( $rule['is_active'] ) ) {
			return $this->errorResult( 'rule_not_found', __( 'This TagLock configuration is not available.', 'taglock' ), 404 );
		}

		$requiredTagIds = $this->getRequiredTagIds( $rule );
		if ( $requiredTagIds === [] ) {
			return $this->errorResult( 'invalid_rule', __( 'This TagLock configuration is invalid.', 'taglock' ), 400 );
		}

		$adminBypassEnabled = $canBypass && ! empty( $rule['admin_bypass_enabled'] );

		if ( ! $adminBypassEnabled && ( $subscriberId === '' || ! ctype_digit( $subscriberId ) ) ) {
			return $this->errorResult( 'invalid_subscriber_id', __( 'Invalid identifier. Please use the link from your email.', 'taglock' ), 400 );
		}

		return $this->checkAccess( $subscriberId, (int) $ruleId, $rule, $requiredTagIds, $adminBypassEnabled, $contentId );
	}

	/**
	 * Check if the subscriber has access to the protected content.
	 *
	 * @param string $subscriberId The subscriber ID.
	 * @param int $ruleId The rule ID.
	 * @param array<string, mixed> $rule The rule configuration.
	 * @param int[] $requiredTagIds The required tag IDs.
	 * @param bool $adminBypassEnabled Whether admin bypass is enabled.
	 * @param string $contentId The content ID for transient lookup.
	 * @return array<string, mixed>|null The result or null on CRM auth failure.
	 */
	private function checkAccess(
		string $subscriberId,
		int $ruleId,
		array $rule,
		array $requiredTagIds,
		bool $adminBypassEnabled,
		string $contentId
	): ?array {
		HookUtil::doAction( HookAction::BEFORE_ACCESS_CHECK, $subscriberId, $ruleId, $requiredTagIds );

		$hasAccess = $this->determineAccess( $subscriberId, $rule, $requiredTagIds, $adminBypassEnabled );

		if ( $hasAccess === null ) {
			return null; // CRM auth failure - handled by caller
		}

		HookUtil::doAction( HookAction::AFTER_ACCESS_CHECK, $subscriberId, $ruleId, $requiredTagIds, $hasAccess );

		if ( $hasAccess ) {
			return $this->buildGrantedResponse( $subscriberId, $ruleId, $rule, $requiredTagIds, $adminBypassEnabled, $contentId );
		}

		return $this->buildDeniedResponse( $subscriberId, $ruleId, $rule, $requiredTagIds );
	}

	/**
	 * Determine if the subscriber has access based on tags.
	 *
	 * @param string $subscriberId The subscriber ID.
	 * @param array<string, mixed> $rule The rule configuration.
	 * @param int[] $requiredTagIds The required tag IDs.
	 * @param bool $adminBypassEnabled Whether admin bypass is enabled.
	 * @return bool|null True if access granted, false if denied, null on CRM auth failure.
	 */
	private function determineAccess( string $subscriberId, array $rule, array $requiredTagIds, bool $adminBypassEnabled ): ?bool {
		if ( $adminBypassEnabled ) {
			return true;
		}

		if ( ! $this->ensureCrmAuthenticated() ) {
			return null;
		}

		$accessMode = isset( $rule['access_mode'] ) ? (string) $rule['access_mode'] : 'tag_any';

		if ( $accessMode === 'tag_all' ) {
			foreach ( $requiredTagIds as $tagId ) {
				if ( ! $this->crmProvider->hasTag( $subscriberId, (string) $tagId ) ) {
					return false;
				}
			}
			return true;
		}

		// Default: tag_any
		foreach ( $requiredTagIds as $tagId ) {
			if ( $this->crmProvider->hasTag( $subscriberId, (string) $tagId ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Ensure CRM is authenticated (lazy check).
	 *
	 * @return bool True if authenticated, false otherwise.
	 */
	private function ensureCrmAuthenticated(): bool {
		if ( ! $this->crmAuthChecked ) {
			$this->crmAuthChecked   = true;
			$this->crmAuthenticated = $this->crmProvider->isAuthenticated();

			if ( ! $this->crmAuthenticated ) {
				$error = $this->crmProvider->getLastError();
				$this->logger->error( __( 'CRM authentication failed', 'taglock' ), [ 'error' => $error ] );
				HookUtil::doAction( HookAction::API_EXCEPTION_CAUGHT, 'authentication_failed', $error );
			}
		}

		return $this->crmAuthenticated;
	}

	/**
	 * Get the last CRM authentication error for admin users.
	 *
	 * @return string|null The error message or null.
	 */
	public function getCrmAuthError(): ?string {
		if ( ! $this->crmAuthenticated && function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			$error = $this->crmProvider->getLastError();
			return ! empty( $error ) ? $error : null;
		}
		return null;
	}

	/**
	 * Check if CRM authentication failed.
	 *
	 * @return bool True if CRM auth was checked and failed.
	 */
	public function isCrmAuthFailed(): bool {
		return $this->crmAuthChecked && ! $this->crmAuthenticated;
	}

	/**
	 * Build the response for granted access.
	 *
	 * @param string $subscriberId The subscriber ID.
	 * @param int $ruleId The rule ID.
	 * @param array<string, mixed> $rule The rule configuration.
	 * @param int[] $requiredTagIds The required tag IDs.
	 * @param bool $adminBypassEnabled Whether admin bypass is enabled.
	 * @param string $contentId The content ID.
	 * @return array<string, mixed> The response data.
	 */
	private function buildGrantedResponse(
		string $subscriberId,
		int $ruleId,
		array $rule,
		array $requiredTagIds,
		bool $adminBypassEnabled,
		string $contentId
	): array {
		$content = get_transient( $contentId );

		if ( $content === false ) {
			return $this->errorResult( 'content_not_found', __( 'This content has expired. Please refresh the page and try again.', 'taglock' ), 410 );
		}

		$content = HookUtil::applyFilter( HookFilter::PROTECTED_CONTENT, $content, $subscriberId, $ruleId, $requiredTagIds );

		HookUtil::doAction( HookAction::ACCESS_GRANTED, $subscriberId, $ruleId, $content );

		// Apply engagement tags
		if ( ! $adminBypassEnabled && ! empty( $rule['engagement_tagging_enabled'] ) ) {
			$this->applyEngagementTags( $subscriberId, $rule );
		}

		$data = [
			'content' => $content,
			'message' => __( 'Access granted', 'taglock' ),
		];

		$data = HookUtil::applyFilter( HookFilter::ACCESS_GRANTED_RESPONSE, $data, $subscriberId, $ruleId, $requiredTagIds );

		return [
			'success' => true,
			'content' => $data['content'] ?? $content,
			'message' => $data['message'] ?? __( 'Access granted', 'taglock' ),
		];
	}

	/**
	 * Build the response for denied access.
	 *
	 * @param string $subscriberId The subscriber ID.
	 * @param int $ruleId The rule ID.
	 * @param array<string, mixed> $rule The rule configuration.
	 * @param int[] $requiredTagIds The required tag IDs.
	 * @return array<string, mixed> The response data.
	 */
	private function buildDeniedResponse( string $subscriberId, int $ruleId, array $rule, array $requiredTagIds ): array {
		HookUtil::doAction( HookAction::ACCESS_DENIED, $subscriberId, $ruleId, $requiredTagIds );

		$defaultMessage = __( 'You do not have access to this content. Please contact support if you believe this is an error.', 'taglock' );
		$denyMode       = isset( $rule['deny_mode'] ) ? (string) $rule['deny_mode'] : 'message';
		$denyMessage    = isset( $rule['deny_message'] ) && is_string( $rule['deny_message'] ) && $rule['deny_message'] !== ''
			? $rule['deny_message']
			: $defaultMessage;

		$redirectUrl = null;
		$teaserHtml  = null;

		if ( $denyMode === 'redirect' ) {
			$postId = isset( $rule['redirect_post_id'] ) ? (int) $rule['redirect_post_id'] : 0;
			if ( $postId > 0 ) {
				$redirectUrl = get_permalink( $postId );
			}
		}

		if ( $denyMode === 'teaser' ) {
			$rawTeaser = isset( $rule['teaser_html'] ) && is_string( $rule['teaser_html'] ) ? $rule['teaser_html'] : '';
			$rawTeaser = $rawTeaser !== '' ? do_shortcode( $rawTeaser ) : '';
			$rawTeaser = $rawTeaser !== '' ? wp_kses_post( $rawTeaser ) : '';
			$teaserHtml = $rawTeaser !== '' ? $rawTeaser : null;
		}

		$data = [
			'success' => false,
			'message' => $denyMessage,
		];

		$data = HookUtil::applyFilter( HookFilter::ACCESS_DENIED_RESPONSE, $data, $subscriberId, $ruleId, $requiredTagIds, $rule );

		return [
			'success'      => false,
			'status'       => 403,
			'message'      => $denyMessage,
			'redirect_url' => $redirectUrl,
			'teaser_html'  => $teaserHtml,
		];
	}

	/**
	 * Apply engagement tags to the subscriber.
	 *
	 * @param string $subscriberId The subscriber ID.
	 * @param array<string, mixed> $rule The rule configuration.
	 */
	private function applyEngagementTags( string $subscriberId, array $rule ): void {
		$engagementTagIds = isset( $rule['engagement_tag_ids'] ) && is_array( $rule['engagement_tag_ids'] )
			? $rule['engagement_tag_ids']
			: [];
		$engagementTagIds = ArrayUtil::normalizePositiveIntegers( $engagementTagIds );

		foreach ( $engagementTagIds as $engagementTagId ) {
			$this->crmProvider->applyTag( $subscriberId, (string) $engagementTagId );
		}
	}

	/**
	 * Get the required tag IDs from a rule.
	 *
	 * @param array<string, mixed> $rule The rule configuration.
	 * @return int[] The normalized tag IDs.
	 */
	private function getRequiredTagIds( array $rule ): array {
		$requiredTagIds = isset( $rule['required_tag_ids'] ) && is_array( $rule['required_tag_ids'] )
			? $rule['required_tag_ids']
			: [];

		return ArrayUtil::normalizePositiveIntegers( $requiredTagIds );
	}

	/**
	 * Create an error result array.
	 *
	 * @param string $code The error code.
	 * @param string $message The error message.
	 * @param int $status The HTTP status code.
	 * @return array<string, mixed> The error result.
	 */
	private function errorResult( string $code, string $message, int $status ): array {
		return [
			'success' => false,
			'code'    => $code,
			'status'  => $status,
			'message' => $message,
		];
	}

	/**
	 * Check if the current user can bypass access checks.
	 *
	 * @return bool True if the user can bypass.
	 */
	public function canUserBypass(): bool {
		return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
	}
}
