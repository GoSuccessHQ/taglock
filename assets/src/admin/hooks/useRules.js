/**
 * Rules hook.
 *
 * Manages TagLock rules state and CRUD operations.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	fetchRules,
	createRule as apiCreateRule,
	updateRule as apiUpdateRule,
	deleteRule as apiDeleteRule,
} from '../services/api';

/**
 * Default pagination state.
 */
const DEFAULT_PAGINATION = {
	page: 1,
	per_page: 10,
	total: 0,
	total_pages: 1,
};

/**
 * Default rule form state.
 */
export const DEFAULT_RULE_FORM = {
	name: '',
	is_active: true,
	access_mode: 'tag_any',
	required_tag_ids: [],
	deny_mode: 'message',
	deny_message: '',
	teaser_html: '',
	redirect_post_id: '',
	engagement_tagging_enabled: false,
	engagement_tag_ids: [],
	admin_bypass_enabled: false,
};

/**
 * Normalize an array of IDs to unique positive integers.
 *
 * @param {Array} ids - Array of IDs to normalize.
 * @return {Array<number>} Normalized array of unique positive integers.
 */
export const normalizeIdArray = (ids) => {
	const seen = new Set();
	return (Array.isArray(ids) ? ids : [])
		.map((v) => Number(v))
		.filter((n) => Number.isInteger(n) && n > 0)
		.filter((n) => {
			if (seen.has(n)) {
				return false;
			}
			seen.add(n);
			return true;
		});
};

/**
 * Hook to manage rules state and operations.
 *
 * @param {number} perPage - Items per page.
 * @return {Object} Rules state and operations.
 */
