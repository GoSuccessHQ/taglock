/**
 * Pro Sidebar component.
 *
 * Displays promotional content for TagLock Pro features.
 */

import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody } from '@wordpress/components';
import { useAdminConfig } from '../hooks';

/**
 * Pro Sidebar component.
 *
 * @return {JSX.Element} The Pro sidebar.
 */
const ProSidebar = () => {
	const { proUrl, isPro } = useAdminConfig();

	// Don't show sidebar if Pro is active
	if (isPro) {
		return null;
	}

	const features = [
		{
			title: __('Teaser Mode', 'taglock'),
			description: __(
				'Show a preview of protected content to increase conversion rates. Perfect for enticing visitors to take action.',
				'taglock'
			),
		},
		{
			title: __('Custom Redirects', 'taglock'),
			description: __(
				'Send visitors to specific pages when access is denied. Ideal for sales pages, landing pages, or custom messaging.',
				'taglock'
			),
		},
		{
			title: __('Engagement Tagging', 'taglock'),
			description: __(
				'Automatically tag subscribers when they access protected content. Track engagement and trigger follow-up campaigns.',
				'taglock'
			),
		},
		{
			title: __('Admin Bypass', 'taglock'),
			description: __(
				'Preview protected content as administrator without needing a subscriber ID. Simplifies content testing and quality control.',
				'taglock'
			),
		},
		{
			title: __('Priority Support', 'taglock'),
			description: __(
				'Get direct help from our support team with faster response times and priority handling of your requests.',
				'taglock'
			),
		},
	];

	return (
		<Card className="taglock-pro-sidebar">
			<CardBody>
				<div className="taglock-pro-sidebar__header">
					<h3 className="taglock-pro-sidebar__title">
						{__('Unlock Pro Features', 'taglock')}
					</h3>
					<p className="taglock-pro-sidebar__subtitle">
						{__(
							'Take your content protection to the next level with TagLock Pro.',
							'taglock'
						)}
					</p>
				</div>

				<ul className="taglock-pro-sidebar__features">
					{features.map((feature, index) => (
						<li key={index} className="taglock-pro-sidebar__feature">
							<h4 className="taglock-pro-sidebar__feature-title">
								{feature.title}
							</h4>
							<p className="taglock-pro-sidebar__feature-description">
								{feature.description}
							</p>
						</li>
					))}
				</ul>

				<div className="taglock-pro-sidebar__cta">
					<Button
						variant="primary"
						href={proUrl}
						target="_blank"
						className="taglock-pro-sidebar__button"
					>
						{__('Upgrade to Pro', 'taglock')}
					</Button>
				</div>
			</CardBody>
		</Card>
	);
};

export default ProSidebar;
