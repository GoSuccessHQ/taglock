/**
 * TagLockers Tab component.
 *
 * Displays the list of TagLocker rules with pagination and actions.
 */

import { useCallback, useMemo } from '@wordpress/element';
import { __ ,sprintf } from '@wordpress/i18n';
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
 * TagLockers Tab component.
 *
 * @param {Object} props - Component props.
 * @param {Array<Object>} props.rules - List of rules.
 * @param {boolean} props.isLoading - Whether rules are loading.
 * @param {Object} props.pagination - Pagination state.
 * @param {Function} props.setPage - Callback to set current page.
 * @param {Function} props.onNewRule - Callback to create new rule.
 * @param {Function} props.onEditRule - Callback to edit a rule.
 * @param {Function} props.onDeleteRule - Callback to delete a rule.
 * @param {Function} props.formatTagList - Function to format tag list.
 * @param {boolean} props.isConnected - Whether connected to KlickTipp.
 * @param {boolean} props.settingsLoading - Whether settings are loading.
 * @param {Function} props.onGoToConnection - Callback to navigate to connection tab.
 * @param {Object|null} props.notice - Notice to display.
 * @param {Function} props.clearNotice - Callback to clear notice.
 * @return {JSX.Element} The TagLockers tab.
 */
const TagLockersTab = ({
	rules,
	isLoading,
	pagination,
	setPage,
	onNewRule,
	onEditRule,
	onDeleteRule,
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

	return (
		<Card>
			<CardHeader>
				<div className="taglock-admin__card-header">
					<h2 className="taglock-admin__card-header-title">
						{__('TagLockers', 'taglock')}
					</h2>
					<Button
						variant="primary"
						onClick={onNewRule}
						disabled={settingsLoading || !isConnected}
					>
						{__('New TagLocker', 'taglock')}
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
								'Please save valid credentials first. While disconnected, you cannot create new TagLockers.',
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
					<table className="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th scope="col">{__('ID', 'taglock')}</th>
								<th scope="col">{__('Name', 'taglock')}</th>
								<th scope="col">{__('Active', 'taglock')}</th>
								<th scope="col">{__('Required Tags', 'taglock')}</th>
								<th scope="col">{__('Actions', 'taglock')}</th>
							</tr>
						</thead>
						<tbody>
							{rules.length === 0 ? (
								<tr>
									<td colSpan="5">
										{__('No TagLockers found.', 'taglock')}
									</td>
								</tr>
							) : (
								rules.map((rule) => (
									<tr key={rule.id}>
										<td data-label={__('ID', 'taglock')}>{rule.id}</td>
										<td data-label={__('Name', 'taglock')}>{rule.name}</td>
										<td data-label={__('Active', 'taglock')}>
											{rule.is_active
												? __('Yes', 'taglock')
												: __('No', 'taglock')}
										</td>
										<td data-label={__('Required Tags', 'taglock')}>
											{formatTagList(rule.required_tag_ids)}
										</td>
										<td data-label={__('Actions', 'taglock')}>
											<Button
												variant="secondary"
												onClick={() => onEditRule(rule)}
											>
												{__('Edit', 'taglock')}
											</Button>{' '}
											<Button
												variant="tertiary"
												onClick={() => onDeleteRule(rule)}
											>
												{__('Delete', 'taglock')}
											</Button>
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

TagLockersTab.propTypes = {
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

export default TagLockersTab;
