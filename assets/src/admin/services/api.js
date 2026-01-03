/**
 * TagLock Admin API Service
 *
 * Centralizes all REST API calls for the admin interface.
 */

import apiFetch from '@wordpress/api-fetch';

/**
 * Get the API namespace from config.
 *
 * @return {string} The API namespace.
 */
const getApiNamespace = () => {
	if (typeof window === 'undefined') {
		return 'taglock/v1';
	}
	return window.taglockAdminConfig?.apiNamespace || 'taglock/v1';
};

/**
 * Fetch settings from the API.
 *
 * @return {Promise<Object>} The settings response.
 */
export const fetchSettings = async () => {
	const response = await apiFetch({
		path: `/${getApiNamespace()}/settings`,
		method: 'GET',
	});
	return response;
};

/**
 * Save settings to the API.
 *
 * @param {Object} data - The settings data.
 * @param {string} data.klicktipp_username - The KlickTipp username.
 * @param {string} [data.klicktipp_password] - The KlickTipp password.
 * @return {Promise<Object>} The save response.
 */
export const saveSettings = async (data) => {
	const response = await apiFetch({
		path: `/${getApiNamespace()}/settings`,
		method: 'POST',
		data,
	});
	return response;
};

/**
 * Fetch rules from the API.
 *
 * @param {number} page - The page number.
 * @param {number} perPage - Items per page.
 * @return {Promise<Object>} The rules response.
 */
export const fetchRules = async (page, perPage) => {
	const response = await apiFetch({
		path: `/${getApiNamespace()}/rules?page=${page}&per_page=${perPage}`,
		method: 'GET',
	});
	return response;
};

/**
 * Create a new rule.
 *
 * @param {Object} data - The rule data.
 * @return {Promise<Object>} The create response.
 */
export const createRule = async (data) => {
	const response = await apiFetch({
		path: `/${getApiNamespace()}/rules`,
		method: 'POST',
		data,
	});
	return response;
};

/**
 * Update an existing rule.
 *
 * @param {number} id - The rule ID.
 * @param {Object} data - The rule data.
 * @return {Promise<Object>} The update response.
 */
export const updateRule = async (id, data) => {
	const response = await apiFetch({
		path: `/${getApiNamespace()}/rules/${id}`,
		method: 'PUT',
		data,
	});
	return response;
};

/**
 * Delete a rule.
 *
 * @param {number} id - The rule ID.
 * @return {Promise<Object>} The delete response.
 */
export const deleteRule = async (id) => {
	const response = await apiFetch({
		path: `/${getApiNamespace()}/rules/${id}`,
		method: 'DELETE',
	});
	return response;
};

/**
 * Fetch tags from the API.
 *
 * @return {Promise<Object>} The tags response.
 */
export const fetchTags = async () => {
	const response = await apiFetch({
		path: `/${getApiNamespace()}/tags`,
		method: 'GET',
	});
	return response;
};
