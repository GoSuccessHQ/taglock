# TagLock - Instant Access for KlickTipp

Protect WordPress content based on KlickTipp tags - no membership required, 100% cache compatible and secure.

## Features

- ✅ **Shortcode-Based Protection**: Use `[taglock tag="123"]` to protect any content
- ✅ **Cache Compatible**: Content loaded via React after server-side tag verification
- ✅ **Secure**: Protected content never appears in page source
- ✅ **KlickTipp Integration**: Seamlessly checks subscriber tags via API
- ✅ **Modern Architecture**: Built with PHP 8.3, Symfony DI, React, and WordPress best practices
- ✅ **Pro-Ready**: Filter hooks prepared for Pro addon features

## Installation

1. Clone or download this plugin to `wp-content/plugins/taglock`
2. Run `composer install` to install PHP dependencies
3. Run `npm install` to install JavaScript dependencies
4. Run `npm run build` to build React assets
5. Activate the plugin in WordPress

## Configuration

1. Navigate to **Settings > TagLock** in WordPress admin
2. Enter your KlickTipp username and password
3. Save settings

## Usage

Protect content with the shortcode:

```
[taglock tag="123"]
Your protected content here. Only subscribers with tag #123 can see this.
[/taglock]
```

### Shortcode Attributes

- `tag` (required): The KlickTipp tag ID to check
- `message` (optional): Custom message for denied access
- `loader_text` (optional): Custom loading text

### Access URL

**Important:** Users access protected content via KlickTipp email links containing their subscriber ID:

```
https://yoursite.com/page/?subscriber_id=12345
```

**How it works:**
1. You send an email via KlickTipp with a link to your protected page
2. KlickTipp automatically appends `?subscriber_id=XXX` to the URL
3. The page loads, React detects the subscriber ID from the URL
4. Backend checks if this subscriber has the required tag
5. Content is displayed or access is denied

**No user login required!** The subscriber ID in the URL is sufficient.

## Architecture

### PHP (Backend)

- **Core/Plugin.php**: Symfony DI container initialization
- **Provider/KlickTippProvider**: KlickTipp API integration
- **Service/ShortcodeService**: Shortcode registration
- **Route/AccessCheckRoute**: REST API endpoint for tag verification
- **Service/AdminMenuService**: Settings page
- **Service/AssetService**: Asset management

### React (Frontend)

- **assets/src/admin/**: Settings UI with WordPress Components
- **assets/src/frontend/**: Content loader with access check

## Lite vs Pro Architecture

The plugin is designed with Pro addon support:

### Hooks for Pro Features

**Filters:**
- `taglock_access_denied_response`: Modify response (add redirect URL, etc.)
- `taglock_access_granted_response`: Modify response after access granted
- `taglock_protected_content`: Filter content before output
- `taglock_settings_fields`: Add Pro settings fields

**Actions:**
- `taglock_access_granted`: Triggered when access is granted
- `taglock_access_denied`: Triggered when access is denied
- `taglock_before_access_check`: Before tag verification
- `taglock_after_access_check`: After tag verification

### Pro Features (Coming Soon)

- Custom redirect URLs on access denied
- Automatically apply tags after viewing content
- Advanced analytics and tracking
- Priority support

## Development

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
