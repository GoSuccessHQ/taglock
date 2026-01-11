/**
 * Rule Modal component.
 *
 * Modal dialog for creating and editing TagLock rules.
 */

import { useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Modal,
	TextControl,
	TextareaControl,
	ToggleControl,
	SelectControl,
	Button,
	Notice,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import TagPicker from './TagPicker';

/**
 * Rule Modal component.
 *
 * @param {Object} props - Component props.
 * @param {boolean} props.isOpen - Whether modal is open.
 * @param {Function} props.onClose - Callback to close modal.
 * @param {Function} props.onSave - Callback to save rule.
 * @param {Object} props.ruleForm - Current rule form data.
 * @param {Function} props.setRuleForm - Callback to update rule form.
 * @param {number|null} props.editingRuleId - ID of rule being edited (null for new).
 * @param {boolean} props.isSaving - Whether rule is being saved.
 * @param {Object|null} props.notice - Notice to display.
 * @param {Function} props.clearNotice - Callback to clear notice.
 * @param {Array<{value: string, label: string}>} props.tagOptions - Available tag options.
 * @param {Object<string, string>} props.tagsById - Map of tag IDs to names.
 * @param {boolean} props.tagsLoading - Whether tags are loading.
 * @return {JSX.Element|null} The rule modal or null.
 */
const RuleModal = ({
	isOpen,
	onClose,
	onSave,
	ruleForm,
	setRuleForm,
	editingRuleId,
	isSaving,
	notice,
	clearNotice,
	tagOptions,
	tagsById,
	tagsLoading,
}) => {
	/**
	 * Update a single field in the rule form.
	 *
	 * @param {string} field - Field name to update.
	 * @param {*} value - New value for the field.
	 */
	const updateField = useCallback((field, value) => {
		setRuleForm((prev) => ({ ...prev, [field]: value }));
	}, [setRuleForm]);

	/**
	 * Handle required tags change.
	 *
	 * @param {Array<number>} newIds - New required tag IDs.
	 */
	const handleRequiredTagsChange = useCallback((newIds) => {
		updateField('required_tag_ids', newIds);
	}, [updateField]);

	/**
	 * Handle engagement tags change.
	 *
	 * @param {Array<number>} newIds - New engagement tag IDs.
	 */
	const handleEngagementTagsChange = useCallback((newIds) => {
		updateField('engagement_tag_ids', newIds);
	}, [updateField]);

	/**
	 * Handle form submit.
	 *
	 * @param {Event} e - Form submit event.
	 */
	const handleSubmit = useCallback((e) => {
		e.preventDefault();
		if (!isSaving) {
			onSave();
		}
	}, [isSaving, onSave]);

	if (!isOpen) {
		return null;
	}

	const modalTitle = editingRuleId
		? __('Edit TagLock', 'taglock')
		: __('New TagLock', 'taglock');

	return (
		<Modal
			title={modalTitle}
			className="taglock-admin__modal"
			onRequestClose={onClose}
		>
			<form onSubmit={handleSubmit}>
				{notice && (
					<Notice
						className="taglock-admin__notice"
						status={notice.status}
						isDismissible
						onRemove={clearNotice}
					>
						{notice.message}
					</Notice>
				)}

			<TextControl
				label={__('Name', 'taglock')}
				value={ruleForm.name}
				onChange={(value) => updateField('name', value)}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>

			<ToggleControl
				label={__('Active', 'taglock')}
				checked={ruleForm.is_active}
				onChange={(value) => updateField('is_active', value)}
				__nextHasNoMarginBottom
			/>

			<SelectControl
				label={__('Access mode', 'taglock')}
				value={ruleForm.access_mode}
				onChange={(value) => updateField('access_mode', value)}
				options={[
					{ label: __('Any tag (OR)', 'taglock'), value: 'tag_any' },
					{ label: __('All tags (AND)', 'taglock'), value: 'tag_all' },
				]}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>

			<TagPicker
				label={__('Required tags', 'taglock')}
				selectedIds={ruleForm.required_tag_ids}
				onChange={handleRequiredTagsChange}
				tagOptions={tagOptions}
				tagsById={tagsById}
				isLoading={tagsLoading}
			/>

			<SelectControl
				label={__('Deny mode', 'taglock')}
				value={ruleForm.deny_mode}
				onChange={(value) => updateField('deny_mode', value)}
				options={[
					{ label: __('Message', 'taglock'), value: 'message' },
					{ label: __('Teaser', 'taglock'), value: 'teaser' },
					{ label: __('Redirect', 'taglock'), value: 'redirect' },
				]}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>

			{ruleForm.deny_mode === 'message' && (
				<TextControl
					label={__('Deny message', 'taglock')}
					value={ruleForm.deny_message}
					onChange={(value) => updateField('deny_message', value)}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			)}

			{ruleForm.deny_mode === 'teaser' && (
				<TextareaControl
					label={__('Teaser HTML', 'taglock')}
					value={ruleForm.teaser_html}
					onChange={(value) => updateField('teaser_html', value)}
					help={__(
						'Shortcodes are allowed and will be executed on the server.',
						'taglock'
					)}
					__nextHasNoMarginBottom
				/>
			)}

			{ruleForm.deny_mode === 'redirect' && (
				<TextControl
					label={__('Redirect post ID', 'taglock')}
					type="number"
					value={ruleForm.redirect_post_id}
					onChange={(value) => updateField('redirect_post_id', value)}
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			)}

			<ToggleControl
				label={__('Engagement tagging', 'taglock')}
				checked={ruleForm.engagement_tagging_enabled}
				onChange={(value) => updateField('engagement_tagging_enabled', value)}
				__nextHasNoMarginBottom
			/>

			{ruleForm.engagement_tagging_enabled && (
				<TagPicker
					label={__('Engagement tags', 'taglock')}
					selectedIds={ruleForm.engagement_tag_ids}
					onChange={handleEngagementTagsChange}
					tagOptions={tagOptions}
					tagsById={tagsById}
					isLoading={tagsLoading}
				/>
			)}

			<ToggleControl
				label={__('Admin bypass (preview without subscriber ID)', 'taglock')}
				checked={ruleForm.admin_bypass_enabled}
				onChange={(value) => updateField('admin_bypass_enabled', value)}
				__nextHasNoMarginBottom
			/>

			<div className="taglock-admin__modal-actions">
				<Button
					variant="secondary"
					onClick={onClose}
					disabled={isSaving}
				>
					{__('Cancel', 'taglock')}
				</Button>
				<Button
					variant="primary"
					type="submit"
					isBusy={isSaving}
					disabled={isSaving}
				>
					{editingRuleId ? __('Save', 'taglock') : __('Create', 'taglock')}
				</Button>
			</div>
			</form>
		</Modal>
	);
};

RuleModal.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
	ruleForm: PropTypes.shape({
		name: PropTypes.string.isRequired,
		is_active: PropTypes.bool.isRequired,
		access_mode: PropTypes.string.isRequired,
		required_tag_ids: PropTypes.arrayOf(PropTypes.number).isRequired,
		deny_mode: PropTypes.string.isRequired,
		deny_message: PropTypes.string.isRequired,
		teaser_html: PropTypes.string.isRequired,
		redirect_post_id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
		engagement_tagging_enabled: PropTypes.bool.isRequired,
		engagement_tag_ids: PropTypes.arrayOf(PropTypes.number).isRequired,
		admin_bypass_enabled: PropTypes.bool.isRequired,
	}).isRequired,
	setRuleForm: PropTypes.func.isRequired,
	editingRuleId: PropTypes.number,
	isSaving: PropTypes.bool.isRequired,
	notice: PropTypes.shape({
		status: PropTypes.oneOf(['success', 'error', 'warning', 'info']).isRequired,
		message: PropTypes.string.isRequired,
	}),
	clearNotice: PropTypes.func.isRequired,
	tagOptions: PropTypes.arrayOf(
		PropTypes.shape({
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		})
	).isRequired,
	tagsById: PropTypes.objectOf(PropTypes.string).isRequired,
	tagsLoading: PropTypes.bool.isRequired,
};

export default RuleModal;
