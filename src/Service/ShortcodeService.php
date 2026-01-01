<?php

declare(strict_types=1);

namespace GoSuccess\TagLock\Service;

use GoSuccess\TagLock\Enum\HookAction;
use GoSuccess\TagLock\Enum\HookFilter;
use GoSuccess\TagLock\Util\HookUtil;

use function add_shortcode;
use function esc_attr;
use function esc_html;
use function shortcode_atts;
use function wp_create_nonce;
use function set_transient;
use function sprintf;
use function time;
use function implode;
use function array_map;

/**
 * Shortcode Service
 *
 * Registers and handles the [taglock] shortcode for protected content.
 * IMPORTANT: Never outputs protected content directly - only a React container.
 */
final class ShortcodeService {

	public function __construct(
		private readonly LoggerService $logger
	) {
		$this->registerShortcode();
	}

	/**
	 * Register the [taglock] shortcode.
	 */
	private function registerShortcode(): void {
		add_shortcode( 'taglock', [ $this, 'renderShortcode' ] );
		$this->logger->debug( __( 'TagLock shortcode registered', 'taglock' ) );
	}

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

		// Parse attributes
		$attributes = shortcode_atts(
			[
				'tag'          => '',
				'message'      => __( 'This content is protected. Please check your access.', 'taglock' ),
				'loader_text'  => __( 'Checking access...', 'taglock' ),
			],
			$atts,
			'taglock'
		);

		// Allow filtering attributes (for Pro features)
		$attributes = HookUtil::applyFilter( HookFilter::SHORTCODE_ATTRIBUTES, $attributes, $content );

		// Validate required attributes
		if ( empty( $attributes['tag'] ) ) {
			$this->logger->warning( __( 'TagLock shortcode missing required "tag" attribute', 'taglock' ) );
			return '<div class="taglock-error">' . esc_html__( 'Error: Tag attribute is required.', 'taglock' ) . '</div>';
		}

		// Generate nonce for REST API request
		$nonce = wp_create_nonce( 'taglock_access_check' );

		// Create data attributes for React
		$dataAttributes = [
			'data-tag'         => esc_attr( $attributes['tag'] ),
			'data-nonce'       => esc_attr( $nonce ),
			'data-message'     => esc_attr( $attributes['message'] ),
			'data-loader-text' => esc_attr( $attributes['loader_text'] ),
		];

		// Store protected content in a transient with unique ID
		$contentId = 'taglock_' . md5( $content . $attributes['tag'] . time() );
		set_transient( $contentId, $content, HOUR_IN_SECONDS );

		$dataAttributes['data-content-id'] = esc_attr( $contentId );

		$dataAttrString = implode( ' ', array_map(
			fn( $key, $value ) => sprintf( '%s="%s"', $key, $value ),
			array_keys( $dataAttributes ),
			$dataAttributes
		) );

		// Build container HTML
		$html = sprintf(
			'<div id="taglock-root" class="taglock-container" %s>
				<div class="taglock-loader">
					<span class="taglock-spinner"></span>
					<span class="taglock-loader-text">%s</span>
				</div>
			</div>',
			$dataAttrString,
			esc_html( $attributes['loader_text'] )
		);

		// Allow filtering final HTML (for Pro customizations)
		$html = HookUtil::applyFilter( HookFilter::SHORTCODE_CONTAINER_HTML, $html, $attributes, $content );

		HookUtil::doAction( HookAction::AFTER_SHORTCODE_RENDER, $html, $attributes );

		$this->logger->debug( __( 'TagLock shortcode rendered', 'taglock' ), [
			'tag'        => $attributes['tag'],
			'content_id' => $contentId,
		] );

		return $html;
	}
}
