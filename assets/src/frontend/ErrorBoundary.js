/**
 * Error Boundary component for Frontend.
 *
 * Catches JavaScript errors in child component tree and displays fallback UI.
 *
 * @package TagLock
 */

import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Error Boundary component.
 *
 * Uses class component because error boundaries require componentDidCatch.
 */
class ErrorBoundary extends Component {
	/**
	 * Constructor.
	 *
	 * @param {Object} props - Component props.
	 */
	constructor(props) {
		super(props);
		this.state = {
			hasError: false,
			error: null,
		};
	}

	/**
	 * Update state when error is caught.
	 *
	 * @param {Error} error - The error that was thrown.
	 * @return {Object} New state.
	 */
	static getDerivedStateFromError(error) {
		return { hasError: true, error };
	}

	/**
	 * Log error information.
	 *
	 * @param {Error} error - The error that was thrown.
	 * @param {Object} errorInfo - Additional error information.
	 */
	componentDidCatch(error, errorInfo) {
		// Log to console in development.
		// eslint-disable-next-line no-console
		console.error('TagLock Error Boundary caught an error:', error, errorInfo);
	}

	/**
	 * Render the component.
	 *
	 * @return {JSX.Element} The component.
	 */
	render() {
		const { hasError } = this.state;
		const { children, fallbackMessage } = this.props;

		if (hasError) {
			return (
				<div className="taglock-error">
					<p>{fallbackMessage}</p>
				</div>
			);
		}

		return children;
	}
}

ErrorBoundary.propTypes = {
	children: PropTypes.node.isRequired,
	fallbackMessage: PropTypes.string,
};

ErrorBoundary.defaultProps = {
	fallbackMessage: __('An error occurred while loading content.', 'taglock'),
};

export default ErrorBoundary;
