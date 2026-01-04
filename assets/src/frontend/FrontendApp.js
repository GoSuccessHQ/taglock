/**
 * Frontend App component.
 *
 * Handles access checking and content loading for TagLock protected content.
 * Uses Intersection Observer for lazy loading to improve performance.
 *
 * @package TagLock
 */

import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import PropTypes from 'prop-types';
import { useIntersectionObserver } from './hooks';

/**
 * Content state shape.
 *
 * @typedef {Object} ContentState
 * @property {boolean} loading - Whether content is loading.
 * @property {string|null} content - HTML content to display.
 * @property {string|null} error - Error message if any.
 * @property {boolean} isTeaser - Whether content is teaser.
 */

/**
 * Initial content state.
 *
 * @type {ContentState}
 */
const INITIAL_STATE = {
	loading: true,
	content: null,
	error: null,
	isTeaser: false,
};

/**
 * Error state factory.
 *
 * @param {string} message - Error message.
 * @return {ContentState} Error state.
 */
const createErrorState = (message) => ({
	loading: false,
	content: null,
	error: message,
	isTeaser: false,
});

/**
 * Content state factory.
 *
 * @param {string} content - HTML content.
 * @param {boolean} isTeaser - Whether content is teaser.
 * @return {ContentState} Content state.
 */
const createContentState = (content, isTeaser = false) => ({
	loading: false,
	content,
	error: null,
	isTeaser,
});

/**
 * Frontend App component.
 *
 * @param {Object} props - Component props.
 * @param {string|null} props.error - Immediate error from shortcode validation.
 * @param {string|null} props.subscriberId - Subscriber ID from localStorage or URL.
 * @param {boolean} props.adminBypass - Whether admin bypass is enabled.
 * @param {string|null} props.contentId - Unique content ID for this block.
 * @param {string} props.message - Default deny message.
 * @param {string} props.loaderText - Text to show while loading.
 * @param {Promise|null} props.batchRequest - Batch API request promise.
 * @param {boolean} [props.lazyLoad=false] - Whether to enable lazy loading.
 * @return {JSX.Element} The frontend app.
 */
const FrontendApp = ({
	error = null,
	subscriberId = null,
	adminBypass,
	contentId = null,
	message = '',
	loaderText = '',
	batchRequest = null,
	lazyLoad = false,
}) => {
	// If there's an immediate error from shortcode validation, show it directly.
	const initialState = error ? createErrorState(error) : INITIAL_STATE;
	const [state, setState] = useState(initialState);
	const [hasStartedLoading, setHasStartedLoading] = useState(!lazyLoad);

	// Intersection observer for lazy loading.
	const [containerRef, isVisible] = useIntersectionObserver({
		rootMargin: '200px', // Start loading 200px before visible.
		triggerOnce: true,
	});

	/**
	 * Default error message.
	 */
	const defaultError = useMemo(() => {
		return __('An error occurred while checking access. Please try again later.', 'taglock');
	}, []);

	/**
	 * Access denied error message.
	 */
	const accessDeniedError = useMemo(() => {
		return __('Access denied. This link is invalid or has expired. Please use the link from your email.', 'taglock');
	}, []);

	/**
	 * Process API response for this content.
	 *
	 * @param {Object} response - API response.
	 */
	const processResponse = useCallback((response) => {
		if (!response?.success) {
			setState(createErrorState(response?.message || defaultError));
			return;
		}

		const result = response?.data?.results?.[contentId];
		if (!result) {
			setState(createErrorState(defaultError));
			return;
		}

		if (result.success) {
			setState(createContentState(result?.content ?? null, false));
			return;
		}

		// Handle redirect.
		if (result.redirect_url) {
			window.location.href = result.redirect_url;
			return;
		}

		// Handle teaser.
		if (result.teaser_html) {
			setState(createContentState(result.teaser_html, true));
			return;
		}

		// Show deny message.
		setState(createErrorState(result.message || message));
	}, [contentId, message, defaultError]);

	/**
	 * Check access on mount.
	 */
	useEffect(() => {
		// Skip if there's an immediate error from shortcode validation.
		if (error) {
			return;
		}

		// For lazy loading, wait until visible.
		if (lazyLoad && !isVisible) {
			return;
		}

		// Already started loading.
		if (hasStartedLoading && lazyLoad) {
			return;
		}

		setHasStartedLoading(true);

		const checkAccess = async () => {
			// No subscriber and no admin bypass - deny immediately.
			if (!subscriberId && !adminBypass) {
				setState(createErrorState(accessDeniedError));
				return;
			}

			// No batch request available.
			if (!batchRequest) {
				setState(createErrorState(defaultError));
				return;
			}

			try {
				const response = await batchRequest;
				processResponse(response);
			} catch (err) {
				setState(createErrorState(err.message || defaultError));
			}
		};

		checkAccess();
	}, [error, subscriberId, adminBypass, batchRequest, processResponse, defaultError, accessDeniedError, lazyLoad, isVisible, hasStartedLoading]);

	// Loading state (including lazy load placeholder).
	if (state.loading) {
		return (
			<div ref={containerRef} className="taglock-loader">
				<Spinner />
				<span className="taglock-loader-text">{loaderText}</span>
			</div>
		);
	}

	// Error state.
	if (state.error) {
		return (
			<div className="taglock-error">
				<p>{state.error}</p>
			</div>
		);
	}

	// Content state.
	const contentClassName = state.isTeaser ? 'taglock-teaser' : 'taglock-content';

	return (
		<div
			className={contentClassName}
			dangerouslySetInnerHTML={{ __html: state.content }}
		/>
	);
};

FrontendApp.propTypes = {
	error: PropTypes.string,
	subscriberId: PropTypes.string,
	adminBypass: PropTypes.bool.isRequired,
	contentId: PropTypes.string,
	message: PropTypes.string,
	loaderText: PropTypes.string,
	batchRequest: PropTypes.instanceOf(Promise),
	lazyLoad: PropTypes.bool,
};

export default FrontendApp;
