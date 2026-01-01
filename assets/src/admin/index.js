import { render } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import AdminApp from './AdminApp';
import './style.css';

domReady(() => {
	const root = document.getElementById('taglock-admin-root');
	if (root) {
		render(<AdminApp />, root);
	}
});
