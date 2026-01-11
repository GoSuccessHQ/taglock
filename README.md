# TagLock - Instant Access for KlickTipp

Protect WordPress content based on KlickTipp tags - no membership required, cache compatible, and secure.

## Features

- ✅ **Shortcode-Based Protection**: Use `[taglock id="1"]...[/taglock]` to protect content
- ✅ **Cache Compatible**: Protected content is loaded client-side after verification
- ✅ **Secure by Design**: Protected content is not rendered into the initial HTML
- ✅ **KlickTipp Integration**: Checks subscriber tags via KlickTipp API
- ✅ **Connection Health Monitoring**: The plugin periodically checks whether the KlickTipp connection is healthy and shows a connected/disconnected status in the admin UI
- ✅ **Modern Architecture**: PHP 8.3, Symfony DI, React, WordPress REST API
- ✅ **Extensible**: Filter/action hooks for customizations and add-ons

## Installation (Production)

1. Upload the plugin to `wp-content/plugins/taglock`
2. Activate it in WordPress

## Configuration

1. Navigate to **Settings > TagLock** in WordPress admin
2. Enter your KlickTipp username and password
3. Save settings

## Usage

Protect content with the shortcode:

```
[taglock id="1"]
Your protected content here. Only subscribers matching TagLocker #1 can see this.
[/taglock]
```

### Shortcode Attributes

- `id` (required): The TagLocker ID (configured in WordPress admin)
- `message` (optional): Custom message for denied access
- `loader_text` (optional): Custom loading text

### Access URL

**Important:** Users access protected content via email links containing their subscriber identifier (`subscriber_id`) in the URL hash (not as a query parameter):

```
https://yoursite.com/page/#taglock_subscriber_id=12345
```

**How it works:**
1. You send an email with a link to your protected page including `#taglock_subscriber_id=...`
2. The page loads, React reads `taglock_subscriber_id` from the URL hash
3. React stores it in LocalStorage and immediately removes it from the address bar
4. Backend checks if this identifier has the required tag
5. Content is displayed or access is denied

**No user login required!** The identifier is sufficient.

## Architecture

### PHP (Backend)

- **Core/Plugin.php**: Symfony DI container initialization
- **Provider/KlickTippProvider**: KlickTipp API integration
- **Service/ShortcodeService**: Shortcode registration
- **Route/AccessCheckRoute**: REST API endpoint for tag verification
- **Route/TagsRoute**: REST API endpoint that loads available KlickTipp tags for the admin UI
- **Service/AdminMenuService**: Settings page
- **Service/AssetService**: Asset management
- **Controller/ActivationController**: Plugin activation and scheduled connection checks

### REST API Endpoints (Internal)

These endpoints are used by the admin UI and frontend loader:

- `POST /wp-json/taglock/v1/check-access` - Batch access checks for protected content
- `GET  /wp-json/taglock/v1/settings` - Read settings (also includes connection status)
- `POST /wp-json/taglock/v1/settings` - Save settings
- `GET  /wp-json/taglock/v1/rules` - Manage TagLockers
- `GET  /wp-json/taglock/v1/tags` - Load available KlickTipp tags (id => name)

### React (Frontend)

- **assets/src/admin/**: Settings UI with WordPress Components
- **assets/src/frontend/**: Content loader with access check

## Extensibility

The plugin provides hooks for customizations and add-ons:

### Available Filters

- `taglock_access_denied_response`: Modify response when access is denied
- `taglock_access_granted_response`: Modify response after access granted
- `taglock_protected_content`: Filter content before output
- `taglock_settings_fields`: Add custom settings fields

### Available Actions

- `taglock_access_granted`: Triggered when access is granted
- `taglock_access_denied`: Triggered when access is denied
- `taglock_before_access_check`: Before tag verification
- `taglock_after_access_check`: After tag verification

## Development

### Install Dependencies

```bash
composer install
npm install
```

### Build Assets

```bash
npm run build        # Production build
npm run start        # Development mode with watch
```

### Code Style

```bash
npm run lint:js      # Lint JavaScript
npm run lint:css     # Lint CSS
npm run format       # Format code
```

## Requirements

- WordPress 6.8+
- PHP 8.3+
- Composer
- Node.js & npm

## License

GPL v3 or later

## Author

GoSuccess - https://gosuccess.io
