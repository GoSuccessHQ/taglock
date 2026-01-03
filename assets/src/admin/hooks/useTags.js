/**
 * Tags hook.
 *
 * Manages KlickTipp tags state and operations.
 */

import { useState, useCallback } from '@wordpress/element';
import { fetchTags } from '../services/api';

/**
 * Hook to manage tags state and operations.
 *
 * @return {Object} Tags state and operations.
 */
const useTags = () => {
	const [isLoading, setIsLoading] = useState(false);
	const [tagOptions, setTagOptions] = useState([]);
	const [tagsById, setTagsById] = useState({});

	/**
	 * Load tags from API.
	 */
	const loadTags = useCallback(async () => {
		setIsLoading(true);
		try {
			const response = await fetchTags();

			if (response?.success && response?.data?.items) {
				const items = Array.isArray(response.data.items)
					? response.data.items
					: [];
				const byId = {};
				const options = items
					.filter((t) => t && t.id && t.name)
					.map((t) => {
						byId[String(t.id)] = String(t.name);
						return { value: String(t.id), label: String(t.name) };
					});

				setTagsById(byId);
				setTagOptions(options);
			} else {
				setTagsById({});
				setTagOptions([]);
			}
		} catch {
			setTagsById({});
			setTagOptions([]);
		} finally {
			setIsLoading(false);
		}
	}, []);

	/**
	 * Format a list of tag IDs as a readable string.
	 *
	 * @param {Array} ids - Array of tag IDs.
	 * @return {string} Formatted tag list.
	 */
	const formatTagList = useCallback((ids) => {
		if (!Array.isArray(ids) || ids.length === 0) {
			return '';
		}
		return ids
			.map((raw) => {
				const id = Number(raw);
				if (!Number.isInteger(id) || id <= 0) {
					return '';
				}
				const name = tagsById[String(id)];
				return name ? `${name} (#${id})` : `#${id}`;
			})
			.filter(Boolean)
			.join(', ');
	}, [tagsById]);

	/**
	 * Get a tag label by ID.
	 *
	 * @param {number|string} id - Tag ID.
	 * @return {string} Tag label.
	 */
	const getTagLabel = useCallback((id) => {
		const name = tagsById[String(id)];
		return name ? `${name} (#${id})` : `#${id}`;
	}, [tagsById]);

	return {
		// State
		isLoading,
		tagOptions,
		tagsById,
		
		// Actions
		loadTags,
		formatTagList,
		getTagLabel,
	};
};

export default useTags;
