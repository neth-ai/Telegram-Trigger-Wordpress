<?php
/**
 * Notification message builder.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Notification_Manager
 */
class MYP_Telegram_Notification_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Notification_Manager|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Notification_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Send a content activity alert.
	 *
	 * @param WP_Post|int $post   Post or ID.
	 * @param string      $action Action label.
	 * @param string|null $url    URL override.
	 * @return bool
	 */
	public function send_content_alert( $post, $action, $url = null ) {
		return MYP_Telegram_Trigger_Manager::instance()->send_content_alert( $post, $action, $url );
	}

	/**
	 * Send a comment alert.
	 *
	 * @param WP_Comment $comment Comment.
	 * @param string     $action  Action label.
	 * @return bool
	 */
	public function send_comment_alert( $comment, $action ) {
		return MYP_Telegram_Trigger_Manager::instance()->send_comment_alert( $comment, $action );
	}

	/**
	 * Send a system alert.
	 *
	 * @param string $action    Action.
	 * @param string $component Component.
	 * @param string $item      Item.
	 * @param string $version   Version.
	 * @return bool
	 */
	public function send_system_alert( $action, $component, $item = '', $version = '' ) {
		return MYP_Telegram_Trigger_Manager::instance()->send_system_alert( $action, $component, $item, $version );
	}

	/**
	 * Send a user alert.
	 *
	 * @param string       $action Action.
	 * @param WP_User|int  $user   User or ID.
	 * @param string       $detail Detail.
	 * @param WP_User|null $actor  Actor.
	 * @return bool
	 */
	public function send_user_alert( $action, $user, $detail = '', $actor = null ) {
		return MYP_Telegram_Trigger_Manager::instance()->send_user_alert( $action, $user, $detail, $actor );
	}

	/**
	 * Send a failed login alert.
	 *
	 * @param string $username Attempted username.
	 * @return bool
	 */
	public function send_failed_login_alert( $username ) {
		$username = MYP_Telegram_Template_Manager::instance()->safe_text( $username, 100 );
		$khmer    = MYP_Telegram_Template_Manager::instance()->is_khmer_locale();
		$lines    = array(
			'⚠️ ' . ( $khmer ? 'Failed Login Alert' : 'Failed Login Alert' ),
			'',
			( $khmer ? 'ឈ្មោះដែលបានប៉ុនប៉ង: ' : 'Attempted username: ' ) . $username,
			( $khmer ? 'ពេលវេលា: ' : 'Time: ' ) . myp_telegram_format_datetime(),
		);

		return MYP_Telegram_API::instance()->send( implode( "\n", $lines ) );
	}
}
