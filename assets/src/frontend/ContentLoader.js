import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

const ContentLoader = ({ subscriberId, adminBypass, contentId, message, loaderText, batchRequest }) => {
	const [state, setState] = useState({
		loading: true,
		content: null,
		error: null,
		isTeaser: false,
	});

	useEffect(() => {
		const checkAccess = async () => {
			if (!subscriberId && !adminBypass) {
				setState({
					loading: false,
					content: null,
					error: __('Access denied. This link is invalid or has expired. Please use the link from your email.', 'taglock'),
					isTeaser: false,
				});
				return;
			}

			try {
				if (!batchRequest) {
					setState({
						loading: false,
						content: null,
						error: __('An error occurred while checking access. Please try again later.', 'taglock'),
					});
					return;
				}

				const response = await batchRequest;
				if (!response?.success) {
					setState({
						loading: false,
						content: null,
						error: response?.message || __('An error occurred while checking access. Please try again later.', 'taglock'),
					});
					return;
				}

				const result = response?.data?.results?.[contentId];
				if (!result) {
					setState({
						loading: false,
						content: null,
						error: __('An error occurred while checking access. Please try again later.', 'taglock'),
					});
					return;
				}

				if (result.success) {
					setState({
						loading: false,
						content: result?.content ?? null,
						error: null,
						isTeaser: false,
					});
				} else {
					if (result.redirect_url) {
						window.location.href = result.redirect_url;
						return;
					}

					if (result.teaser_html) {
						setState({
							loading: false,
							content: result.teaser_html,
							error: null,
							isTeaser: true,
						});
						return;
					}

					setState({
						loading: false,
						content: null,
						error: result.message || message,
						isTeaser: false,
					});
				}
			} catch (error) {
				setState({
					loading: false,
					content: null,
					error: error.message || __('An error occurred while checking access. Please try again later.', 'taglock'),
					isTeaser: false,
				});
			}
		};

		checkAccess();
		}, [subscriberId, adminBypass, contentId, message, batchRequest]);

	if (state.loading) {
		return (
			<div className="taglock-loader">
				<Spinner />
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

	const contentClassName = state.isTeaser ? 'taglock-teaser' : 'taglock-content';

	return (
		<div
			className={contentClassName}
			dangerouslySetInnerHTML={{ __html: state.content }}
		/>
	);
};

export default ContentLoader;
