<?php
/**
 * User activity module.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_User_Module
 */
class MYP_Telegram_User_Module {

	/**
	 * Register user activity hooks.
	 *
	 * @return void
	 */
	public static function register() {
		$manager = MYP_Telegram_Trigger_Manager::instance();

		add_action( 'user_register', array( $manager, 'user_registered' ) );
		add_action( 'profile_update', array( $manager, 'user_profile_updated' ), 10, 2 );
		add_action( 'set_user_role', array( $manager, 'queue_role_change' ) );
		add_action( 'add_user_role', array( $manager, 'queue_role_change' ) );
		add_action( 'remove_user_role', array( $manager, 'queue_role_change' ) );
		add_action( 'wp_login', array( $manager, 'user_logged_in' ), 10, 2 );
		add_action( 'wp_login_failed', array( $manager, 'user_login_failed' ) );
		add_action( 'wp_logout', array( $manager, 'user_logged_out' ) );
		add_action( 'after_password_reset', array( $manager, 'password_reset' ), 10, 1 );
		add_action( 'deleted_user', array( $manager, 'user_deleted' ), 10, 3 );
		add_action( 'shutdown', array( $manager, 'flush_role_changes' ), 20 );
	}
}
