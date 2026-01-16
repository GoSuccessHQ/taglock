=== TagLock ===

Contributors: gosuccess
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Tags: klicktipp, email marketing, newsletter, content protection

Protect WordPress content based on KlickTipp tags - no membership required, 100% cache compatible and secure.

== Description ==

TagLock allows you to protect WordPress content (videos, text, downloads) based on KlickTipp tags without setting up a complex membership plugin.

Unlike traditional solutions, TagLock uses a headless approach: Protected content is not rendered in the initial HTML but is dynamically loaded via React and REST API only after successful API validation.

= 🔥 Features =

* **TagLocker-based Protection** - Shortcode `[taglock id="1"]...[/taglock]` protects any content.
* **Tag-based Access Control** - Define which KlickTipp tags are required (Any or All tags mode).
* **Cache Compatible** - Protected content is loaded only after verification.
* **Secure by Design** - Protected content is not rendered in the initial HTML.
* **React-based Admin Interface** - Modern settings UI using WordPress Components.
* **Connection Health Monitoring** - Periodic connection checks and a connected/disconnected status in the admin UI.
* **No User Accounts Required** - Access is verified via a subscriber identifier.
* **Extensible** - Provides filters and actions for customizations and add-ons.

= Requirements =

* WordPress 6.8 or higher
* PHP 8.3 or higher
* KlickTipp account with API access

= Documentation =

For detailed documentation, API references, and integration guides, please visit our [GitHub repository](https://github.com/GoSuccessHQ/taglock).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/taglock/`, or install through the WordPress Plugins screen
2. Activate the plugin
3. Go to Settings → TagLock
4. Enter your KlickTipp login credentials and connect

== Usage ==

Use the shortcode to protect content:

`[taglock id="1"]Protected content[/taglock]`

Shortcode attributes:

* `id` (required): TagLocker ID (configured in WordPress admin)
* `message` (optional): Custom denied message
* `loader_text` (optional): Custom loading text

== Admin UI ==

After connecting your KlickTipp account, TagLock loads your available tags and lets you select them by name while storing their tag IDs internally.

== Access Links ==

Users should open protected pages via links that include their subscriber identifier in the URL hash (not as a query parameter):

`https://example.com/protected-page/#taglock_subscriber_id=12345`

The frontend stores the identifier in LocalStorage and removes it from the address bar.

== Frequently Asked Questions ==

= What is KlickTipp? =

KlickTipp is an email marketing platform popular in German-speaking countries. It provides email automation, tag-based subscriber management, and marketing campaign tools.

= Do I need a KlickTipp account? =

Yes, this plugin requires an active KlickTipp account with API access enabled.

= What PHP version is required? =

This plugin requires PHP 8.3 or higher.

== Screenshots ==

1. TagLock settings screen

== Changelog ==
= 1.0.0 - 2026-01-16 =
Initial release.


= Privacy Policy =

This plugin stores plugin settings in your WordPress database and communicates with KlickTipp for tag verification. Please ensure your privacy policy reflects this data processing.

= Support =

For support, bug reports, or feature requests, please use our [GitHub Issues](https://github.com/GoSuccessHQ/taglock/issues) page.

= Contributing =

We welcome contributions! Please see our [Contributing Guidelines](https://github.com/GoSuccessHQ/taglock/blob/main/CONTRIBUTING.md) for details.