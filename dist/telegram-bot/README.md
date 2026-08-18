# Telegram Bot Notifications

Contributors: Neth

Telegram Bot Notifications is a modular WordPress plugin that sends real-time Telegram messages for content activity, user actions, system updates, and optional third-party integrations.

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
