<?php
/**
 * Uninstall handler.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'myp_telegram_settings' );
delete_site_transient( 'myp_tg_core_old_version' );
wp_clear_scheduled_hook( 'myp_telegram_available_updates' );
