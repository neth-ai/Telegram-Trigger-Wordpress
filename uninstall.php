<?php
/**
 * Uninstall handler.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'myp_telegram_settings' );
delete_site_transient( 'myp_tg_core_old_version' );
delete_transient( 'myp_telegram_github_release' );

wp_clear_scheduled_hook( 'myp_telegram_available_updates' );
