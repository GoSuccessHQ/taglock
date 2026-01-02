import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import ContentLoader from './ContentLoader';
import './style.css';

domReady(() => {
	const containers = document.querySelectorAll('.taglock-container');

	containers.forEach((container) => {
		const tag = container.getAttribute('data-tag');
		const nonce = container.getAttribute('data-nonce');
		const contentId = container.getAttribute('data-content-id');
		const message = container.getAttribute('data-message');
		const loaderText = container.getAttribute('data-loader-text');

		if (tag && nonce && contentId) {
			createRoot(container).render(
				<ContentLoader
					tag={tag}
					nonce={nonce}
					contentId={contentId}
					message={message}
					loaderText={loaderText}
				/>
			);
		}
	});
});