const useRules = (perPage = 10) => {
	const [rules, setRules] = useState([]);
	const [pagination, setPagination] = useState({ ...DEFAULT_PAGINATION, per_page: perPage });
	const [page, setPage] = useState(1);
	const [isLoading, setIsLoading] = useState(false);
	const [notice, setNotice] = useState(null);

	// Modal state
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [editingRuleId, setEditingRuleId] = useState(null);
	const [isSaving, setIsSaving] = useState(false);
	const [modalNotice, setModalNotice] = useState(null);
	const [ruleForm, setRuleForm] = useState(DEFAULT_RULE_FORM);

	/**
	 * Load rules from API.
	 *
	 * @param {number} pageNum - Page number to load.
	 */
	const loadRules = useCallback(async (pageNum) => {
		setIsLoading(true);
		setNotice(null);

		try {
			const response = await fetchRules(pageNum, perPage);

			if (response?.success && response?.data) {
				setRules(Array.isArray(response.data.items) ? response.data.items : []);
				setPagination(response.data.pagination || { ...DEFAULT_PAGINATION, page: pageNum, per_page: perPage });
			} else {
				setRules([]);
				setPagination({ ...DEFAULT_PAGINATION, page: pageNum, per_page: perPage });
			}
		} catch (error) {
			setRules([]);
			setPagination({ ...DEFAULT_PAGINATION, page: pageNum, per_page: perPage });
			setNotice({
				status: 'error',
				message: error.message || __('Failed to load TagLocks.', 'taglock'),
			});
		} finally {
			setIsLoading(false);
		}
	}, [perPage]);

	/**
	 * Open modal for creating a new rule.
	 */
	const openCreateModal = useCallback(() => {
		setModalNotice(null);
		setEditingRuleId(null);
		setRuleForm(DEFAULT_RULE_FORM);
		setIsModalOpen(true);
	}, []);

	/**
	 * Open modal for editing an existing rule.
	 *
	 * @param {Object} rule - The rule to edit.
	 */
	const openEditModal = useCallback((rule) => {
		setModalNotice(null);
		setEditingRuleId(rule?.id ?? null);
		setRuleForm({
			name: rule?.name || '',
			is_active: Boolean(rule?.is_active),
			access_mode: rule?.access_mode || 'tag_any',
			required_tag_ids: normalizeIdArray(rule?.required_tag_ids),
			deny_mode: rule?.deny_mode || 'message',
			deny_message: rule?.deny_message || '',
			teaser_html: rule?.teaser_html || '',
			redirect_post_id:
				rule?.redirect_post_id && Number(rule.redirect_post_id) > 0
					? String(rule.redirect_post_id)
					: '',
			engagement_tagging_enabled: Boolean(rule?.engagement_tagging_enabled),
			engagement_tag_ids: normalizeIdArray(rule?.engagement_tag_ids),
			admin_bypass_enabled: Boolean(rule?.admin_bypass_enabled),
		});
		setIsModalOpen(true);
	}, []);

	/**
	 * Open modal for duplicating an existing rule.
	 * Creates a new rule with the same settings but a modified name.
	 *
	 * @param {Object} rule - The rule to duplicate.
	 */
	const openDuplicateModal = useCallback((rule) => {
		setModalNotice(null);
		setEditingRuleId(null); // null = create mode
		setRuleForm({
			name: rule?.name ? `${rule.name} (Copy)` : '',
			is_active: Boolean(rule?.is_active),
			access_mode: rule?.access_mode || 'tag_any',
			required_tag_ids: normalizeIdArray(rule?.required_tag_ids),
			deny_mode: rule?.deny_mode || 'message',
			deny_message: rule?.deny_message || '',
			teaser_html: rule?.teaser_html || '',
			redirect_post_id:
				rule?.redirect_post_id && Number(rule.redirect_post_id) > 0
					? String(rule.redirect_post_id)
					: '',
			engagement_tagging_enabled: Boolean(rule?.engagement_tagging_enabled),
			engagement_tag_ids: normalizeIdArray(rule?.engagement_tag_ids),
			admin_bypass_enabled: Boolean(rule?.admin_bypass_enabled),
		});
		setIsModalOpen(true);
	}, []);

	/**
	 * Close the rule modal.
	 */
	const closeModal = useCallback(() => {
		if (isSaving) {
			return;
		}
		setIsModalOpen(false);
		setEditingRuleId(null);
		setModalNotice(null);
	}, [isSaving]);

	/**
	 * Update a field in the rule form.
	 *
	 * @param {string} field - Field name.
	 * @param {*} value - Field value.
	 */
	const updateFormField = useCallback((field, value) => {
		setRuleForm((prev) => ({ ...prev, [field]: value }));
	}, []);

	/**
	 * Save the current rule (create or update).
	 */
	const saveRule = useCallback(async () => {
		setModalNotice(null);
		const name = (ruleForm.name || '').trim();
		const requiredTagIds = normalizeIdArray(ruleForm.required_tag_ids);

		if (!name) {
			setModalNotice({
				status: 'error',
				message: __('Please enter a name.', 'taglock'),
			});
			return;
		}

		if (requiredTagIds.length === 0) {
			setModalNotice({
				status: 'error',
				message: __('Please enter at least one required tag ID.', 'taglock'),
			});
			return;
		}

		const payload = {
			name,
			is_active: Boolean(ruleForm.is_active),
			access_mode: ruleForm.access_mode,
			required_tag_ids: requiredTagIds,
			deny_mode: ruleForm.deny_mode,
			deny_message: ruleForm.deny_message || '',
			teaser_html: ruleForm.teaser_html || '',
			redirect_post_id: ruleForm.redirect_post_id
				? Number(ruleForm.redirect_post_id)
				: null,
			engagement_tagging_enabled: Boolean(ruleForm.engagement_tagging_enabled),
			engagement_tag_ids: normalizeIdArray(ruleForm.engagement_tag_ids),
			admin_bypass_enabled: Boolean(ruleForm.admin_bypass_enabled),
		};

		setIsSaving(true);
		try {
			if (editingRuleId) {
				await apiUpdateRule(editingRuleId, payload);
			} else {
				await apiCreateRule(payload);
			}

			closeModal();
			await loadRules(page);
		} catch (error) {
			setModalNotice({
				status: 'error',
				message: error.message || __('Failed to save TagLock.', 'taglock'),
			});
		} finally {
			setIsSaving(false);
		}
	}, [ruleForm, editingRuleId, closeModal, loadRules, page]);

	/**
	 * Delete a rule.
	 *
	 * @param {Object} rule - The rule to delete.
	 */
	const removeRule = useCallback(async (rule) => {
		setNotice(null);
		const id = rule?.id;
		if (!id) {
			return;
		}
		// eslint-disable-next-line no-alert
		if (!window.confirm(__('Delete this TagLock? This cannot be undone.', 'taglock'))) {
			return;
		}
		try {
			await apiDeleteRule(id);
			await loadRules(page);
		} catch (error) {
			setNotice({
				status: 'error',
				message: error.message || __('Failed to delete TagLock.', 'taglock'),
			});
		}
	}, [loadRules, page]);

	/**
	 * Clear the notice.
	 */
	const clearNotice = useCallback(() => {
		setNotice(null);
	}, []);

	/**
	 * Clear the modal notice.
	 */
	const clearModalNotice = useCallback(() => {
		setModalNotice(null);
	}, []);

	/**
	 * Go to previous page.
	 */
	const prevPage = useCallback(() => {
		setPage((p) => Math.max(1, p - 1));
	}, []);

	/**
	 * Go to next page.
	 */
	const nextPage = useCallback(() => {
		setPage((p) => Math.min(pagination.total_pages || 1, p + 1));
	}, [pagination.total_pages]);

	// Load rules when page changes
	useEffect(() => {
		loadRules(page);
	}, [page, loadRules]);

	return {
		// State
		rules,
		pagination,
		page,
		isLoading,
		notice,
		setNotice,
		
		// Modal state
		isModalOpen,
		editingRuleId,
		isSaving,
		modalNotice,
		setModalNotice,
		ruleForm,
		
		// Actions
		loadRules,
		reloadRules: loadRules,
		openCreateModal,
		openEditModal,
		openDuplicateModal,
		closeModal,
		saveRule,
		removeRule,
		deleteRule: removeRule,
		updateFormField,
		setRuleForm,
		clearNotice,
		clearModalNotice,
		prevPage,
		nextPage,
		setPage,
	};
};

export default useRules;
