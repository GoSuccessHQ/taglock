/**
 * Connection Tab component.
 *
 * Handles KlickTipp API credentials input and connection testing.
 */

import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	TextControl,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Connection Tab component.
 *
 * @param {Object} props - Component props.
 * @param {Object} props.settings - Current settings object.
 * @param {string} props.settings.username - KlickTipp username.
 * @param {string} props.settings.password - KlickTipp password.
 * @param {Function} props.onChange - Callback when settings change.
 * @param {Function} props.onSave - Callback to save settings.
 * @param {boolean} props.isSaving - Whether settings are being saved.
 * @param {boolean} props.isConnected - Whether connected to KlickTipp.
 * @param {string|null} props.notice - Notice message to display.
 * @param {string} props.noticeType - Notice type ('success' or 'error').
 * @param {Function} props.clearNotice - Callback to clear notice.
 * @return {JSX.Element} The connection tab.
 */
const ConnectionTab = ({
	settings,
	onChange,
	onSave,
	isSaving,
	isConnected,
	notice,
	noticeType,
	clearNotice,
}) => {
	const [showPassword, setShowPassword] = useState(false);

	/**
	 * Handle username change.
	 *
	 * @param {string} value - New username value.
	 */
	const handleUsernameChange = useCallback((value) => {
		onChange({ ...settings, username: value });
	}, [settings, onChange]);

	/**
	 * Handle password change.
	 *
	 * @param {string} value - New password value.
	 */
	const handlePasswordChange = useCallback((value) => {
		onChange({ ...settings, password: value });
	}, [settings, onChange]);

	/**
	 * Toggle password visibility.
	 */
	const togglePasswordVisibility = useCallback(() => {
		setShowPassword((prev) => !prev);
	}, []);

	/**
	 * Handle form submission.
	 *
	 * @param {Event} e - Form submit event.
	 */
	const handleSubmit = useCallback((e) => {
		e.preventDefault();
		onSave();
	}, [onSave]);

	return (
		<Card className="taglock-admin__card">
			<CardHeader>
				<h2>{__('KlickTipp Connection', 'taglock')}</h2>
			</CardHeader>
			<CardBody>
				{notice && (
					<Notice
						status={noticeType}
						onRemove={clearNotice}
						isDismissible
					>
						{notice}
					</Notice>
				)}

				<form onSubmit={handleSubmit}>
					<TextControl
						label={__('KlickTipp Username', 'taglock')}
						value={settings.username}
						onChange={handleUsernameChange}
						help={__('Your KlickTipp API username (email address).', 'taglock')}
						autoComplete="username"
					/>

					<div className="taglock-admin__password-field">
						<TextControl
							label={__('KlickTipp Password', 'taglock')}
							type={showPassword ? 'text' : 'password'}
							value={settings.password}
							onChange={handlePasswordChange}
							help={__('Your KlickTipp API password.', 'taglock')}
							autoComplete="current-password"
						/>
						<Button
							variant="secondary"
							isSmall
							onClick={togglePasswordVisibility}
							className="taglock-admin__toggle-password"
						>
							{showPassword
								? __('Hide', 'taglock')
								: __('Show', 'taglock')}
						</Button>
					</div>

					<div className="taglock-admin__connection-status">
						<span className="taglock-admin__status-label">
							{__('Connection Status:', 'taglock')}
						</span>
						<span
							className={`taglock-admin__status-badge taglock-admin__status-badge--${
								isConnected ? 'success' : 'error'
							}`}
						>
							{isConnected
								? __('Connected', 'taglock')
								: __('Not Connected', 'taglock')}
						</span>
					</div>

					<Button
						variant="primary"
						type="submit"
						isBusy={isSaving}
						disabled={isSaving}
					>
						{isSaving && <Spinner />}
						{__('Save & Test Connection', 'taglock')}
					</Button>
				</form>
			</CardBody>
		</Card>
	);
};

ConnectionTab.propTypes = {
	settings: PropTypes.shape({
		username: PropTypes.string.isRequired,
		password: PropTypes.string.isRequired,
	}).isRequired,
	onChange: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
	isSaving: PropTypes.bool.isRequired,
	isConnected: PropTypes.bool.isRequired,
	notice: PropTypes.string,
	noticeType: PropTypes.oneOf(['success', 'error']),
	clearNotice: PropTypes.func.isRequired,
};

export default ConnectionTab;
