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
			// Get subscriber ID from URL parameter (has priority)
			const urlParams = new URLSearchParams(window.location.search);
			let subscriberId = urlParams.get('subscriber_id');

			// If no URL parameter, check localStorage
			if (!subscriberId) {
				subscriberId = localStorage.getItem('taglock_subscriber_id');
			}

			// If subscriber ID found in URL, save it to localStorage for future visits
			if (urlParams.get('subscriber_id')) {
				localStorage.setItem('taglock_subscriber_id', urlParams.get('subscriber_id'));
			}

			if (!subscriberId) {
				setState({
					loading: false,
					content: null,
					error: __('Access denied. This link is invalid or has expired. Please use the link from your email.', 'taglock'),
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
					error: error.message || __('An error occurred while checking access. Please try again later.', 'taglock'),
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

export default ContentLoader;
