# Telegram Bot Trigger Notifications

Author: Neth

Telegram Bot Trigger Notifications is a modular WordPress plugin that sends real-time Telegram messages for content activity, user actions, system updates, and optional third-party integrations.

## Requirements

- WordPress 6.9 or newer
- PHP 8.2 or newer

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

## GitHub updates

This plugin checks the public GitHub releases for:

```text
neth-ai/Telegram-Trigger-Wordpress
```

When a release tag such as `v1.0.1` is newer than the installed version and has the matching release ZIP asset, WordPress will show an update in **Plugins**.

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
