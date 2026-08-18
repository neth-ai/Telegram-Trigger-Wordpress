# Telegram Bot Trigger Notifications

Author: Neth

Telegram Bot Trigger Notifications is a modular WordPress plugin that sends real-time Telegram messages for content activity, user actions, system updates, and optional third-party integrations.

![Telegram Bot Trigger Notifications](assets/images/telegram-bot-trigger-notifications.png)

## Requirements

- WordPress 6.9 or newer
- PHP 8.2 or newer

Build information: **18 សីហា 2026, 09:10:22 (Asia/Phnom_Penh)**.

## Structure

```
.
├── telegram-bot.php
├── includes/
│   ├── class-plugin.php
│   ├── class-telegram-api.php
│   ├── class-settings.php
│   ├── class-trigger-manager.php
│   ├── class-notification-manager.php
│   ├── class-template-manager.php
│   ├── class-logger.php
│   └── helpers.php
├── admin/
│   ├── class-admin.php
│   └── views/
│       ├── dashboard.php
│       ├── telegram-settings.php
│       ├── triggers.php
│       ├── alerts.php
│       ├── datetime.php
│       ├── templates.php
│       └── logs.php
├── modules/
│   ├── class-news-module.php
│   ├── class-document-module.php
│   ├── class-user-module.php
│   ├── class-plugin-monitor.php
│   └── class-system-monitor.php
├── assets/
├── languages/
├── uninstall.php
├── readme.txt
└── README.md
```

## Admin access

The **Telegram-Bot** menu and all subpages require the `manage_options` capability. By default, this capability is available only to Administrators, so Editors and other lower roles cannot see the plugin dashboard.

## Getting started

1. Upload this plugin folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Open **Telegram-Bot → Telegram Settings**.
4. Add your BotFather token and chat ID.
5. Return to **Dashboard** and send a test message.
6. Enable the triggers under **Triggers** and **Alerts**.

## Using the plugin in WordPress

- **Dashboard** shows connection readiness, setup progress, recipient count, enabled feature groups, and the test-message action.
- **Telegram Settings** manages the BotFather token, chat IDs, parse mode, link previews, and duplicate suppression.
- **Triggers** controls notifications for content, media, comments, WooCommerce, and supported form plugins.
- **Alerts** controls user actions, login/security events, system changes, and scheduled available-update digests.
- **Date & Time Format** controls DMY, MDY, or YMD order; numeric or named months; 2/4-digit years; separators; 12/24-hour clocks; optional seconds; and the message timezone. Cambodia time (`Asia/Phnom_Penh`) is the default, with an option to follow the WordPress site timezone.
- **Templates & Developer** documents the shortcode, action hook, helper function, and message filter.
- **Logs** provides recent diagnostic information without logging bot tokens or private authentication data.

The WordPress plugin-details modal includes project-owned Description, Installation, FAQ, Changelog, Screenshots, Valuations, and Usage Guide tabs. It does not import banners, ratings, or descriptions from unrelated WordPress.org plugins.

## GitHub updates

This plugin checks the public GitHub releases for:

```text
neth-ai/Telegram-Trigger-Wordpress
```

When a release tag such as `v1.0.1` is newer than the installed version and has the matching release ZIP asset, WordPress will show an update in **Plugins**.

The plugin dashboard also reads the latest GitHub Release tag. It shows the installed and latest versions, displays an upgrade action when a valid package is available, or explains which release ZIP is missing. Release information is refreshed every 15 minutes.

For the update to install cleanly, attach a ZIP named like:

```text
telegram-bot-v1.0.1.zip
```

to the GitHub release. The ZIP must contain the `telegram-bot/` plugin folder. GitHub's automatically generated source archive is intentionally not used because its top-level directory does not preserve the installed plugin basename. The plugin details modal also uses this GitHub repository instead of WordPress.org, so no third-party Telegram plugin metadata is shown.

Release ZIP files are build artifacts and are ignored by Git. They can still be uploaded directly to a GitHub Release for users and WordPress auto-updates.

## Custom development

Shortcode:

```php
[myp_telegram message="Hello from WordPress"]
```

Action hook:

```php
do_action( 'myp_telegram_send', 'Your message here' );
```

Message filter:

```php
add_filter( 'myp_telegram_message', function ( $message, $context ) {
    return $message . "\nEvent: " . $context['event'];
}, 10, 2 );
```
