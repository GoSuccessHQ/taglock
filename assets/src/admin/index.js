import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import AdminApp from './AdminApp';
import { ErrorBoundary } from './components';
import './style.css';

domReady(() => {
	const container = document.getElementById('taglock-admin-root');
	if (container) {
		createRoot(container).render(
			<ErrorBoundary>
				<AdminApp />
			</ErrorBoundary>
		);
	}
});
