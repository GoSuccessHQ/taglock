/**
 * Error Boundary component.
 *
 * Catches JavaScript errors in child component tree and displays fallback UI.
 *
 * @package TagLock
 */

import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Notice, Button } from '@wordpress/components';
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
			errorInfo: null,
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
		this.setState({ errorInfo });

		// Log to console in development.
		// eslint-disable-next-line no-console
		console.error('TagLock Error Boundary caught an error:', error, errorInfo);
	}

	/**
	 * Handle retry button click.
	 */
	handleRetry = () => {
		this.setState({
			hasError: false,
			error: null,
			errorInfo: null,
		});
	};

	/**
	 * Render the component.
	 *
	 * @return {JSX.Element} The component.
	 */
	render() {
		const { hasError, error } = this.state;
		const { children, fallback } = this.props;

		if (hasError) {
			// Custom fallback if provided.
			if (fallback) {
				return fallback;
			}

			// Default fallback UI.
			return (
				<div className="taglock-admin__error-boundary">
					<Notice status="error" isDismissible={false}>
						<strong>{__('Something went wrong', 'taglock')}</strong>
						<p>
							{__('An error occurred while rendering this section.', 'taglock')}
						</p>
						{error && error.message && (
							<p className="taglock-admin__error-message">
								<code>{error.message}</code>
							</p>
						)}
						<Button variant="secondary" onClick={this.handleRetry}>
							{__('Try Again', 'taglock')}
						</Button>
					</Notice>
				</div>
			);
		}

		return children;
	}
}

ErrorBoundary.propTypes = {
	children: PropTypes.node.isRequired,
	fallback: PropTypes.node,
};

export default ErrorBoundary;
