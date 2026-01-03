import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import FrontendApp from './FrontendApp';
import ErrorBoundary from './ErrorBoundary';
import './style.css';

/**
 * Get subscriber ID from URL hash.
 *
 * Persists to localStorage and removes from URL.
 *
 * @return {string|null} Subscriber ID or null.
 */
const getSubscriberIdFromHash = () => {
	const rawHash = window.location.hash || '';
	if (!rawHash.includes('taglock_subscriber_id=')) {
		return null;
	}

	const hash = rawHash.startsWith('#') ? rawHash.slice(1) : rawHash;
	if (!hash.includes('=')) {
		return null;
	}

	const params = new URLSearchParams(hash);
	const subscriberId = params.get('taglock_subscriber_id');
	if (!subscriberId) {
		return null;
	}

	localStorage.setItem('taglock_subscriber_id', subscriberId);

	// Remove taglock_subscriber_id from the address bar after persisting it.
	params.delete('taglock_subscriber_id');
	const nextHash = params.toString();
	const nextUrl = `${window.location.pathname}${window.location.search}${nextHash ? `#${nextHash}` : ''}`;
	window.history.replaceState(null, '', nextUrl);

	return subscriberId;
};

/**
 * Get subscriber ID from hash or localStorage.
 *
 * @return {string|null} Subscriber ID or null.
 */
const getSubscriberId = () => {
	const fromHash = getSubscriberIdFromHash();
	if (fromHash) {
		return fromHash;
	}
	return localStorage.getItem('taglock_subscriber_id');
};

/**
 * Initialize TagLock frontend.
 */
domReady(() => {
	const containers = document.querySelectorAll('.taglock');
	if (!containers.length) {
		return;
	}

	const adminBypass = Array.from(containers).some(
		(container) => container.getAttribute('data-admin-bypass') === '1'
	);

	const subscriberId = getSubscriberId();

	const firstNonce = containers[0].getAttribute('data-nonce');
	const items = [];

	containers.forEach((container) => {
		const ruleId = container.getAttribute('data-rule-id');
		const contentId = container.getAttribute('data-content-id');
		if (ruleId && contentId) {
			items.push({ rule_id: ruleId, content_id: contentId });
		}
	});

	const shouldRequest = firstNonce && items.length && (subscriberId || adminBypass);
	const data = shouldRequest
		? {
			items,
			nonce: firstNonce,
			...(subscriberId ? { subscriber_id: subscriberId } : {}),
		}
		: null;

	const batchRequest = data
		? apiFetch({
			path: '/taglock/v1/check-access',
			method: 'POST',
			data,
		})
		: null;

	containers.forEach((container) => {
		const contentId = container.getAttribute('data-content-id');
		const message = container.getAttribute('data-message');
		const loaderText = container.getAttribute('data-loader-text');

		if (contentId) {
			createRoot(container).render(
				<ErrorBoundary>
					<FrontendApp
						contentId={contentId}
						message={message}
						loaderText={loaderText}
						subscriberId={subscriberId}
						adminBypass={adminBypass}
						batchRequest={batchRequest}
					/>
				</ErrorBoundary>
			);
		}
	});
});
