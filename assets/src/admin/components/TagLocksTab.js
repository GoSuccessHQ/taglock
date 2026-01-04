/**
 * TagLocks Tab component.
 *
 * Displays the list of TagLock rules with pagination and actions.
 */

import { useCallback, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * TagLocks Tab component.
 *
 * @param {Object} props - Component props.
 * @param {Array<Object>} props.rules - List of rules.
 * @param {boolean} props.isLoading - Whether rules are loading.
 * @param {Object} props.pagination - Pagination state.
 * @param {Function} props.setPage - Callback to set current page.
 * @param {Function} props.onNewRule - Callback to create new rule.
 * @param {Function} props.onEditRule - Callback to edit a rule.
 * @param {Function} props.onDeleteRule - Callback to delete a rule.
 * @param {Function} props.onDuplicateRule - Callback to duplicate a rule.
 * @param {Function} props.formatTagList - Function to format tag list.
 * @param {boolean} props.isConnected - Whether connected to KlickTipp.
 * @param {boolean} props.settingsLoading - Whether settings are loading.
 * @param {Function} props.onGoToConnection - Callback to navigate to connection tab.
 * @param {Object|null} props.notice - Notice to display.
 * @param {Function} props.clearNotice - Callback to clear notice.
 * @return {JSX.Element} The TagLocks tab.
 */
const TagLocksTab = ({
	rules,
	isLoading,
	pagination,
	setPage,
	onNewRule,
	onEditRule,
	onDeleteRule,
	onDuplicateRule,
	formatTagList,
	isConnected,
	settingsLoading,
	onGoToConnection,
	notice,
	clearNotice,
}) => {
	/**
	 * Handle previous page click.
	 */
	const handlePrevPage = useCallback(() => {
		setPage(Math.max(1, pagination.page - 1));
	}, [pagination.page, setPage]);

	/**
	 * Handle next page click.
	 */
	const handleNextPage = useCallback(() => {
		setPage(Math.min(pagination.total_pages || 1, pagination.page + 1));
	}, [pagination.page, pagination.total_pages, setPage]);

	/**
	 * Check if previous button should be disabled.
	 */
	const isPrevDisabled = useMemo(() => {
		return isLoading || pagination.page <= 1;
	}, [isLoading, pagination.page]);

	/**
	 * Check if next button should be disabled.
	 */
	const isNextDisabled = useMemo(() => {
		return isLoading || pagination.page >= (pagination.total_pages || 1);
	}, [isLoading, pagination.page, pagination.total_pages]);

	/**
	 * Pagination label.
	 */
	const paginationLabel = useMemo(() => {
		return sprintf(
			__('Page %d of %d', 'taglock'),
			pagination.page,
			pagination.total_pages || 1
		);
	}, [pagination.page, pagination.total_pages]);

	/**
	 * Copy shortcode to clipboard.
	 *
	 * @param {number} ruleId - The rule ID.
	 */
	const copyShortcode = useCallback((ruleId) => {
		const placeholder = __('Your content here', 'taglock');
		const shortcode = `[taglock id="${ruleId}"]${placeholder}[/taglock]`;
		navigator.clipboard.writeText(shortcode);
	}, []);

	return (
		<Card>
			<CardHeader>
				<div className="taglock-admin__card-header">
					<h2 className="taglock-admin__card-header-title">
						{__('TagLocks', 'taglock')}
					</h2>
					<Button
						variant="primary"
						onClick={onNewRule}
						disabled={settingsLoading || !isConnected}
					>
						{__('New TagLock', 'taglock')}
					</Button>
				</div>
			</CardHeader>
			<CardBody>
				{!settingsLoading && !isConnected && (
					<Notice
						className="taglock-admin__notice"
						status="warning"
						isDismissible={false}
					>
						<strong>{__('No KlickTipp connection', 'taglock')}</strong>
						<div>
							{__(
								'Please save valid credentials first. While disconnected, you cannot create new TagLocks.',
								'taglock'
							)}
						</div>
						<Button variant="link" onClick={onGoToConnection}>
							{__('Go to KlickTipp Connection', 'taglock')}
						</Button>
					</Notice>
				)}

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

				{isLoading ? (
					<Spinner />
				) : (
					<table className="wp-list-table widefat fixed striped table-view-list taglocks">
						<thead>
							<tr>
								<th
									scope="col"
									className="manage-column column-id"
								>
									{__('ID', 'taglock')}
								</th>
								<th
									scope="col"
									className="manage-column column-name column-primary"
								>
									{__('Name', 'taglock')}
								</th>
								<th scope="col" className="manage-column column-active">
									{__('Active', 'taglock')}
								</th>
								<th scope="col" className="manage-column column-tags">
									{__('Required Tags', 'taglock')}
								</th>
							</tr>
						</thead>
						<tbody>
							{rules.length === 0 ? (
								<tr>
									<td colSpan="4">
										{__('No TagLocks found.', 'taglock')}
									</td>
								</tr>
							) : (
								rules.map((rule) => (
									<tr key={rule.id}>
										<td
											className="column-id"
											data-colname={__('ID', 'taglock')}
										>
											{rule.id}
										</td>
										<td
											className="column-name column-primary has-row-actions"
											data-colname={__('Name', 'taglock')}
										>
											<strong>{rule.name}</strong>
											<div className="row-actions">
												<span className="edit">
													<a
														href="#"
														onClick={(e) => {
															e.preventDefault();
															onEditRule(rule);
														}}
													>
														{__('Edit', 'taglock')}
													</a>{' '}
													|{' '}
												</span>
												<span className="duplicate">
													<a
														href="#"
														onClick={(e) => {
															e.preventDefault();
															onDuplicateRule(rule);
														}}
													>
														{__('Duplicate', 'taglock')}
													</a>{' '}
													|{' '}
												</span>
												<span className="copy">
													<a
														href="#"
														onClick={(e) => {
															e.preventDefault();
															copyShortcode(rule.id);
														}}
													>
														{__('Copy Shortcode', 'taglock')}
													</a>{' '}
													|{' '}
												</span>
												<span className="delete">
													<a
														href="#"
														onClick={(e) => {
															e.preventDefault();
															onDeleteRule(rule);
														}}
													>
														{__('Delete', 'taglock')}
													</a>
												</span>
											</div>
											<button
												type="button"
												className="toggle-row"
											>
												<span className="screen-reader-text">
													{__('Show more details', 'taglock')}
												</span>
											</button>
										</td>
										<td
											className="column-active"
											data-colname={__('Active', 'taglock')}
										>
											{rule.is_active
												? __('Yes', 'taglock')
												: __('No', 'taglock')}
										</td>
										<td
											className="column-tags"
											data-colname={__('Required Tags', 'taglock')}
										>
											{formatTagList(rule.required_tag_ids)}
										</td>
									</tr>
								))
							)}
						</tbody>
					</table>
				)}

				<div className="taglock-admin__pagination">
					<Button
						variant="secondary"
						disabled={isPrevDisabled}
						onClick={handlePrevPage}
					>
						{__('Previous', 'taglock')}
					</Button>
					<span className="taglock-admin__pagination-label">
						{paginationLabel}
					</span>
					<Button
						variant="secondary"
						disabled={isNextDisabled}
						onClick={handleNextPage}
					>
						{__('Next', 'taglock')}
					</Button>
				</div>
			</CardBody>
		</Card>
	);
};

TagLocksTab.propTypes = {
	rules: PropTypes.arrayOf(
		PropTypes.shape({
			id: PropTypes.number.isRequired,
			name: PropTypes.string.isRequired,
			is_active: PropTypes.bool.isRequired,
			required_tag_ids: PropTypes.arrayOf(PropTypes.number).isRequired,
		})
	).isRequired,
	isLoading: PropTypes.bool.isRequired,
	pagination: PropTypes.shape({
		page: PropTypes.number.isRequired,
		per_page: PropTypes.number.isRequired,
		total: PropTypes.number.isRequired,
		total_pages: PropTypes.number.isRequired,
	}).isRequired,
	setPage: PropTypes.func.isRequired,
	onNewRule: PropTypes.func.isRequired,
	onEditRule: PropTypes.func.isRequired,
	onDuplicateRule: PropTypes.func.isRequired,
	onDeleteRule: PropTypes.func.isRequired,
	formatTagList: PropTypes.func.isRequired,
	isConnected: PropTypes.bool.isRequired,
	settingsLoading: PropTypes.bool.isRequired,
	onGoToConnection: PropTypes.func.isRequired,
	notice: PropTypes.shape({
		status: PropTypes.oneOf(['success', 'error', 'warning', 'info']).isRequired,
		message: PropTypes.string.isRequired,
	}),
	clearNotice: PropTypes.func.isRequired,
};

export default TagLocksTab;
