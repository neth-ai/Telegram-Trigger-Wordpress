<?php
/**
 * Admin screens and settings handling.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Admin
 */
class MYP_Telegram_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register admin hooks.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_myp_telegram_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_myp_telegram_test', array( $this, 'handle_test' ) );
		add_action( 'admin_post_myp_telegram_clear_logs', array( $this, 'handle_clear_logs' ) );
	}

	/**
	 * Register the Telegram-Bot admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Telegram Bot', 'telegram-bot' ),
			__( 'Telegram-Bot', 'telegram-bot' ),
			'manage_options',
			'myp-telegram-bot',
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			58
		);

		add_submenu_page( 'myp-telegram-bot', __( 'Dashboard', 'telegram-bot' ), __( 'Dashboard', 'telegram-bot' ), 'manage_options', 'myp-telegram-bot', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Telegram Settings', 'telegram-bot' ), __( 'Telegram Settings', 'telegram-bot' ), 'manage_options', 'myp-telegram-settings', array( $this, 'render_settings' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Triggers', 'telegram-bot' ), __( 'Triggers', 'telegram-bot' ), 'manage_options', 'myp-telegram-triggers', array( $this, 'render_triggers' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Alerts', 'telegram-bot' ), __( 'Alerts', 'telegram-bot' ), 'manage_options', 'myp-telegram-alerts', array( $this, 'render_alerts' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Templates', 'telegram-bot' ), __( 'Templates', 'telegram-bot' ), 'manage_options', 'myp-telegram-templates', array( $this, 'render_templates' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Logs', 'telegram-bot' ), __( 'Logs', 'telegram-bot' ), 'manage_options', 'myp-telegram-logs', array( $this, 'render_logs' ) );
	}

	/**
	 * Enqueue assets on plugin screens.
	 *
	 * @param string $hook_suffix Current hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 0 !== strpos( $hook_suffix, 'myp-telegram' ) ) {
			return;
		}

		wp_enqueue_style( 'myp-telegram-admin', MYP_TELEGRAM_URL . 'assets/css/admin.css', array(), MYP_TELEGRAM_VERSION );
		wp_enqueue_script( 'myp-telegram-admin', MYP_TELEGRAM_URL . 'assets/js/admin.js', array(), MYP_TELEGRAM_VERSION, true );
	}

	/**
	 * Save settings from an admin-post request.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'telegram-bot' ) );
		}

		check_admin_referer( 'myp_telegram_save' );

		$page  = isset( $_POST['myp_page'] ) ? sanitize_key( wp_unslash( $_POST['myp_page'] ) ) : 'dashboard';
		$input = $this->input_for_page( $page );

		myp_telegram_settings()->save_partial( $input );
		myp_telegram_settings()->reschedule_available_updates_cron();

		set_transient(
			'myp_telegram_admin_notice',
			array(
				'type'    => 'success',
				'message' => __( 'Settings saved.', 'telegram-bot' ),
			),
			30
		);

		wp_safe_redirect( add_query_arg( array( 'page' => $this->page_slug( $page ), 'myp_saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Send a test message.
	 *
	 * @return void
	 */
	public function handle_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'telegram-bot' ) );
		}

		check_admin_referer( 'myp_telegram_test' );

		$result = MYP_Telegram_API::instance()->send_test();

		set_transient(
			'myp_telegram_admin_notice',
			array(
				'type'    => $result['ok'] ? 'success' : 'error',
				'message' => $result['message'],
			),
			30
		);

		wp_safe_redirect( add_query_arg( array( 'page' => 'myp-telegram-bot', 'myp_tested' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Clear debug logs.
	 *
	 * @return void
	 */
	public function handle_clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'telegram-bot' ) );
		}

		check_admin_referer( 'myp_telegram_clear_logs' );

		MYP_Telegram_Logger::instance()->clear_logs();

		set_transient(
			'myp_telegram_admin_notice',
			array(
				'type'    => 'success',
				'message' => __( 'Logs cleared.', 'telegram-bot' ),
			),
			30
		);

		wp_safe_redirect( add_query_arg( array( 'page' => 'myp-telegram-logs', 'myp_cleared' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Extract and shape posted input.
	 *
	 * @param string $page Page key.
	 * @return array<string, mixed>
	 */
	private function input_for_page( $page ) {
		$posted  = wp_unslash( $_POST );
		$input   = array();
		$current = myp_telegram_settings()->get_settings();

		if ( 'settings' === $page ) {
			$input['enabled']                  = isset( $posted['enabled'] ) ? 1 : 0;
			$input['bot_token']                = isset( $posted['bot_token'] ) ? sanitize_text_field( $posted['bot_token'] ) : '';
			$input['chat_ids']                 = isset( $posted['chat_ids'] ) ? sanitize_text_field( $posted['chat_ids'] ) : '';
			$input['parse_mode']               = isset( $posted['parse_mode'] ) ? sanitize_key( $posted['parse_mode'] ) : '';
			$input['disable_web_page_preview'] = isset( $posted['disable_web_page_preview'] ) ? 1 : 0;
			$input['duplicate_ttl']            = isset( $posted['duplicate_ttl'] ) ? (int) $posted['duplicate_ttl'] : 15;
		}

		if ( 'triggers' === $page ) {
			$content_enabled = isset( $posted['content']['enabled'] ) ? 1 : 0;
			$media_enabled   = isset( $posted['media']['enabled'] ) ? 1 : 0;
			$comments_enabled = isset( $posted['comments']['enabled'] ) ? 1 : 0;
			$integrations_enabled = isset( $posted['integrations']['enabled'] ) ? 1 : 0;

			$input['content'] = array(
				'enabled'    => $content_enabled,
				'post_types' => isset( $posted['content']['post_types'] ) ? array_map( 'sanitize_key', (array) $posted['content']['post_types'] ) : array(),
				'events'     => isset( $posted['content']['events'] ) ? array_map( 'sanitize_key', (array) $posted['content']['events'] ) : ( $content_enabled ? array() : (array) $current['content']['events'] ),
			);
			$input['media'] = array(
				'enabled' => $media_enabled,
				'events'  => isset( $posted['media']['events'] ) ? array_map( 'sanitize_key', (array) $posted['media']['events'] ) : ( $media_enabled ? array() : (array) $current['media']['events'] ),
			);
			$input['comments'] = array(
				'enabled' => $comments_enabled,
				'events'  => isset( $posted['comments']['events'] ) ? array_map( 'sanitize_key', (array) $posted['comments']['events'] ) : ( $comments_enabled ? array() : (array) $current['comments']['events'] ),
			);
			$input['integrations'] = array(
				'enabled' => $integrations_enabled,
				'events'  => isset( $posted['integrations']['events'] ) ? array_map( 'sanitize_key', (array) $posted['integrations']['events'] ) : ( $integrations_enabled ? array() : (array) $current['integrations']['events'] ),
			);
		}

		if ( 'alerts' === $page ) {
			$users_enabled  = isset( $posted['users']['enabled'] ) ? 1 : 0;
			$system_enabled = isset( $posted['system']['enabled'] ) ? 1 : 0;

			$input['users'] = array(
				'enabled' => $users_enabled,
				'events'  => isset( $posted['users']['events'] ) ? array_map( 'sanitize_key', (array) $posted['users']['events'] ) : ( $users_enabled ? array() : (array) $current['users']['events'] ),
			);
			$input['system'] = array(
				'enabled' => $system_enabled,
				'events'  => isset( $posted['system']['events'] ) ? array_map( 'sanitize_key', (array) $posted['system']['events'] ) : ( $system_enabled ? array() : (array) $current['system']['events'] ),
			);
			$input['available_updates'] = array(
				'enabled'  => isset( $posted['available_updates']['enabled'] ) ? 1 : 0,
				'schedule' => isset( $posted['available_updates']['schedule'] ) ? sanitize_key( $posted['available_updates']['schedule'] ) : 'daily',
			);
		}

		return $input;
	}

	/**
	 * Render dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$settings = myp_telegram_settings()->get_settings();
		$this->page_header( __( 'Telegram Bot Dashboard', 'telegram-bot' ), __( 'Monitor configuration status and test message delivery.', 'telegram-bot' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Render Telegram settings.
	 *
	 * @return void
	 */
	public function render_settings() {
		$settings = myp_telegram_settings()->get_settings();
		$this->page_header( __( 'Telegram Settings', 'telegram-bot' ), __( 'Connect your BotFather bot and choose delivery options.', 'telegram-bot' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/telegram-settings.php';
	}

	/**
	 * Render trigger settings.
	 *
	 * @return void
	 */
	public function render_triggers() {
		$settings   = myp_telegram_settings()->get_settings();
		$post_types = myp_telegram_settings()->get_available_post_types();
		$this->page_header( __( 'Trigger Manager', 'telegram-bot' ), __( 'Choose which WordPress activity is forwarded to Telegram.', 'telegram-bot' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/triggers.php';
	}

	/**
	 * Render alert settings.
	 *
	 * @return void
	 */
	public function render_alerts() {
		$settings = myp_telegram_settings()->get_settings();
		$this->page_header( __( 'Alerts', 'telegram-bot' ), __( 'Configure user-action and system-update alerts.', 'telegram-bot' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/alerts.php';
	}

	/**
	 * Render templates and developer page.
	 *
	 * @return void
	 */
	public function render_templates() {
		$this->page_header( __( 'Templates & Developer', 'telegram-bot' ), __( 'Customize outgoing messages and integrate custom events.', 'telegram-bot' ) );
		include MYP_TELEGRAM_DIR . 'admin/views/templates.php';
	}

	/**
	 * Render logs.
	 *
	 * @return void
	 */
	public function render_logs() {
		$logs = MYP_Telegram_Logger::instance()->get_logs( 100 );
		$this->page_header( __( 'Logs', 'telegram-bot' ), __( 'Recent diagnostic entries. No bot tokens or private data are logged.', 'telegram-bot' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/logs.php';
	}

	/**
	 * Return admin page slug for a page key.
	 *
	 * @param string $page Page key.
	 * @return string
	 */
	private function page_slug( $page ) {
		return 'dashboard' === $page ? 'myp-telegram-bot' : 'myp-telegram-' . $page;
	}

	/**
	 * Event labels.
	 *
	 * @return array<string, string>
	 */
	private function content_events() {
		return array(
			'publish'       => __( 'Published', 'telegram-bot' ),
			'update'        => __( 'Edited without status change', 'telegram-bot' ),
			'create'        => __( 'Created as draft/pending/private', 'telegram-bot' ),
			'trash'         => __( 'Moved to trash', 'telegram-bot' ),
			'restore'       => __( 'Restored from trash', 'telegram-bot' ),
			'delete'        => __( 'Permanently deleted', 'telegram-bot' ),
			'status_change' => __( 'Other status changes', 'telegram-bot' ),
		);
	}

	/**
	 * Media event labels.
	 *
	 * @return array<string, string>
	 */
	private function media_events() {
		return array(
			'upload' => __( 'Media uploaded', 'telegram-bot' ),
		);
	}

	/**
	 * Comment event labels.
	 *
	 * @return array<string, string>
	 */
	private function comment_events() {
		return array(
			'new'      => __( 'New approved comment', 'telegram-bot' ),
			'pending'  => __( 'New pending comment', 'telegram-bot' ),
			'approved' => __( 'Comment approved/restored', 'telegram-bot' ),
			'trash'    => __( 'Comment moved to trash', 'telegram-bot' ),
			'spam'     => __( 'Comment marked as spam', 'telegram-bot' ),
			'delete'   => __( 'Comment permanently deleted', 'telegram-bot' ),
		);
	}

	/**
	 * User event labels.
	 *
	 * @return array<string, string>
	 */
	private function user_events() {
		return array(
			'register'       => __( 'New user registered', 'telegram-bot' ),
			'profile_update' => __( 'Profile updated', 'telegram-bot' ),
			'role_change'    => __( 'Role changed', 'telegram-bot' ),
			'login'          => __( 'Successful login', 'telegram-bot' ),
			'logout'         => __( 'Logout', 'telegram-bot' ),
			'failed_login'   => __( 'Failed login attempt', 'telegram-bot' ),
			'password_reset' => __( 'Password reset', 'telegram-bot' ),
			'delete'         => __( 'Account deleted', 'telegram-bot' ),
		);
	}

	/**
	 * System event labels.
	 *
	 * @return array<string, string>
	 */
	private function system_events() {
		return array(
			'plugin_activate'    => __( 'Plugin activated', 'telegram-bot' ),
			'plugin_deactivate'  => __( 'Plugin deactivated', 'telegram-bot' ),
			'plugin_delete'      => __( 'Plugin deleted', 'telegram-bot' ),
			'plugin_update'      => __( 'Plugin installed/updated', 'telegram-bot' ),
			'theme_switch'       => __( 'Active theme switched', 'telegram-bot' ),
			'theme_delete'       => __( 'Theme deleted', 'telegram-bot' ),
			'theme_update'       => __( 'Theme installed/updated', 'telegram-bot' ),
			'core_update'        => __( 'WordPress core updated', 'telegram-bot' ),
			'translation_update' => __( 'Language pack updated', 'telegram-bot' ),
		);
	}

	/**
	 * Integration event labels.
	 *
	 * @return array<string, string>
	 */
	private function integration_events() {
		return array(
			'woocommerce_order' => __( 'WooCommerce new order / status change', 'telegram-bot' ),
			'woocommerce_stock' => __( 'WooCommerce low stock / out of stock', 'telegram-bot' ),
			'contact_form_7'    => __( 'Contact Form 7 submission', 'telegram-bot' ),
			'wpforms'           => __( 'WPForms submission', 'telegram-bot' ),
			'fluentforms'       => __( 'Fluent Forms submission', 'telegram-bot' ),
			'ninja_forms'       => __( 'Ninja Forms submission', 'telegram-bot' ),
			'elementor_forms'   => __( 'Elementor Forms submission', 'telegram-bot' ),
			'gravity_forms'     => __( 'Gravity Forms submission', 'telegram-bot' ),
		);
	}

	/**
	 * Render a checkbox grid.
	 *
	 * @param string                $prefix  Prefix.
	 * @param array<string, string> $events  Labels.
	 * @param array<int, string>    $current Current values.
	 * @return void
	 */
	private function render_event_checkboxes( $prefix, $events, $current ) {
		$current = (array) $current;
		echo '<div class="myp-check-grid">';

		foreach ( $events as $key => $label ) {
			printf(
				'<label><input type="checkbox" name="%1$s[events][]" value="%2$s" %3$s> %4$s</label>',
				esc_attr( $prefix ),
				esc_attr( $key ),
				checked( in_array( $key, $current, true ), true, false ),
				esc_html( $label )
			);
		}

		echo '</div>';
	}

	/**
	 * Print page header.
	 *
	 * @param string $title       Title.
	 * @param string $description Description.
	 * @return void
	 */
	private function page_header( $title, $description ) {
		echo '<div class="wrap myp-wrap">';
		echo '<h1>' . esc_html( $title ) . '</h1>';
		echo '<p class="myp-lead">' . esc_html( $description ) . '</p>';
	}

	/**
	 * Print an admin notice.
	 *
	 * @return void
	 */
	private function render_notice() {
		$notice  = get_transient( 'myp_telegram_admin_notice' );
		$type    = '';
		$message = '';

		if ( isset( $_GET['myp_saved'] ) ) {
			$type    = 'success';
			$message = __( 'Settings saved.', 'telegram-bot' );
		}

		if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
			$type    = $notice['type'];
			$message = $notice['message'];
			delete_transient( 'myp_telegram_admin_notice' );
		}

		if ( $message ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $type ?: 'info' ), esc_html( $message ) );
		}
	}
}
