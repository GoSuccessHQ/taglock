<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Service\Rule\RuleRepository;
use GoSuccess\TagLock\Util\HookUtil;

use function esc_attr;
use function esc_html;
use function ctype_digit;
use function shortcode_atts;
use function wp_create_nonce;
use function set_transient;
use function sprintf;
use function time;
use function implode;
use function array_map;
use function current_user_can;
use function function_exists;
use function trim;

/**
 * Shortcode Service
 *
 * Registers and handles the [taglock] shortcode for protected content.
 * IMPORTANT: Never outputs protected content directly - only a React container.
 */
final class ShortcodeService {

	public function __construct(
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
		if ( $ruleId === '' || ! ctype_digit( $ruleId ) ) {
			$this->logger->warning( __( 'TagLock shortcode missing or invalid rule id', 'taglock' ) );
			return '<div class="taglock-error">' . esc_html__( 'Error: id attribute is required. Example: [taglock id="1"]...[/taglock]', 'taglock' ) . '</div>';
		}

		$rule = $this->ruleRepository->getRule( (int) $ruleId );
		if ( $rule === null || empty( $rule['is_active'] ) ) {
			$this->logger->warning( __( 'TagLock shortcode references missing/inactive rule', 'taglock' ), [ 'rule_id' => $ruleId ] );
			return '<div class="taglock-error">' . esc_html__( 'Error: This TagLock rule does not exist or is disabled.', 'taglock' ) . '</div>';
		}

		// Generate nonce for REST API request
		$nonce = wp_create_nonce( 'taglock_access_check' );
		$adminBypassEnabled = false;
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			$adminBypassEnabled = ! empty( $rule['admin_bypass_enabled'] );
		}

		// Create data attributes for React
		$dataAttributes = [
			'data-rule-id'     => esc_attr( $ruleId ),
			'data-nonce'       => esc_attr( $nonce ),
			'data-message'     => esc_attr( __( 'This content is protected. Please check your access.', 'taglock' ) ),
			'data-loader-text' => esc_attr( __( 'Checking access...', 'taglock' ) ),
		];

		if ( $adminBypassEnabled ) {
			$dataAttributes['data-admin-bypass'] = '1';
		}

		// Store protected content in a transient with unique ID
		$contentId = 'taglock_' . md5( (string) $content . $ruleId . time() );
		set_transient( $contentId, $content, HOUR_IN_SECONDS );

		$dataAttributes['data-content-id'] = esc_attr( $contentId );

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
		$html = HookUtil::applyFilter( HookFilter::SHORTCODE_CONTAINER_HTML, $html, $attributes, $content );

		HookUtil::doAction( HookAction::AFTER_SHORTCODE_RENDER, $html, $attributes );

		$this->logger->debug( __( 'TagLock shortcode rendered', 'taglock' ), [
			'rule_id'    => $ruleId,
			'content_id' => $contentId,
		] );

		return $html;
	}
}
