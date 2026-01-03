/**
 * Settings hook.
 *
 * Manages KlickTipp connection settings state and API operations.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { fetchSettings, saveSettings as apiSaveSettings } from '../services/api';

/**
 * Default connection status.
 */
const DEFAULT_CONNECTION_STATUS = {
	is_connected: false,
	checked_at: 0,
	error: '',
};

/**
 * Hook to manage settings state and operations.
 *
 * @return {Object} Settings state and operations.
 */
const useSettings = () => {
	const [username, setUsername] = useState('');
	const [password, setPassword] = useState('');
	const [hasPassword, setHasPassword] = useState(null);
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [connectionStatus, setConnectionStatus] = useState(DEFAULT_CONNECTION_STATUS);

	/**
	 * Load settings from API.
	 */
	const loadSettings = useCallback(async () => {
		try {
			const response = await fetchSettings();
			
			if (response?.success && response?.data) {
				setUsername(response.data.klicktipp_username || '');
				setHasPassword(Boolean(response.data.has_password));
				if (response.data.connection_status) {
					setConnectionStatus(response.data.connection_status);
				}
			} else {
				setHasPassword(false);
			}
		} catch (error) {
			setHasPassword(false);
			setNotice({
				status: 'error',
				message: error.message || __('Failed to load settings.', 'taglock'),
			});
		} finally {
			setIsLoading(false);
		}
	}, []);

	/**
	 * Save settings to API.
	 *
	 * @param {Function} onSuccess - Callback on successful save.
	 */
	const saveSettingsData = useCallback(async (onSuccess) => {
		setIsSaving(true);
		setNotice(null);

		try {
			const data = {
				klicktipp_username: username,
			};
			if (password) {
				data.klicktipp_password = password;
			}

			await apiSaveSettings(data);

			setPassword('');
			if (password) {
				setHasPassword(true);
			}

			setNotice({
				status: 'success',
				message: __('Settings saved successfully!', 'taglock'),
			});

			// Refresh connection status
			try {
				const refreshed = await fetchSettings();
				if (refreshed?.success && refreshed?.data?.connection_status) {
					setConnectionStatus(refreshed.data.connection_status);
				}
			} catch {
				// Ignore; the cron check and next page load will update the badge.
			}

			if (onSuccess) {
				onSuccess();
			}
		} catch (error) {
			setNotice({
				status: 'error',
				message: error.message || __('Failed to save settings.', 'taglock'),
			});
		} finally {
			setIsSaving(false);
		}
	}, [username, password]);

	/**
	 * Clear the notice.
	 */
	const clearNotice = useCallback(() => {
		setNotice(null);
	}, []);

	// Load settings on mount
	useEffect(() => {
		let isMounted = true;

		const init = async () => {
			if (isMounted) {
				await loadSettings();
			}
		};

		init();

		return () => {
			isMounted = false;
		};
	}, [loadSettings]);

	return {
		// State
		username,
		password,
		hasPassword,
		isLoading,
		isSaving,
		notice,
		connectionStatus,
		isConnected: Boolean(connectionStatus?.is_connected),
		
		// Setters
		setUsername,
		setPassword,
		setNotice,
		
		// Actions
		saveSettings: saveSettingsData,
		clearNotice,
		refreshSettings: loadSettings,
	};
};

export default useSettings;
