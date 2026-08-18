<?php
/**
 * Global helper functions.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'myp_telegram_settings' ) ) {
	/**
	 * Return the plugin settings object.
	 *
	 * @return MYP_Telegram_Settings
	 */
	function myp_telegram_settings() {
		return MYP_Telegram_Settings::instance();
	}
}

if ( ! function_exists( 'myp_telegram_send_notification' ) ) {
	/**
	 * Send a message through the configured Telegram bot.
	 *
	 * @param string $message Message text.
	 * @param array  $args    Optional overrides.
	 * @return bool
	 */
	function myp_telegram_send_notification( $message, $args = array() ) {
		return MYP_Telegram_API::instance()->send( $message, $args );
	}
}

if ( ! function_exists( 'myp_telegram_format_datetime' ) ) {
	/**
	 * Format a date and time using the plugin's saved display preferences.
	 *
	 * @param int|null $timestamp Unix timestamp, or null for now.
	 * @return string
	 */
	function myp_telegram_format_datetime( $timestamp = null ) {
		return myp_telegram_settings()->format_datetime( $timestamp );
	}
}

if ( ! function_exists( 'myp_telegram_send_system_alert' ) ) {
	/**
	 * Send a system alert.
	 *
	 * @param string $action    Action.
	 * @param string $component Component type.
	 * @param string $item      Item.
	 * @param string $version   Version.
	 * @return bool
	 */
	function myp_telegram_send_system_alert( $action, $component, $item = '', $version = '' ) {
		return MYP_Telegram_Trigger_Manager::instance()->send_system_alert( $action, $component, $item, $version );
	}
}

if ( ! function_exists( 'myp_telegram_send_user_alert' ) ) {
	/**
	 * Send a user alert.
	 *
	 * @param string       $action Action.
	 * @param WP_User|int  $user   User or ID.
	 * @param string       $detail Detail.
	 * @param WP_User|null $actor  Actor.
	 * @return bool
	 */
	function myp_telegram_send_user_alert( $action, $user, $detail = '', $actor = null ) {
		return MYP_Telegram_Trigger_Manager::instance()->send_user_alert( $action, $user, $detail, $actor );
	}
}

if ( ! function_exists( 'myp_telegram_log' ) ) {
	/**
	 * Write a debug log entry.
	 *
	 * @param string $context Context.
	 * @param string $message Message.
	 * @param string $level   Log level.
	 * @return void
	 */
	function myp_telegram_log( $context, $message, $level = 'info' ) {
		MYP_Telegram_Logger::instance()->log( $context, $message, $level );
	}
}
