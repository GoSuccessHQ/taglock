/**
 * Tag Picker component.
 *
 * A reusable component for selecting KlickTipp tags with a combobox
 * and displaying selected tags with remove buttons.
 */

import { useState, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ComboboxControl, Button } from '@wordpress/components';
import PropTypes from 'prop-types';
import { normalizeIdArray } from '../hooks';

/**
 * Tag Picker component.
 *
 * @param {Object} props - Component props.
 * @param {string} props.label - Label for the combobox.
 * @param {Array<number>} props.selectedIds - Currently selected tag IDs.
 * @param {Function} props.onChange - Callback when selection changes.
 * @param {Array<{value: string, label: string}>} props.tagOptions - Available tag options.
 * @param {Object<string, string>} props.tagsById - Map of tag IDs to names.
 * @param {boolean} [props.isLoading=false] - Whether tags are loading.
 * @param {boolean} [props.disabled=false] - Whether the picker is disabled.
 * @param {string} [props.help] - Help text for the combobox.
 * @param {JSX.Element} [props.labelSuffix] - Element to append to the label (e.g., ProBadge).
 * @return {JSX.Element} The tag picker.
 */
const TagPicker = ({
	label,
	selectedIds,
	onChange,
	tagOptions,
	tagsById,
	isLoading = false,
	disabled = false,
	help,
	labelSuffix,
}) => {
	const [pickerValue, setPickerValue] = useState('');

	/**
	 * Handle tag selection from combobox.
	 */
	const handleSelect = useCallback((value) => {
		setPickerValue(value || '');
		const id = Number(value);
		if (!Number.isInteger(id) || id <= 0) {
			return;
		}
		const newIds = normalizeIdArray([...selectedIds, id]);
		onChange(newIds);
		setPickerValue('');
	}, [selectedIds, onChange]);

	/**
	 * Handle tag removal.
	 *
	 * @param {number} idToRemove - ID of tag to remove.
	 */
	const handleRemove = useCallback((idToRemove) => {
		const newIds = selectedIds.filter((id) => id !== idToRemove);
		onChange(newIds);
	}, [selectedIds, onChange]);

	/**
	 * Get label for a tag by ID.
	 *
	 * @param {number} id - Tag ID.
	 * @return {string} Tag label.
	 */
	const getTagLabel = useCallback((id) => {
		const name = tagsById[String(id)];
		return name ? `${name} (#${id})` : `#${id}`;
	}, [tagsById]);

	/**
	 * Computed label element.
	 */
	const labelElement = useMemo(() => {
		if (!labelSuffix) {
			return label;
		}
		return (
			<span className="taglock-admin__label-with-badge">
				{label}
				{labelSuffix}
			</span>
		);
	}, [label, labelSuffix]);

	/**
	 * Computed help text.
	 */
	const helpText = useMemo(() => {
		if (help) {
			return help;
		}
		return isLoading
			? __('Loading tags…', 'taglock')
			: __('Select a KlickTipp tag by name to add it.', 'taglock');
	}, [help, isLoading]);

	return (
		<>
			<ComboboxControl
				label={labelElement}
				value={pickerValue}
				onChange={handleSelect}
				options={tagOptions}
				help={helpText}
				disabled={disabled}
			/>

			{selectedIds.length > 0 && (
				<div className="taglock-admin__selected-tags">
					{selectedIds.map((id) => (
						<div key={`tag-${id}`} className="taglock-admin__selected-tag">
							<span>{getTagLabel(id)}</span>
							<Button
								variant="secondary"
								isSmall
								disabled={disabled}
								onClick={() => handleRemove(id)}
							>
								{__('Remove', 'taglock')}
							</Button>
						</div>
					))}
				</div>
			)}
		</>
	);
};

TagPicker.propTypes = {
	label: PropTypes.string.isRequired,
	selectedIds: PropTypes.arrayOf(PropTypes.number).isRequired,
	onChange: PropTypes.func.isRequired,
	tagOptions: PropTypes.arrayOf(
		PropTypes.shape({
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		})
	).isRequired,
	tagsById: PropTypes.objectOf(PropTypes.string).isRequired,
	isLoading: PropTypes.bool,
	disabled: PropTypes.bool,
	help: PropTypes.string,
	labelSuffix: PropTypes.node,
};

export default TagPicker;
