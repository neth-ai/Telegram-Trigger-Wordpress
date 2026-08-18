<?php
/**
 * Plugin lifecycle monitor.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Plugin_Monitor
 */
class MYP_Telegram_Plugin_Monitor {

	/**
	 * Register plugin lifecycle hooks.
	 *
	 * @return void
	 */
	public static function register() {
		$manager = MYP_Telegram_Trigger_Manager::instance();

		add_action( 'activated_plugin', array( $manager, 'plugin_activated' ), 10, 2 );
		add_action( 'deactivated_plugin', array( $manager, 'plugin_deactivated' ), 10, 2 );
		add_action( 'deleted_plugin', array( $manager, 'plugin_deleted' ), 10, 2 );
	}
}
