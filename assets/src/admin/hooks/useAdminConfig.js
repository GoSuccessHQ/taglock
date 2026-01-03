/**
 * Admin configuration hook.
 *
 * Provides access to the admin configuration from window.taglockAdminConfig.
 */

import { useMemo } from '@wordpress/element';

/**
 * Get admin configuration from window object.
 *
 * @return {Object|null} The admin configuration.
 */
const getAdminConfig = () => {
	if (typeof window === 'undefined') {
		return null;
	}
	return window.taglockAdminConfig || null;
};

/**
 * Hook to access admin configuration.
 *
 * @return {Object} Admin configuration values.
 */
const useAdminConfig = () => {
	return useMemo(() => {
		const adminConfig = getAdminConfig();
		
		return {
			apiNamespace: adminConfig?.apiNamespace || 'taglock/v1',
			proUrl: adminConfig?.proUrl || 'https://gosuccess.io/taglock',
			isPro: Boolean(adminConfig?.isPro),
			isProDisabled: !Boolean(adminConfig?.isPro),
		};
	}, []);
};

export default useAdminConfig;
