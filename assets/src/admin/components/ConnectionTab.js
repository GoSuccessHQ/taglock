/**
 * Connection Tab component.
 *
 * Displays the KlickTipp connection settings form.
 */

import { useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	TextControl,
	Button,
	Spinner,
	Disabled,
} from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Connection Tab component.
 *
 * @param {Object} props - Component props.
 * @param {string} props.username - KlickTipp username.
 * @param {string} props.password - KlickTipp password.
 * @param {boolean} props.hasPassword - Whether a password is already saved.
 * @param {boolean} props.isLoading - Whether settings are loading.
 * @param {boolean} props.isSaving - Whether settings are being saved.
 * @param {boolean} props.isConnected - Whether connected to KlickTipp.
 * @param {Function} props.onUsernameChange - Callback when username changes.
 * @param {Function} props.onPasswordChange - Callback when password changes.
 * @param {Function} props.onSave - Callback to save settings.
 * @return {JSX.Element} The connection tab.
 */
const ConnectionTab = ({
	username,
	password,
	hasPassword,
	isLoading,
	isSaving,
	isConnected,
	onUsernameChange,
	onPasswordChange,
	onSave,
}) => {
	/**
	 * Handle form submission.
	 *
	 * @param {Event} event - Form submit event.
	 */
	const handleSubmit = useCallback((event) => {
		event.preventDefault();
		if (isLoading || isSaving) {
			return;
		}
		onSave();
	}, [isLoading, isSaving, onSave]);

	const connectionBadgeText = isConnected
		? __('Connected', 'taglock')
		: __('Disconnected', 'taglock');

	return (
		<Card>
			<CardHeader>
				<div className="taglock-admin__card-header">
					<h2 className="taglock-admin__card-header-title">
						{__('KlickTipp Connection', 'taglock')}
					</h2>
					<div className="taglock-admin__card-header-indicator">
						{isLoading ? (
							<Spinner />
						) : (
							<span
								className={
									'taglock-admin__status-badge taglock-admin__connection-badge ' +
									(isConnected
										? 'taglock-admin__status-badge--success'
										: 'taglock-admin__status-badge--error')
								}
								role="status"
								aria-live="polite"
							>
								{connectionBadgeText}
							</span>
						)}
					</div>
				</div>
			</CardHeader>
			<CardBody>
				<form onSubmit={handleSubmit}>
					<p className="description">
						{__('Enter your KlickTipp username and password to connect.', 'taglock')}
					</p>

					<Disabled isDisabled={isLoading}>
						<div className="taglock-admin__credentials">
							<TextControl
								label={__('Username', 'taglock')}
								value={username}
								onChange={onUsernameChange}
								autoComplete="username"
								autoCapitalize="none"
								autoCorrect="off"
								spellCheck={false}
								required
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>

							<TextControl
								label={__('Password', 'taglock')}
								type="password"
								value={password}
								onChange={onPasswordChange}
								autoComplete="current-password"
								autoCapitalize="none"
								autoCorrect="off"
								spellCheck={false}
								placeholder={
									hasPassword
										? __('Saved. Enter a new password to update.', 'taglock')
										: ''
								}
								help={
									hasPassword
										? __('Password is already saved. Enter a new one only if you want to change it.', 'taglock')
										: __('For security reasons, the password is not displayed after saving.', 'taglock')
								}
								required={!hasPassword}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</div>

						<Button
							variant="primary"
							type="submit"
							isBusy={isSaving}
							disabled={isSaving}
							className="taglock-admin__connect-button"
						>
							{__('Connect', 'taglock')}
						</Button>
					</Disabled>
				</form>
			</CardBody>
		</Card>
	);
};

ConnectionTab.propTypes = {
	username: PropTypes.string.isRequired,
	password: PropTypes.string.isRequired,
	hasPassword: PropTypes.bool.isRequired,
	isLoading: PropTypes.bool.isRequired,
	isSaving: PropTypes.bool.isRequired,
	isConnected: PropTypes.bool.isRequired,
	onUsernameChange: PropTypes.func.isRequired,
	onPasswordChange: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
};

export default ConnectionTab;
