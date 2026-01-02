import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	TextControl,
	ToggleControl,
	Button,
	Notice,
	Spinner,
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

	const [username, setUsername] = useState('');
	const [password, setPassword] = useState('');
	const [hasPassword, setHasPassword] = useState(null);
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [notice, setNotice] = useState(null);

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
                        <h2>{__('Usage', 'taglock')}</h2>
                    </CardHeader>
                    <CardBody>
                        <p>
                            {__(
                                'Use the [taglock] shortcode to protect your content:',
                                'taglock'
                            )}
                        </p>
						<pre className="taglock-admin__code">
                            {'[taglock tag="123"]Your protected content here[/taglock]'}
                        </pre>
                        <p className="description">
                            {__(
                                'Replace "123" with your KlickTipp tag ID.',
                                'taglock'
                            )}
                        </p>
                    </CardBody>
                </Card>

				<Card className="taglock-admin__card taglock-admin__card--spaced">
                    <CardHeader>
						<div className="taglock-admin__card-header">
							<h2 className="taglock-admin__card-header-title">{__('Pro Features', 'taglock')}</h2>
							<a
								className="taglock-admin__pro-badge"
								href={proUrl}
								target="_blank"
								rel="noopener noreferrer"
							>
								PRO
							</a>
						</div>
                    </CardHeader>
                    <CardBody>
						<p className="description">
							{__(
								'These settings are available in TagLock Pro. They are shown here but disabled in the Lite version.',
								'taglock'
							)}
						</p>

						<Disabled isDisabled={true}>
							<div className="taglock-admin__pro-toggle-list">
								<ToggleControl
									label={__('Enable teaser mode', 'taglock')}
									checked={false}
									onChange={() => {}}
									help={__(
										'Show a teaser instead of a hard error when access is denied.',
										'taglock'
									)}
								/>

								<ToggleControl
									label={__('Enable redirect on access denied', 'taglock')}
									checked={false}
									onChange={() => {}}
									help={__(
										'Redirect visitors to a URL instead of showing the default message.',
										'taglock'
									)}
								/>

								<ToggleControl
									label={__('Enable engagement tagging', 'taglock')}
									checked={false}
									onChange={() => {}}
									help={__(
										'Automatically apply a tag after a visitor successfully views protected content.',
										'taglock'
									)}
								/>

								<ToggleControl
									label={__('Enable admin bypass (preview without subscriber ID)', 'taglock')}
									checked={false}
									onChange={() => {}}
									help={__(
										'Allow administrators to view protected content without an access link.',
										'taglock'
									)}
								/>
							</div>
						</Disabled>
                    </CardBody>
                </Card>
            </div>
		</div>
	);
};

export default AdminApp;
