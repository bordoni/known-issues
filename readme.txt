=== Known Issues ===
Contributors: bordoni
Tags: jira, helpscout, known-issues, support, notifications
Requires at least: 6.7
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track and manage known issues synced from Jira, with automated HelpScout notifications for affected users.

== Description ==

Known Issues is a WordPress plugin that integrates with Jira and HelpScout to:

* Track known issues from Jira via webhooks
* Allow users to register as "affected" by an issue
* Automatically notify users via HelpScout when issues are resolved
* Provide a custom block for displaying affected user counts
* Manage user subscriptions with GDPR compliance

= Features =

* **Jira Webhook Integration** - Automatically sync issues from Jira with HMAC signature verification
* **User Sign-up System** - Let users track issues they're affected by
* **HelpScout Notifications** - Send automated emails when signing up and when issues are resolved
* **Custom Post Type** - Manage known issues with WordPress block editor
* **Custom Post Statuses** - Track issue lifecycle (open, closed, archived, done)
* **GDPR Compliant** - Full data export and erasure support
* **Batch Processing** - Queue-based notification system with WP-Cron and WP-CLI support

= Requirements =

* WordPress 6.7 or higher
* PHP 7.4 or higher
* Jira account (for webhook integration)
* HelpScout account (for email notifications)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/known-issues/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings → Known Issues to configure Jira and HelpScout integration
4. Set up environment variables in wp-config.php (see documentation)

== Frequently Asked Questions ==

= How do I set up Jira webhooks? =

See the plugin documentation for detailed setup instructions.

= How do I configure HelpScout integration? =

You'll need to create a HelpScout OAuth application and configure the credentials in the plugin settings.

= Is this GDPR compliant? =

Yes, the plugin includes full support for WordPress privacy export and erasure tools.

== Changelog ==

= 1.0.0 =
* Initial release
