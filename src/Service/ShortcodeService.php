<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Configuration\PluginConfiguration;
use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Repository\RuleRepository;
use GoSuccess\TagLock\Util\HookUtil;

use function __;
use function array_map;
use function ctype_digit;
use function current_user_can;
use function defined;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function function_exists;
use function implode;
use function md5;
use function set_transient;
use function shortcode_atts;
use function sprintf;
use function trim;
use function wp_create_nonce;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode Service
 *
 * Registers and handles the [taglock] shortcode for protected content.
 * IMPORTANT: Never outputs protected content directly - only a React container.
 */
final class ShortcodeService {

	public function __construct(
		private readonly PluginConfiguration $config,
		private readonly RuleRepository $ruleRepository,
		private readonly LoggerService $logger,
		private readonly AssetService $assetService
	) {}

	/**
	 * Render the shortcode.
	 *
	 * SECURITY: Never outputs $content directly! Only returns a React container
	 * that will load the content via REST API after server-side validation.
	 *
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @param string|null $content The protected content (NOT output directly).
	 * @return string The HTML container for React to mount.
	 */
	public function renderShortcode( $atts, ?string $content = null ): string {
		HookUtil::doAction( HookAction::BEFORE_SHORTCODE_RENDER, $atts, $content );

		// Ensure frontend assets are available for this shortcode instance.
		$this->assetService->enqueueFrontendAssets( true );

		$attributes = shortcode_atts(
			[
				'id' => '',
			],
			$atts,
			'taglock'
		);

		// Allow filtering attributes (for Pro features)
		$attributes = HookUtil::applyFilter( HookFilter::SHORTCODE_ATTRIBUTES, $attributes, $content );

		$ruleId = (string) ( $attributes['id'] ?? '' );
		$ruleId = trim( $ruleId );

		// Build data attributes for React - React handles ALL rendering including errors
		$dataAttributes = [
			'data-loader-text' => esc_attr( __( 'Checking access...', 'taglock' ) ),
		];

		// Validate rule ID
		if ( $ruleId === '' || ! ctype_digit( $ruleId ) ) {
			$this->logger->warning( __( 'TagLock shortcode missing or invalid rule id', 'taglock' ) );
			$dataAttributes['data-error'] = esc_attr( __( 'Error: id attribute is required. Example: [taglock id="1"]...[/taglock]', 'taglock' ) );
			return $this->renderContainer( $dataAttributes, $attributes );
		}

		$rule = $this->ruleRepository->getRule( (int) $ruleId );
		if ( $rule === null || empty( $rule['is_active'] ) ) {
			$this->logger->warning( __( 'TagLock shortcode references missing/inactive rule', 'taglock' ), [ 'rule_id' => $ruleId ] );
			$dataAttributes['data-error'] = esc_attr( __( 'Error: This TagLock rule does not exist or is disabled.', 'taglock' ) );
			return $this->renderContainer( $dataAttributes, $attributes );
		}

		// Generate nonce for REST API request
		$nonce = wp_create_nonce( $this->config->accessCheckNonce );
		$adminBypassEnabled = false;
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			$adminBypassEnabled = ! empty( $rule['admin_bypass_enabled'] );
		}

		// Add successful rule data attributes
		$dataAttributes['data-rule-id'] = esc_attr( $ruleId );
		$dataAttributes['data-nonce']   = esc_attr( $nonce );
		$dataAttributes['data-message'] = esc_attr( __( 'This content is protected. Please check your access.', 'taglock' ) );

		if ( $adminBypassEnabled ) {
			$dataAttributes['data-admin-bypass'] = '1';
		}

		// Store protected content in a transient with unique ID.
		// Use deterministic hash based on content + rule to allow transient reuse.
		$contentId = 'taglock_' . md5( (string) $content . $ruleId );
		set_transient( $contentId, $content, HOUR_IN_SECONDS );

		$dataAttributes['data-content-id'] = esc_attr( $contentId );

		$this->logger->debug( __( 'TagLock shortcode rendered', 'taglock' ), [
			'rule_id'    => $ruleId,
			'content_id' => $contentId,
		] );

		return $this->renderContainer( $dataAttributes, $attributes );
	}

	/**
	 * Render the container element for React to mount.
	 *
	 * @param array<string, string> $dataAttributes Data attributes for the container.
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 * @return string The HTML container.
	 */
	private function renderContainer( array $dataAttributes, array $attributes ): string {
		$dataAttrString = implode( ' ', array_map(
			fn( $key, $value ) => sprintf( '%s="%s"', $key, $value ),
			array_keys( $dataAttributes ),
			$dataAttributes
		) );

		// IMPORTANT: Keep shortcode output minimal. React renders everything else.
		$html = sprintf(
			'<div class="taglock" %s></div>',
			$dataAttrString
		);

		// Allow filtering final HTML (for Pro customizations)
		$html = HookUtil::applyFilter( HookFilter::SHORTCODE_CONTAINER_HTML, $html, $attributes, null );

		HookUtil::doAction( HookAction::AFTER_SHORTCODE_RENDER, $html, $attributes );

		return $html;
	}
}
