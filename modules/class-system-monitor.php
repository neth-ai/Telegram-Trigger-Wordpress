<?php
/**
 * System and update monitor.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_System_Monitor
 */
class MYP_Telegram_System_Monitor {

	/**
	 * Register system and update hooks.
	 *
	 * @return void
	 */
	public static function register() {
		$manager = MYP_Telegram_Trigger_Manager::instance();

		add_action( 'switch_theme', array( $manager, 'theme_switched' ), 10, 3 );
		add_action( 'deleted_theme', array( $manager, 'theme_deleted' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $manager, 'upgrade_completed' ), 20, 2 );
		add_action( '_core_updated_successfully', array( $manager, 'core_updated' ) );
		add_filter( 'upgrader_pre_install', array( $manager, 'capture_versions_before_upgrade' ), 10, 2 );
		add_action( 'myp_telegram_available_updates', array( $manager, 'send_available_updates_summary' ) );
	}
}
