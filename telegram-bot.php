<?php
/**
 * Plugin Name:       Telegram Bot Trigger Notifications
 * Plugin URI:        https://github.com/neth-ai/Telegram-Trigger-Wordpress
 * Description:       Sends configurable Telegram notifications for WordPress content, comments, users, system/plugin updates, and popular third-party integrations.
 * Version:           1.0.1
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

define( 'MYP_TELEGRAM_FILE', __FILE__ );
define( 'MYP_TELEGRAM_DIR', plugin_dir_path( __FILE__ ) );
define( 'MYP_TELEGRAM_URL', plugin_dir_url( __FILE__ ) );
define( 'MYP_TELEGRAM_OPTION', 'myp_telegram_settings' );

$myp_telegram_headers = get_file_data( MYP_TELEGRAM_FILE, array( 'Version' => 'Version' ), 'plugin' );
$myp_telegram_version = ! empty( $myp_telegram_headers['Version'] ) ? (string) $myp_telegram_headers['Version'] : '1.0.1';

define( 'MYP_TELEGRAM_VERSION', $myp_telegram_version );

unset( $myp_telegram_headers, $myp_telegram_version );

require_once MYP_TELEGRAM_DIR . 'includes/class-plugin.php';

MYP_Telegram_Plugin::instance();
