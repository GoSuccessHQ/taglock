=== TagLock Lite - Instant Access for KlickTipp ===

Contributors: gosuccess
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Tags: klicktipp, email marketing, newsletter, content protection

Protect WordPress content based on KlickTipp tags - no membership required, 100% cache compatible and secure.

== Description ==

TagLock allows you to protect WordPress content (videos, text, downloads) based on KlickTipp tags without setting up a complex membership plugin.

Unlike traditional solutions, TagLock uses a headless approach: Protected content is not rendered in the initial HTML but is dynamically loaded via React and REST API only after successful API validation.

= 🔥 Features (Lite) =

* **Tag-based Protection** - Simple shortcode [taglock tag="123"]....[/taglock] to protect any content.
* **Cache Compatible** - Works seamlessly with caching plugins and CDNs.
* **Maximum Security** - Protected content never appears in the source code (forget about insecure display: none CSS tricks).
* **React-based Admin Interface** - Modern, responsive administration panel
* **No User Accounts** - Your visitors don't need a WordPress account - validation happens directly via the KlickTipp Subscriber ID (GDPR compliant).

*Note: This is the Lite version. Advanced features like redirects upon access denial or engagement tagging (consumption tracking) are available in the Pro version.*

= Requirements =

* WordPress 6.0 or higher
* PHP 8.3 or higher
* KlickTipp account with API access

= Documentation =

For detailed documentation, API references, and integration guides, please visit our [GitHub repository](https://github.com/GoSuccessHQ/taglock).

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/taglock` directory, or install through WordPress plugins screen
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to the TagLock settings page
4. Enter your KlickTipp API credentials
5. Configure settings for tag-based content protection
6. Set up your access preferences

== Frequently Asked Questions ==

= What is KlickTipp? =

KlickTipp is an email marketing platform popular in German-speaking countries. It provides email automation, tag-based subscriber management, and marketing campaign tools.

= Do I need a KlickTipp account? =

Yes, this plugin requires an active KlickTipp account with API access enabled.

= What PHP version is required? =

This plugin requires PHP 8.3 or higher to take advantage of modern PHP features like Property Hooks and improved type safety.

== Screenshots ==

1. Main dashboard with overview statistics
2. KlickTipp account configuration panel
3. Tag-based content protection interface
4. Access preferences settings

== Changelog ==

= 1.0.0 =
* Initial release
* Digistore24 API integration
* Affiliate tracking system
* IPN payment processing
* React-based admin interface
* Comprehensive reporting system

== Upgrade Notice ==

= 1.0.0 =
Initial release of TagLock.

== Additional Info ==

= Privacy Policy =

This plugin stores information about KlickTipp tags and subscriber activities in your WordPress database. No data is sent to third parties except KlickTipp for API communication. Please ensure your privacy policy reflects this data processing.

= Support =

For support, bug reports, or feature requests, please use our [GitHub Issues](https://github.com/GoSuccessHQ/taglock/issues) page.

= Contributing =

We welcome contributions! Please see our [Contributing Guidelines](https://github.com/GoSuccessHQ/taglock/blob/main/CONTRIBUTING.md) for details.