import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	TextControl,
	TextareaControl,
	ToggleControl,
	SelectControl,
	Button,
	Notice,
	Spinner,
	Modal,
	Disabled,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const getAdminConfig = () => {
	if (typeof window === 'undefined') {
		return null;
	}
	return window.taglockAdminConfig || null;
};

const AdminApp = () => {
	const adminConfig = getAdminConfig();
	const apiNamespace = adminConfig?.apiNamespace || 'taglock/v1';
	const proUrl = adminConfig?.proUrl || 'https://gosuccess.io/taglock';
	const isPro = Boolean(adminConfig?.isPro);
	const isProDisabled = !isPro;
	const proBadge = (
		<a
			className="taglock-admin__pro-badge taglock-admin__pro-badge--inline"
			href={proUrl}
			target="_blank"
			rel="noopener noreferrer"
		>
			PRO
		</a>
	);

	const [username, setUsername] = useState('');
	const [password, setPassword] = useState('');
	const [hasPassword, setHasPassword] = useState(null);
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	const perPage = 10;
	const [rules, setRules] = useState([]);
	const [rulesPagination, setRulesPagination] = useState({
		page: 1,
		per_page: perPage,
		total: 0,
		total_pages: 1,
	});
	const [rulesPage, setRulesPage] = useState(1);
	const [rulesLoading, setRulesLoading] = useState(false);
	const [rulesNotice, setRulesNotice] = useState(null);

	const [isRuleModalOpen, setIsRuleModalOpen] = useState(false);
	const [editingRuleId, setEditingRuleId] = useState(null);
	const [isRuleSaving, setIsRuleSaving] = useState(false);
	const [ruleModalNotice, setRuleModalNotice] = useState(null);
	const [ruleForm, setRuleForm] = useState({
		name: '',
		is_active: true,
		access_mode: 'tag_any',
		required_tag_ids: '',
		deny_mode: 'message',
		deny_message: '',
		teaser_html: '',
		redirect_post_id: '',
		engagement_tagging_enabled: false,
		engagement_tag_ids: '',
		admin_bypass_enabled: false,
	});

	useEffect(() => {
		let isMounted = true;

		const loadSettings = async () => {
			try {
				const response = await apiFetch({
					path: `/${apiNamespace}/settings`,
					method: 'GET',
				});

				if (isMounted) {
					if (response?.success && response?.data) {
						setUsername(response.data.klicktipp_username || '');
						setHasPassword(Boolean(response.data.has_password));
					} else {
						setHasPassword(false);
					}
					setIsLoading(false);
				}
			} catch (error) {
				if (!isMounted) {
					return;
				}
				setHasPassword(false);
				setNotice({
					status: 'error',
					message: error.message || __('Failed to load settings.', 'taglock'),
				});
				setIsLoading(false);
			}
		};

		loadSettings();

		return () => {
			isMounted = false;
		};
	}, []);

	const loadRules = async (page) => {
		setRulesLoading(true);
		setRulesNotice(null);
		try {
			const response = await apiFetch({
				path: `/${apiNamespace}/rules?page=${page}&per_page=${perPage}`,
				method: 'GET',
			});

			if (response?.success && response?.data) {
				setRules(Array.isArray(response.data.items) ? response.data.items : []);
				setRulesPagination(response.data.pagination || rulesPagination);
			} else {
				setRules([]);
				setRulesPagination({ page, per_page: perPage, total: 0, total_pages: 1 });
			}
		} catch (error) {
			setRules([]);
			setRulesPagination({ page, per_page: perPage, total: 0, total_pages: 1 });
			setRulesNotice({
				status: 'error',
				message: error.message || __('Failed to load TagLockers.', 'taglock'),
			});
		} finally {
			setRulesLoading(false);
		}
	};

	useEffect(() => {
		loadRules(rulesPage);
	}, [rulesPage]);

	const parseIdList = (value) => {
		if (!value) {
			return [];
		}
		return value
			.split(',')
			.map((part) => part.trim())
			.filter(Boolean)
			.map((part) => Number(part))
			.filter((n) => Number.isInteger(n) && n > 0);
	};

	const openCreateRuleModal = () => {
		setRuleModalNotice(null);
		setEditingRuleId(null);
		setRuleForm({
			name: '',
			is_active: true,
			access_mode: 'tag_any',
			required_tag_ids: '',
			deny_mode: 'message',
			deny_message: '',
			teaser_html: '',
			redirect_post_id: '',
			engagement_tagging_enabled: false,
			engagement_tag_ids: '',
			admin_bypass_enabled: false,
		});
		setIsRuleModalOpen(true);
	};

	const openEditRuleModal = (rule) => {
		setRuleModalNotice(null);
		setEditingRuleId(rule?.id ?? null);
		setRuleForm({
			name: rule?.name || '',
			is_active: Boolean(rule?.is_active),
			access_mode: rule?.access_mode || 'tag_any',
			required_tag_ids: Array.isArray(rule?.required_tag_ids)
				? rule.required_tag_ids.join(', ')
				: '',
			deny_mode: rule?.deny_mode || 'message',
			deny_message: rule?.deny_message || '',
			teaser_html: rule?.teaser_html || '',
			redirect_post_id:
				rule?.redirect_post_id && Number(rule.redirect_post_id) > 0
					? String(rule.redirect_post_id)
					: '',
			engagement_tagging_enabled: Boolean(rule?.engagement_tagging_enabled),
			engagement_tag_ids: Array.isArray(rule?.engagement_tag_ids)
				? rule.engagement_tag_ids.join(', ')
				: '',
			admin_bypass_enabled: Boolean(rule?.admin_bypass_enabled),
		});
		setIsRuleModalOpen(true);
	};

	const closeRuleModal = () => {
		if (isRuleSaving) {
			return;
		}
		setIsRuleModalOpen(false);
		setEditingRuleId(null);
		setRuleModalNotice(null);
	};

	const saveRule = async () => {
		setRuleModalNotice(null);
		const name = (ruleForm.name || '').trim();
		const requiredTagIds = parseIdList(ruleForm.required_tag_ids);

		if (!name) {
			setRuleModalNotice({
				status: 'error',
				message: __('Please enter a name.', 'taglock'),
			});
			return;
		}

		if (requiredTagIds.length === 0) {
			setRuleModalNotice({
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
			engagement_tag_ids: parseIdList(ruleForm.engagement_tag_ids),
			admin_bypass_enabled: Boolean(ruleForm.admin_bypass_enabled),
		};

		setIsRuleSaving(true);
		try {
			if (editingRuleId) {
				await apiFetch({
					path: `/${apiNamespace}/rules/${editingRuleId}`,
					method: 'PUT',
					data: payload,
				});
			} else {
				await apiFetch({
					path: `/${apiNamespace}/rules`,
					method: 'POST',
					data: payload,
				});
			}

			closeRuleModal();
			await loadRules(rulesPage);
		} catch (error) {
			setRuleModalNotice({
				status: 'error',
				message: error.message || __('Failed to save TagLocker.', 'taglock'),
			});
		} finally {
			setIsRuleSaving(false);
		}
	};

	const deleteRule = async (rule) => {
		setRulesNotice(null);
		const id = rule?.id;
		if (!id) {
			return;
		}
		// eslint-disable-next-line no-alert
		if (!window.confirm(__('Delete this TagLocker? This cannot be undone.', 'taglock'))) {
			return;
		}
		try {
			await apiFetch({
				path: `/${apiNamespace}/rules/${id}`,
				method: 'DELETE',
			});
			await loadRules(rulesPage);
		} catch (error) {
			setRulesNotice({
				status: 'error',
				message: error.message || __('Failed to delete TagLocker.', 'taglock'),
			});
		}
	};

	const handleSave = async () => {
		setIsSaving(true);
		setNotice(null);

		try {
			const data = {
				klicktipp_username: username,
			};
			if (password) {
				data.klicktipp_password = password;
			}

			await apiFetch({
				path: `/${apiNamespace}/settings`,
				method: 'POST',
				data,
			});

			setPassword('');
			if (password) {
				setHasPassword(true);
			}

			setNotice({
				status: 'success',
				message: __('Settings saved successfully!', 'taglock'),
			});
		} catch (error) {
			setNotice({
				status: 'error',
				message: error.message || __('Failed to save settings.', 'taglock'),
			});
		} finally {
			setIsSaving(false);
		}
	};

	const handleSubmit = (event) => {
		event.preventDefault();
		if (isLoading || isSaving) {
			return;
		}
		handleSave();
	};

	return (
		<div className="wrap">
			<h1>{__('TagLock Settings', 'taglock')}</h1>
			
			<div className="taglock-admin">
				{notice && (
					<Notice
                        className="taglock-admin__notice"
						status={notice.status}
						isDismissible
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				)}

				<Card>
					<CardHeader>
						<div className="taglock-admin__card-header">
							<h2 className="taglock-admin__card-header-title">{__('KlickTipp Connection', 'taglock')}</h2>
							<div className="taglock-admin__card-header-indicator" aria-hidden="true">
								{isLoading ? <Spinner /> : null}
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

							<Disabled isDisabled={isLoading}>
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
											hasPassword === true
												? __('Saved. Enter a new password to update.', 'taglock')
												: ''
										}
										help={
											hasPassword === true
												? __(
													'Password is already saved. Enter a new one only if you want to change it.',
													'taglock'
												)
											: __(
													'For security reasons, the password is not displayed after saving.',
													'taglock'
												)
									}
									required={hasPassword === false}
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								</div>

								<Button
									variant="primary"
									type="submit"
									isBusy={isSaving}
									disabled={isSaving}
								>
									{__('Save Settings', 'taglock')}
								</Button>
							</Disabled>
						</form>
				</CardBody>
                </Card>

				<Card className="taglock-admin__card taglock-admin__card--spaced">
					<CardHeader>
						<div className="taglock-admin__card-header">
							<h2 className="taglock-admin__card-header-title">{__('TagLockers', 'taglock')}</h2>
							<Button variant="primary" onClick={openCreateRuleModal}>
								{__('New TagLocker', 'taglock')}
							</Button>
						</div>
					</CardHeader>
					<CardBody>
						{rulesNotice && (
							<Notice
								className="taglock-admin__notice"
								status={rulesNotice.status}
								isDismissible
								onRemove={() => setRulesNotice(null)}
							>
								{rulesNotice.message}
							</Notice>
						)}

						{rulesLoading ? (
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
											<td colSpan="5">{__('No TagLockers found.', 'taglock')}</td>
										</tr>
									) : (
										rules.map((rule) => (
											<tr key={rule.id}>
												<td>{rule.id}</td>
												<td>{rule.name}</td>
												<td>{rule.is_active ? __('Yes', 'taglock') : __('No', 'taglock')}</td>
												<td>
													{Array.isArray(rule.required_tag_ids)
														? rule.required_tag_ids.join(', ')
														: ''}
												</td>
												<td>
													<Button
														variant="secondary"
														onClick={() => openEditRuleModal(rule)}
													>
														{__('Edit', 'taglock')}
													</Button>{' '}
													<Button
														variant="tertiary"
														onClick={() => deleteRule(rule)}
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
								disabled={rulesLoading || rulesPagination.page <= 1}
								onClick={() => setRulesPage(Math.max(1, rulesPagination.page - 1))}
							>
								{__('Previous', 'taglock')}
							</Button>
							<span className="taglock-admin__pagination-label">
								{sprintf(
									__('Page %d of %d', 'taglock'),
									rulesPagination.page,
									rulesPagination.total_pages || 1
								)}
							</span>
							<Button
								variant="secondary"
								disabled={
									rulesLoading ||
									rulesPagination.page >= (rulesPagination.total_pages || 1)
								}
								onClick={() =>
									setRulesPage(
										Math.min(
											rulesPagination.total_pages || 1,
											rulesPagination.page + 1
										)
									)
								}
							>
								{__('Next', 'taglock')}
							</Button>
						</div>
					</CardBody>
				</Card>

            </div>

			{isRuleModalOpen && (
				<Modal
					title={
						editingRuleId
							? __('Edit TagLocker', 'taglock')
							: __('New TagLocker', 'taglock')
					}
					className="taglock-admin__modal"
					onRequestClose={closeRuleModal}
				>
					{ruleModalNotice && (
						<Notice
							status={ruleModalNotice.status}
							isDismissible
							onRemove={() => setRuleModalNotice(null)}
						>
							{ruleModalNotice.message}
						</Notice>
					)}

					<TextControl
						label={__('Name', 'taglock')}
						value={ruleForm.name}
						onChange={(value) => setRuleForm({ ...ruleForm, name: value })}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<ToggleControl
						label={__('Active', 'taglock')}
						checked={ruleForm.is_active}
						onChange={(value) =>
							setRuleForm({ ...ruleForm, is_active: value })
						}
						__nextHasNoMarginBottom
					/>

					<SelectControl
						label={__('Access mode', 'taglock')}
						value={ruleForm.access_mode}
						onChange={(value) =>
							setRuleForm({ ...ruleForm, access_mode: value })
						}
						options={[
							{ label: __('Any tag (OR)', 'taglock'), value: 'tag_any' },
							{ label: __('All tags (AND)', 'taglock'), value: 'tag_all' },
						]}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<TextControl
						label={__('Required tag IDs', 'taglock')}
						help={__('Comma-separated KlickTipp tag IDs.', 'taglock')}
						value={ruleForm.required_tag_ids}
						onChange={(value) =>
							setRuleForm({ ...ruleForm, required_tag_ids: value })
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					<SelectControl
						label={
							<span className="taglock-admin__label-with-badge">
								{__('Deny mode', 'taglock')}
								{proBadge}
							</span>
						}
						value={ruleForm.deny_mode}
						onChange={(value) =>
							setRuleForm({ ...ruleForm, deny_mode: value })
						}
						options={[
							{ label: __('Message', 'taglock'), value: 'message' },
							{ label: __('Teaser', 'taglock'), value: 'teaser', disabled: isProDisabled },
							{ label: __('Redirect', 'taglock'), value: 'redirect', disabled: isProDisabled },
						]}
						help={__(
							'Teaser and redirect modes are available in TagLock Pro.',
							'taglock'
						)}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>

					{ruleForm.deny_mode === 'message' && (
						<TextControl
							label={__('Deny message', 'taglock')}
							value={ruleForm.deny_message}
							onChange={(value) =>
								setRuleForm({ ...ruleForm, deny_message: value })
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					)}

					{ruleForm.deny_mode === 'teaser' && (
						<TextareaControl
							disabled={isProDisabled}
							label={
								<span className="taglock-admin__label-with-badge">
									{__('Teaser HTML', 'taglock')}
									{proBadge}
								</span>
							}
							value={ruleForm.teaser_html}
							onChange={(value) =>
								setRuleForm({ ...ruleForm, teaser_html: value })
							}
							help={__(
								'Shortcodes are allowed and will be executed on the server.',
								'taglock'
							)}
							__nextHasNoMarginBottom
						/>
					)}

					{ruleForm.deny_mode === 'redirect' && (
						<TextControl
							disabled={isProDisabled}
							label={
								<span className="taglock-admin__label-with-badge">
									{__('Redirect post ID', 'taglock')}
									{proBadge}
								</span>
							}
							type="number"
							value={ruleForm.redirect_post_id}
							onChange={(value) =>
								setRuleForm({ ...ruleForm, redirect_post_id: value })
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					)}

					<ToggleControl
						disabled={isProDisabled}
						label={
							<span className="taglock-admin__label-with-badge">
								{__('Engagement tagging', 'taglock')}
								{proBadge}
							</span>
						}
						checked={ruleForm.engagement_tagging_enabled}
						onChange={(value) =>
							setRuleForm({
								...ruleForm,
								engagement_tagging_enabled: value,
							})
						}
						__nextHasNoMarginBottom
					/>

					{ruleForm.engagement_tagging_enabled && (
						<TextControl
							disabled={isProDisabled}
							label={
								<span className="taglock-admin__label-with-badge">
									{__('Engagement tag IDs', 'taglock')}
									{proBadge}
								</span>
							}
							help={__('Comma-separated KlickTipp tag IDs.', 'taglock')}
							value={ruleForm.engagement_tag_ids}
							onChange={(value) =>
								setRuleForm({ ...ruleForm, engagement_tag_ids: value })
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					)}

					<ToggleControl
						disabled={isProDisabled}
						label={
							<span className="taglock-admin__label-with-badge">
								{__('Admin bypass (preview without subscriber ID)', 'taglock')}
								{proBadge}
							</span>
						}
						checked={ruleForm.admin_bypass_enabled}
						onChange={(value) =>
							setRuleForm({ ...ruleForm, admin_bypass_enabled: value })
						}
						__nextHasNoMarginBottom
					/>

					<div className="taglock-admin__modal-actions">
						<Button
							variant="secondary"
							onClick={closeRuleModal}
							disabled={isRuleSaving}
						>
							{__('Cancel', 'taglock')}
						</Button>
						<Button
							variant="primary"
							onClick={saveRule}
							isBusy={isRuleSaving}
							disabled={isRuleSaving}
						>
							{editingRuleId ? __('Save', 'taglock') : __('Create', 'taglock')}
						</Button>
					</div>
				</Modal>
			)}
		</div>
	);
};

export default AdminApp;
