import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import apiFetch from '@wordpress/api-fetch';
import ContentLoader from './ContentLoader';
import './style.css';

const getSubscriberId = () => {
	const urlParams = new URLSearchParams(window.location.search);
	const fromUrl = urlParams.get('subscriber_id');
	if (fromUrl) {
		localStorage.setItem('taglock_subscriber_id', fromUrl);
		return fromUrl;
	}

	return localStorage.getItem('taglock_subscriber_id');
};

domReady(() => {
	const containers = document.querySelectorAll('.taglock');
	if (!containers.length) {
		return;
	}

	const subscriberId = getSubscriberId();

	const firstNonce = containers[0].getAttribute('data-nonce');
	const items = [];

	containers.forEach((container) => {
		const tag = container.getAttribute('data-tag');
		const contentId = container.getAttribute('data-content-id');
		if (tag && contentId) {
			items.push({ tag, content_id: contentId });
		}
	});

	const batchRequest = subscriberId && firstNonce && items.length
		? apiFetch({
			path: '/taglock/v1/check-access-batch',
			method: 'POST',
			data: {
				subscriber_id: subscriberId,
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
					subscriberId={subscriberId}
					batchRequest={batchRequest}
				/>
			);
		}
	});
});
