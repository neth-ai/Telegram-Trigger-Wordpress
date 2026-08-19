=== Telegram Bot Trigger Notifications ===
Contributors: Neth
Tags: telegram, notifications, bot, admin, security
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sends configurable Telegram notifications for WordPress content, comments, users, updates, and popular third-party integrations.

== Description ==

Telegram Bot Trigger Notifications turns your WordPress admin activity into real-time messages in Telegram.

Once you install and activate the plugin, a **Telegram-Bot** menu appears in wp-admin. The dashboard lets you enter your BotFather bot token and one or more chat IDs, then test delivery immediately.

Features:

* Content triggers for posts, pages, and registered admin-menu post types, including private types added by themes, plugins, and MU plugins.
* Publish, edit, create, trash, restore, delete, media upload, and comment notifications.
* User actions: registration, profile update, role change, login, logout, failed login, password reset, and account deletion.
* System alerts: plugin activation/deactivation/deletion/update, theme switching/deletion/update, core updates, and language pack updates.
* Optional available-updates digest.
* Optional integrations for WooCommerce, Contact Form 7, WPForms, Fluent Forms, Ninja Forms, Elementor Forms, and Gravity Forms, detected and registered only when their plugins are active.
* Multi-chat support, duplicate suppression, HTML/MarkdownV2 parse modes, shortcode, action hook, and filter customization.
* Configurable DMY, MDY, or YMD dates; month and year styles; separators; 12/24-hour time; optional seconds; and message timezone. Cambodia time is the default.

Build information: **18 សីហា 2026, 09:10:22 (Asia/Phnom_Penh)**.

== Installation ==

1. Upload the `telegram-trigger` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Open the new **Telegram-Bot** menu.
4. Enter your Telegram bot token and chat ID.
5. Click **Send test message**, then enable the triggers you need.

== Usage Guide ==

* **Dashboard:** Review connection status, setup progress, recipient count, enabled alert groups, and send a test message.
* **Telegram Settings:** Configure the BotFather token, chat IDs, message formatting, link previews, and duplicate suppression.
* **Triggers:** Select the content, media, comment, and supported integration events that should send notifications.
* **Alerts:** Select user, login, security, plugin, theme, WordPress core, language, and available-update alerts.
* **Date & Time Format:** Choose the message timezone, date order, month and year display, separator, 12/24-hour clock, and whether notifications include seconds.
* **Message Format:** Customize icons, wording, field order, and placeholders for every built-in message type. The user-role line can be shown or hidden.
* **Templates & Developer:** Use the shortcode, action hook, helper function, and message filter for custom workflows.
* **Logs:** Review recent delivery errors and diagnostic entries. Bot tokens and private authentication data are not logged.

== Frequently Asked Questions ==

= How do I get a bot token? =

Open Telegram, search for @BotFather, send `/newbot`, and follow the prompts. BotFather will return a token that looks like `123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw`.

= How do I find my chat ID? =

Send `/start` to @userinfobot or @get_id_bot. Group and channel IDs are available from `https://api.telegram.org/botYOUR_TOKEN/getUpdates` after your bot receives a message in the target chat.

= Can I send to more than one chat? =

Yes. Separate group or channel IDs with commas. The plugin automatically saves every numeric ID with exactly one leading minus sign, so `5596463200`, `-5596463200`, and `--5596463200` all become `-5596463200`.

Add the bot to each group or channel and grant it permission to post messages.

= Does the plugin send sensitive data? =

No. User alerts intentionally exclude passwords, password hashes, email addresses, IP addresses, session data, cookies, and reset tokens. Form integrations send only form name and entry ID by default.

= How do updates work? =

The plugin follows the standard WordPress update system and does not replace or modify WordPress update data. GitHub builds can be updated manually by installing a newer release package from https://github.com/neth-ai/Telegram-Trigger-Wordpress/releases.

== Screenshots ==

1. Telegram Bot Trigger Notifications banner and WordPress-to-Telegram notification flow.

== Ratings and Feedback ==

This custom GitHub plugin does not import ratings from unrelated WordPress.org plugins. Report issues or suggest improvements at https://github.com/neth-ai/Telegram-Trigger-Wordpress/issues.

== Changelog ==

= 1.0.0 =
* Initial release.
* Responsive administration dashboard, settings, triggers, alerts, message formats, developer templates, and logs.
* Configurable date and time formats for every built-in Telegram notification and plugin log.
* Dynamic discovery of registered post types displayed in the WordPress admin menu.
* Hidden bot-token and Chat ID fields with accessible show/hide controls.
* WordPress-standard update handling without a custom updater.
