<?php
/**
 * Main plugin bootstrap.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Plugin
 */
class MYP_Telegram_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstrap the plugin.
	 */
	private function __construct() {
		register_activation_hook( MYP_TELEGRAM_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( MYP_TELEGRAM_FILE, array( __CLASS__, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'boot' ), 20 );
	}

	/**
	 * Boot after all plugins are loaded.
	 *
	 * @return void
	 */
	public function boot() {
		require_once MYP_TELEGRAM_DIR . 'includes/helpers.php';
		require_once MYP_TELEGRAM_DIR . 'includes/class-settings.php';
		require_once MYP_TELEGRAM_DIR . 'includes/class-telegram-api.php';
		require_once MYP_TELEGRAM_DIR . 'includes/class-template-manager.php';
		require_once MYP_TELEGRAM_DIR . 'includes/class-logger.php';
		require_once MYP_TELEGRAM_DIR . 'includes/class-notification-manager.php';
		require_once MYP_TELEGRAM_DIR . 'includes/class-trigger-manager.php';
		require_once MYP_TELEGRAM_DIR . 'includes/class-integrations.php';
		require_once MYP_TELEGRAM_DIR . 'modules/class-news-module.php';
		require_once MYP_TELEGRAM_DIR . 'modules/class-document-module.php';
		require_once MYP_TELEGRAM_DIR . 'modules/class-user-module.php';
		require_once MYP_TELEGRAM_DIR . 'modules/class-plugin-monitor.php';
		require_once MYP_TELEGRAM_DIR . 'modules/class-system-monitor.php';
		require_once MYP_TELEGRAM_DIR . 'admin/class-admin.php';

		MYP_Telegram_Settings::instance();
		MYP_Telegram_API::instance();
		MYP_Telegram_Template_Manager::instance();
		MYP_Telegram_Logger::instance();
		MYP_Telegram_Notification_Manager::instance();
		MYP_Telegram_Trigger_Manager::instance();
		MYP_Telegram_Integrations::instance();
		MYP_Telegram_News_Module::register();
		MYP_Telegram_Document_Module::register();
		MYP_Telegram_User_Module::register();
		MYP_Telegram_Plugin_Monitor::register();
		MYP_Telegram_System_Monitor::register();
		MYP_Telegram_Admin::instance();

		$this->register_shortcodes_and_hooks();
	}

	/**
	 * Activation routine.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once MYP_TELEGRAM_DIR . 'includes/class-settings.php';

		MYP_Telegram_Settings::instance()->ensure_defaults();
		MYP_Telegram_Settings::instance()->schedule_available_updates_cron();
	}

	/**
	 * Deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		require_once MYP_TELEGRAM_DIR . 'includes/class-settings.php';

		MYP_Telegram_Settings::instance()->clear_available_updates_cron();
	}

	/**
	 * Register shortcode and custom action.
	 *
	 * @return void
	 */
	private function register_shortcodes_and_hooks() {
		add_shortcode( 'myp_telegram', 'myp_telegram_shortcode' );
		add_action( 'myp_telegram_send', 'myp_telegram_send_notification' );
	}
}

if ( ! function_exists( 'myp_telegram_shortcode' ) ) {
	/**
	 * Shortcode: [myp_telegram message="Hello Telegram"].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	function myp_telegram_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'message'  => '',
				'chat_ids' => '',
				'token'    => '',
			),
			$atts,
			'myp_telegram'
		);

		$message = trim( (string) $atts['message'] );

		if ( '' === $message ) {
			return '';
		}

		$args = array();

		if ( '' !== trim( (string) $atts['chat_ids'] ) ) {
			$args['chat_ids'] = trim( (string) $atts['chat_ids'] );
		}

		if ( '' !== trim( (string) $atts['token'] ) ) {
			$args['token'] = trim( (string) $atts['token'] );
		}

		myp_telegram_send_notification( $message, $args );

		return '';
	}
}
