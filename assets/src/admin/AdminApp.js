/**
 * Admin App component.
 *
 * Main component for the TagLock admin interface. Provides tabs for
 * managing TagLocker rules and KlickTipp connection settings.
 *
 * @package TagLock
 */

import { useState, useCallback, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	TextControl,
	Button,
	TabPanel,
	Notice,
	Spinner,
	Disabled,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import {
	useAdminConfig,
	useSettings,
	useRules,
	useTags,
} from './hooks';
import {
	TagLockersTab,
	RuleModal,
} from './components';

/**
 * Admin App component.
 *
 * @return {JSX.Element} The admin app.
 */
const AdminApp = () => {
	const config = useAdminConfig();
	const {
		username,
		password,
		hasPassword,
		isLoading: settingsLoading,
		isSaving: settingsSaving,
		isConnected,
		notice: settingsNotice,
		setUsername,
		setPassword,
		saveSettings,
		clearNotice: clearSettingsNotice,
		refreshSettings,
	} = useSettings();

	const {
		rules,
		isLoading: rulesLoading,
		pagination: rulesPagination,
		setPage: setRulesPage,
		notice: rulesNotice,
		setNotice: setRulesNotice,
		isModalOpen,
		editingRuleId,
		ruleForm,
		setRuleForm,
		modalNotice,
		setModalNotice,
		isSaving: ruleSaving,
		openCreateModal,
		openEditModal,
		closeModal,
		saveRule,
		deleteRule,
		reloadRules,
	} = useRules();

	const {
		tagOptions,
		tagsById,
		isLoading: tagsLoading,
		loadTags,
		formatTagList,
	} = useTags();

	// Local state for UI.
	const [activeTab, setActiveTab] = useState('taglockers');
	const [tabPanelKey, setTabPanelKey] = useState(0);

	// Load tags when settings are loaded and connected.
	useEffect(() => {
		if (!settingsLoading && isConnected) {
			loadTags();
		}
	}, [settingsLoading, isConnected, loadTags]);

	/**
	 * Handle saving settings and refreshing connection.
	 */
	const handleSaveSettings = useCallback(async () => {
		await saveSettings(async () => {
			await loadTags();
		});
	}, [saveSettings, loadTags]);

	/**
	 * Handle form submission.
	 *
	 * @param {Event} event - Form submit event.
	 */
	const handleSubmit = useCallback((event) => {
		event.preventDefault();
		if (settingsLoading || settingsSaving) {
			return;
		}
		handleSaveSettings();
	}, [settingsLoading, settingsSaving, handleSaveSettings]);

	/**
	 * Handle new rule click.
	 */
	const handleNewRuleClick = useCallback(() => {
		if (!isConnected || settingsLoading) {
			return;
		}
		openCreateModal();
	}, [isConnected, settingsLoading, openCreateModal]);

	/**
	 * Navigate to connection tab.
	 */
	const goToConnectionTab = useCallback(() => {
		setActiveTab('connection');
		setTabPanelKey((key) => key + 1);
	}, []);

	/**
	 * Connection status badge text.
	 */
	const connectionBadgeText = useMemo(() => {
		return isConnected
			? __('Connected', 'taglock')
			: __('Disconnected', 'taglock');
	}, [isConnected]);

	/**
	 * Tab definitions.
	 */
	const tabs = useMemo(() => [
		{ name: 'taglockers', title: __('TagLockers', 'taglock') },
		{ name: 'connection', title: __('KlickTipp Connection', 'taglock') },
	], []);

	/**
	 * Render tab content.
	 *
	 * @param {Object} tab - Current tab object.
	 * @return {JSX.Element} Tab content.
	 */
	const renderTab = useCallback((tab) => {
		if (tab.name === 'connection') {
			return (
				<Card>
					<CardHeader>
						<div className="taglock-admin__card-header">
							<h2 className="taglock-admin__card-header-title">
								{__('KlickTipp Connection', 'taglock')}
							</h2>
							<div className="taglock-admin__card-header-indicator">
								{settingsLoading ? (
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
								{__(
									'Enter your KlickTipp username and password to connect.',
									'taglock'
								)}
							</p>

							<Disabled isDisabled={settingsLoading}>
								<div className="taglock-admin__credentials">
									<TextControl
										label={__('Username', 'taglock')}
										value={username}
										onChange={setUsername}
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
										onChange={setPassword}
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
												? __(
													'Password is already saved. Enter a new one only if you want to change it.',
													'taglock'
												)
												: __(
													'For security reasons, the password is not displayed after saving.',
													'taglock'
												)
										}
										required={!hasPassword}
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
								</div>

								<Button
									variant="primary"
									type="submit"
									isBusy={settingsSaving}
									disabled={settingsSaving}
								>
									{__('Save Settings', 'taglock')}
								</Button>
							</Disabled>
						</form>
					</CardBody>
				</Card>
			);
		}

		return (
			<TagLockersTab
				rules={rules}
				isLoading={rulesLoading}
				pagination={rulesPagination}
				setPage={setRulesPage}
				onNewRule={handleNewRuleClick}
				onEditRule={openEditModal}
				onDeleteRule={deleteRule}
				formatTagList={formatTagList}
				isConnected={isConnected}
				settingsLoading={settingsLoading}
				onGoToConnection={goToConnectionTab}
				notice={rulesNotice}
				clearNotice={() => setRulesNotice(null)}
			/>
		);
	}, [
		settingsLoading,
		settingsSaving,
		isConnected,
		connectionBadgeText,
		username,
		password,
		hasPassword,
		setUsername,
		setPassword,
		handleSubmit,
		rules,
		rulesLoading,
		rulesPagination,
		setRulesPage,
		handleNewRuleClick,
		openEditModal,
		deleteRule,
		formatTagList,
		goToConnectionTab,
		rulesNotice,
		setRulesNotice,
	]);

	return (
		<div className="wrap">
			<h1 className="taglock-admin__page-title">
				<span
					className="dashicons dashicons-lock taglock-admin__page-title-icon"
					aria-hidden="true"
				/>
				<span>TagLock</span>
			</h1>

			<div className="taglock-admin">
				{settingsNotice && (
					<Notice
						className="taglock-admin__notice"
						status={settingsNotice.status}
						isDismissible
						onRemove={clearSettingsNotice}
					>
						{settingsNotice.message}
					</Notice>
				)}

				<TabPanel
					key={tabPanelKey}
					className="taglock-admin__tabs"
					initialTabName={activeTab}
					onSelect={setActiveTab}
					tabs={tabs}
				>
					{renderTab}
				</TabPanel>
			</div>

			<RuleModal
				isOpen={isModalOpen}
				onClose={closeModal}
				onSave={saveRule}
				ruleForm={ruleForm}
				setRuleForm={setRuleForm}
				editingRuleId={editingRuleId}
				isSaving={ruleSaving}
				notice={modalNotice}
				clearNotice={() => setModalNotice(null)}
				tagOptions={tagOptions}
				tagsById={tagsById}
				tagsLoading={tagsLoading}
				isPro={config.isPro}
				upgradeUrl={config.proUrl}
			/>
		</div>
	);
};

export default AdminApp;
