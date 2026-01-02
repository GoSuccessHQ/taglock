import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import ContentLoader from './ContentLoader';
import './style.css';

domReady(() => {
	const containers = document.querySelectorAll('.taglock-container');
	const roots = containers.length
		? containers
		: document.querySelectorAll('.taglock-loader');

	roots.forEach((rootEl) => {
		let tag = rootEl.getAttribute('data-tag');
		let nonce = rootEl.getAttribute('data-nonce');
		let contentId = rootEl.getAttribute('data-content-id');
		let message = rootEl.getAttribute('data-message');
		let loaderText = rootEl.getAttribute('data-loader-text');

		if (!tag || !nonce || !contentId) {
			const configEl = rootEl.querySelector?.('.taglock-config');
			const raw = configEl?.textContent?.trim();
			if (raw) {
				try {
					const cfg = JSON.parse(raw);
					tag = tag || cfg?.tag;
					nonce = nonce || cfg?.nonce;
					contentId = contentId || cfg?.contentId;
					message = message || cfg?.message;
					loaderText = loaderText || cfg?.loaderText;
				} catch (e) {
					// ignore
				}
			}
		}

		if (tag && nonce && contentId) {
			createRoot(rootEl).render(
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
