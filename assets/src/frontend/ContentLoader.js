import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const ContentLoader = ({ tag, nonce, contentId, message, loaderText }) => {
	const [state, setState] = useState({
		loading: true,
		content: null,
		error: null,
	});

	useEffect(() => {
		const checkAccess = async () => {
			// Get subscriber ID from URL or cookie
			const urlParams = new URLSearchParams(window.location.search);
			const subscriberId = urlParams.get('subscriber_id') || getCookie('taglock_subscriber_id');

			if (!subscriberId) {
				setState({
					loading: false,
					content: null,
					error: __('Subscriber ID not found. Please use the access link provided.', 'taglock'),
				});
				return;
			}

			try {
				const response = await apiFetch({
					path: '/taglock/v1/check-access',
					method: 'POST',
					data: {
						subscriber_id: subscriberId,
						tag,
						content_id: contentId,
						nonce,
					},
				});

				if (response.success) {
					setState({
						loading: false,
						content: response.content,
						error: null,
					});
				} else {
					// Check if redirect URL is provided (Pro feature)
					if (response.redirect_url) {
						window.location.href = response.redirect_url;
						return;
					}

					setState({
						loading: false,
						content: null,
						error: response.message || message,
					});
				}
			} catch (error) {
				setState({
					loading: false,
					content: null,
					error: error.message || __('An error occurred while checking access.', 'taglock'),
				});
			}
		};

		checkAccess();
	}, [tag, nonce, contentId, message]);

	if (state.loading) {
		return (
			<div className="taglock-loader">
				<span className="taglock-spinner"></span>
				<span className="taglock-loader-text">{loaderText}</span>
			</div>
		);
	}

	if (state.error) {
		return (
			<div className="taglock-error">
				<p>{state.error}</p>
			</div>
		);
	}

	return (
		<div
			className="taglock-content"
			dangerouslySetInnerHTML={{ __html: state.content }}
		/>
	);
};

// Helper function to get cookie
const getCookie = (name) => {
	const value = `; ${document.cookie}`;
	const parts = value.split(`; ${name}=`);
	if (parts.length === 2) return parts.pop().split(';').shift();
	return null;
};

export default ContentLoader;
