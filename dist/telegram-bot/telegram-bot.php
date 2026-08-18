<?php
/**
 * Plugin Name:       Telegram Bot Trigger Notifications
 * Plugin URI:        https://github.com/neth-ai/Telegram-Trigger-Wordpress
 * Description:       Sends configurable Telegram notifications for WordPress content, comments, users, system/plugin updates, and popular third-party integrations.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      8.2
 * Tested up to:      6.9
 * Author:            Neth
 * Author URI:        https://github.com/neth-ai
 * Update URI:        https://github.com/neth-ai/Telegram-Trigger-Wordpress
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       telegram-bot
 * Domain Path:       /languages
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MYP_TELEGRAM_VERSION', '1.0.0' );
define( 'MYP_TELEGRAM_FILE', __FILE__ );
define( 'MYP_TELEGRAM_DIR', plugin_dir_path( __FILE__ ) );
define( 'MYP_TELEGRAM_URL', plugin_dir_url( __FILE__ ) );
define( 'MYP_TELEGRAM_OPTION', 'myp_telegram_settings' );

require_once MYP_TELEGRAM_DIR . 'includes/class-plugin.php';

MYP_Telegram_Plugin::instance();
