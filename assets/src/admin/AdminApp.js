/**
 * Admin App component.
 *
 * Main component for the TagLock admin interface. Provides tabs for
 * managing TagLock rules and KlickTipp connection settings.
 *
 * @package TagLock
 */

import { useState, useCallback, useMemo, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TabPanel, Notice } from '@wordpress/components';
import {
	useAdminConfig,
	useSettings,
	useRules,
	useTags,
} from './hooks';
import {
	ConnectionTab,
	TagLocksTab,
	RuleModal,
	ProSidebar,
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
		openDuplicateModal,
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
	const [activeTab, setActiveTab] = useState('taglocks');
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
	 * Clear rules notice.
	 */
	const clearRulesNotice = useCallback(() => {
		setRulesNotice(null);
	}, [setRulesNotice]);

	/**
	 * Clear modal notice.
	 */
	const clearModalNotice = useCallback(() => {
		setModalNotice(null);
	}, [setModalNotice]);

	/**
	 * Tab definitions.
	 */
	const tabs = useMemo(() => [
		{ name: 'taglocks', title: __('TagLocks', 'taglock') },
		{ name: 'connection', title: __('KlickTipp Connection', 'taglock') },
	], []);

	/**
	 * Render tab content.
	 *
	 * @param {Object} tab - Current tab object.
	 * @return {JSX.Element} Tab content.
	 */
	const renderTab = useCallback((tab) => {
		const content = tab.name === 'connection' ? (
			<ConnectionTab
				username={username}
				password={password}
				hasPassword={hasPassword}
				isLoading={settingsLoading}
				isSaving={settingsSaving}
				isConnected={isConnected}
				onUsernameChange={setUsername}
				onPasswordChange={setPassword}
				onSave={handleSaveSettings}
			/>
		) : (
			<TagLocksTab
				rules={rules}
				isLoading={rulesLoading || tagsLoading}
				pagination={rulesPagination}
				setPage={setRulesPage}
				onNewRule={handleNewRuleClick}
				onEditRule={openEditModal}
				onDuplicateRule={openDuplicateModal}
				onDeleteRule={deleteRule}
				formatTagList={formatTagList}
				isConnected={isConnected}
				settingsLoading={settingsLoading}
				onGoToConnection={goToConnectionTab}
				notice={rulesNotice}
				clearNotice={clearRulesNotice}
			/>
		);

		return (
			<div className="taglock-admin__layout">
				<div className="taglock-admin__main">
					{content}
				</div>
				<div className="taglock-admin__sidebar">
					<ProSidebar />
				</div>
			</div>
		);
	}, [
		username,
		password,
		hasPassword,
		settingsLoading,
		settingsSaving,
		isConnected,
		setUsername,
		setPassword,
		handleSaveSettings,
		rules,
		rulesLoading,
		tagsLoading,
		rulesPagination,
		setRulesPage,
		handleNewRuleClick,
		openEditModal,
		openDuplicateModal,
		deleteRule,
		formatTagList,
		goToConnectionTab,
		rulesNotice,
		clearRulesNotice,
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
				clearNotice={clearModalNotice}
				tagOptions={tagOptions}
				tagsById={tagsById}
				tagsLoading={tagsLoading}
			/>
		</div>
	);
};

export default AdminApp;
