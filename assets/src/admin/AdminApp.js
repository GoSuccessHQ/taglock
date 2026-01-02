import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	TextControl,
	Button,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const AdminApp = () => {
	const [username, setUsername] = useState('');
	const [password, setPassword] = useState('');
	const [isSaving, setIsSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	useEffect(() => {
		let isMounted = true;

		const loadSettings = async () => {
			try {
				const response = await apiFetch({
					path: '/taglock/v1/settings',
					method: 'GET',
				});

				if (isMounted && response?.success && response?.data) {
					setUsername(response.data.klicktipp_username || '');
				}
			} catch (error) {
				if (!isMounted) {
					return;
				}
				setNotice({
					status: 'error',
					message: error.message || __('Failed to load settings.', 'taglock'),
				});
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
			await apiFetch({
				path: '/taglock/v1/settings',
				method: 'POST',
				data: {
					klicktipp_username: username,
					klicktipp_password: password,
				},
			});

			setPassword('');

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

	return (
		<div className="wrap">
			<h1>{__('TagLock Settings', 'taglock')}</h1>
			
			<div className="taglock-admin">
				{notice && (
					<Notice
						status={notice.status}
						isDismissible
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				)}

				<Card>
					<CardHeader>
						<h2>{__('KlickTipp Connection', 'taglock')}</h2>
					</CardHeader>
					<CardBody>
					<p className="description">
						{__(
							'Enter your KlickTipp username and password to connect.',
							'taglock'
						)}
					</p>

					<TextControl
						label={__('Username', 'taglock')}
						value={username}
						onChange={setUsername}
						required
					/>

					<TextControl
						label={__('Password', 'taglock')}
						type="password"
						value={password}
						onChange={setPassword}
						help={__(
							'For security reasons, the password is not displayed after saving.',
							'taglock'
						)}
						required
					/>

					<Button variant="primary" onClick={handleSave} isBusy={isSaving}>
						{__('Save Settings', 'taglock')}
					</Button>
				</CardBody>
                </Card>

                <Card style={{ marginTop: '20px' }}>
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
                        <pre style={{ padding: '10px', background: '#f0f0f0' }}>
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

                <Card style={{ marginTop: '20px' }}>
                    <CardHeader>
                        <h2>{__('Pro Features (Coming Soon)', 'taglock')}</h2>
                    </CardHeader>
                    <CardBody>
                        <p className="description">
                            {__(
                                'Upgrade to Pro for additional features:',
                                'taglock'
                            )}
                        </p>
                        <ul>
                            <li>{__('Custom redirect URLs on access denied', 'taglock')}</li>
                            <li>{__('Automatically apply tags after viewing content', 'taglock')}</li>
                            <li>{__('Advanced analytics and tracking', 'taglock')}</li>
                            <li>{__('Priority support', 'taglock')}</li>
                        </ul>
                    </CardBody>
                </Card>
            </div>
		</div>
	);
};

export default AdminApp;
