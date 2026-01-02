import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import ContentLoader from './ContentLoader';
import './style.css';

const getTlIdFromHash = () => {
	const rawHash = window.location.hash || '';
	if (!rawHash.includes('taglock_subscriber_id=')) {
		return null;
	}

	const hash = rawHash.startsWith('#') ? rawHash.slice(1) : rawHash;
	if (!hash.includes('=')) {
		return null;
	}

	const params = new URLSearchParams(hash);
	const tlId = params.get('taglock_subscriber_id');
	if (!tlId) {
		return null;
	}

	localStorage.setItem('taglock_subscriber_id', tlId);

	// Remove taglock_subscriber_id from the address bar after persisting it.
	params.delete('taglock_subscriber_id');
	const nextHash = params.toString();
	const nextUrl = `${window.location.pathname}${window.location.search}${nextHash ? `#${nextHash}` : ''}`;
	window.history.replaceState(null, '', nextUrl);

	return tlId;
};

const getTlId = () => {
	const fromHash = getTlIdFromHash();
	if (fromHash) {
		return fromHash;
	}
	return localStorage.getItem('taglock_subscriber_id');
};

domReady(() => {
	const containers = document.querySelectorAll('.taglock');
	if (!containers.length) {
		return;
	}

	const tlId = getTlId();

	const firstNonce = containers[0].getAttribute('data-nonce');
	const items = [];

	containers.forEach((container) => {
		const tag = container.getAttribute('data-tag');
		const contentId = container.getAttribute('data-content-id');
		if (tag && contentId) {
			items.push({ tag, content_id: contentId });
		}
	});

	const batchRequest = tlId && firstNonce && items.length
		? apiFetch({
			path: '/taglock/v1/check-access',
			method: 'POST',
			data: {
				subscriber_id: tlId,
				items,
				nonce: firstNonce,
			},
		})
		: null;

	containers.forEach((container) => {
		const contentId = container.getAttribute('data-content-id');
		const message = container.getAttribute('data-message');
		const loaderText = container.getAttribute('data-loader-text');

		if (contentId) {
			createRoot(container).render(
				<ContentLoader
					contentId={contentId}
					message={message}
					loaderText={loaderText}
					tlId={tlId}
					batchRequest={batchRequest}
				/>
			);
		}
	});
});
