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
			title: __('Unlimited TagLocks', 'taglock'),
			description: __(
				'Create as many protected content blocks as you need. No limits, no restrictions.',
				'taglock'
			),
		},
		{
			title: __('Advanced Analytics', 'taglock'),
			description: __(
				'Track access patterns, conversion rates, and user engagement with detailed reports.',
				'taglock'
			),
		},
		{
			title: __('Custom Redirect Pages', 'taglock'),
			description: __(
				'Send visitors to specific pages when access is denied. Perfect for upselling and conversion optimization.',
				'taglock'
			),
		},
		{
			title: __('Email Notifications', 'taglock'),
			description: __(
				'Get instant alerts when users access protected content. Stay informed about your audience.',
				'taglock'
			),
		},
		{
			title: __('Priority Support', 'taglock'),
			description: __(
				'Get help when you need it. Our Pro support team responds within 24 hours.',
				'taglock'
			),
		},
		{
			title: __('White-Label Options', 'taglock'),
			description: __(
				'Remove all TagLock branding and customize the plugin to match your brand identity.',
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
					<p className="taglock-pro-sidebar__guarantee">
						{__('30-day money-back guarantee', 'taglock')}
					</p>
				</div>
			</CardBody>
		</Card>
	);
};

export default ProSidebar;
