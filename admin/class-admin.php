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
		add_action( 'admin_menu', array( $this, 'register_menu' ), 999 );
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
			__( 'Telegram Bot', 'telegram-bot-trigger-notifications' ),
			__( 'Telegram-Bot', 'telegram-bot-trigger-notifications' ),
			'manage_options',
			'myp-telegram-bot',
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			null
		);

		add_submenu_page( 'myp-telegram-bot', __( 'Dashboard', 'telegram-bot-trigger-notifications' ), __( 'Dashboard', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-bot', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Telegram Settings', 'telegram-bot-trigger-notifications' ), __( 'Telegram Settings', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-settings', array( $this, 'render_settings' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Triggers', 'telegram-bot-trigger-notifications' ), __( 'Triggers', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-triggers', array( $this, 'render_triggers' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Alerts', 'telegram-bot-trigger-notifications' ), __( 'Alerts', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-alerts', array( $this, 'render_alerts' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Date & Time Format', 'telegram-bot-trigger-notifications' ), __( 'Date & Time Format', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-datetime', array( $this, 'render_datetime' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Message Format', 'telegram-bot-trigger-notifications' ), __( 'Message Format', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-message-format', array( $this, 'render_message_formats' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Templates', 'telegram-bot-trigger-notifications' ), __( 'Templates', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-templates', array( $this, 'render_templates' ) );
		add_submenu_page( 'myp-telegram-bot', __( 'Logs', 'telegram-bot-trigger-notifications' ), __( 'Logs', 'telegram-bot-trigger-notifications' ), 'manage_options', 'myp-telegram-logs', array( $this, 'render_logs' ) );
	}

	/**
	 * Enqueue assets on plugin screens.
	 *
	 * @param string $hook_suffix Current hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'myp-telegram' ) ) {
			return;
		}

		$style_path     = MYP_TELEGRAM_DIR . 'assets/css/admin.css';
		$script_path    = MYP_TELEGRAM_DIR . 'assets/js/admin.js';
		$style_version  = file_exists( $style_path ) ? (string) filemtime( $style_path ) : MYP_TELEGRAM_VERSION;
		$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : MYP_TELEGRAM_VERSION;

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'myp-telegram-admin', MYP_TELEGRAM_URL . 'assets/css/admin.css', array( 'dashicons' ), $style_version );
		wp_enqueue_script( 'myp-telegram-admin', MYP_TELEGRAM_URL . 'assets/js/admin.js', array(), $script_version, true );
	}

	/**
	 * Save settings from an admin-post request.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'telegram-bot-trigger-notifications' ) );
		}

		check_admin_referer( 'myp_telegram_save' );

		$posted = wp_unslash( $_POST );
		$page   = isset( $posted['myp_page'] ) ? sanitize_key( $posted['myp_page'] ) : 'dashboard';
		$input  = $this->input_for_page( $page, $posted );

		myp_telegram_settings()->save_partial( $input );
		myp_telegram_settings()->reschedule_available_updates_cron();

		set_transient(
			'myp_telegram_admin_notice',
			array(
				'type'    => 'success',
				'message' => __( 'Settings saved.', 'telegram-bot-trigger-notifications' ),
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'telegram-bot-trigger-notifications' ) );
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
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'telegram-bot-trigger-notifications' ) );
		}

		check_admin_referer( 'myp_telegram_clear_logs' );

		MYP_Telegram_Logger::instance()->clear_logs();

		set_transient(
			'myp_telegram_admin_notice',
			array(
				'type'    => 'success',
				'message' => __( 'Logs cleared.', 'telegram-bot-trigger-notifications' ),
			),
			30
		);

		wp_safe_redirect( add_query_arg( array( 'page' => 'myp-telegram-logs', 'myp_cleared' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Extract and shape posted input.
	 *
	 * @param string               $page   Page key.
	 * @param array<string, mixed> $posted Nonce-verified request data.
	 * @return array<string, mixed>
	 */
	private function input_for_page( $page, $posted ) {
		$input   = array();
		$current = myp_telegram_settings()->get_settings();

		if ( 'settings' === $page ) {
			$input['enabled']                  = isset( $posted['enabled'] ) ? 1 : 0;
			$input['bot_token']                = isset( $posted['bot_token'] ) ? sanitize_text_field( $posted['bot_token'] ) : '';
			$input['chat_ids']                 = isset( $posted['chat_ids'] ) ? sanitize_text_field( $posted['chat_ids'] ) : '';
			$input['parse_mode']               = isset( $posted['parse_mode'] ) ? sanitize_text_field( $posted['parse_mode'] ) : '';
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

		if ( 'datetime' === $page ) {
			$posted_datetime = isset( $posted['datetime'] ) && is_array( $posted['datetime'] ) ? $posted['datetime'] : array();
			$input['datetime'] = array(
				'date_order'     => isset( $posted_datetime['date_order'] ) ? sanitize_key( $posted_datetime['date_order'] ) : 'dmy',
				'month_format'   => isset( $posted_datetime['month_format'] ) ? sanitize_key( $posted_datetime['month_format'] ) : 'full',
				'year_format'    => isset( $posted_datetime['year_format'] ) ? sanitize_key( $posted_datetime['year_format'] ) : 'four',
				'date_separator' => isset( $posted_datetime['date_separator'] ) ? sanitize_key( $posted_datetime['date_separator'] ) : 'space',
				'time_format'    => isset( $posted_datetime['time_format'] ) ? sanitize_key( $posted_datetime['time_format'] ) : '24',
				'show_seconds'   => isset( $posted_datetime['show_seconds'] ) ? 1 : 0,
				'timezone'       => isset( $posted_datetime['timezone'] ) ? sanitize_text_field( $posted_datetime['timezone'] ) : 'Asia/Phnom_Penh',
			);
		}

		if ( 'message-format' === $page ) {
			$posted_formats = isset( $posted['message_formats'] ) && is_array( $posted['message_formats'] ) ? $posted['message_formats'] : array();
			$format_defaults = myp_telegram_settings()->get_message_format_defaults();
			$input['message_formats'] = array();

			foreach ( $format_defaults as $type => $defaults ) {
				$format = isset( $posted_formats[ $type ] ) && is_array( $posted_formats[ $type ] ) ? $posted_formats[ $type ] : array();
				$input['message_formats'][ $type ] = array(
					'icon'     => isset( $format['icon'] ) ? sanitize_text_field( $format['icon'] ) : $defaults['icon'],
					'template' => isset( $format['template'] ) ? sanitize_textarea_field( $format['template'] ) : $defaults['template'],
				);

				if ( array_key_exists( 'show_role', $defaults ) ) {
					$input['message_formats'][ $type ]['show_role'] = isset( $format['show_role'] ) ? 1 : 0;
				}
			}
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
		$this->page_header( __( 'Telegram Bot Dashboard', 'telegram-bot-trigger-notifications' ), __( 'Monitor configuration status and test message delivery.', 'telegram-bot-trigger-notifications' ) );
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
		$this->page_header( __( 'Telegram Settings', 'telegram-bot-trigger-notifications' ), __( 'Connect your BotFather bot and choose delivery options.', 'telegram-bot-trigger-notifications' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/telegram-settings.php';
	}

	/**
	 * Render trigger settings.
	 *
	 * @return void
	 */
	public function render_triggers() {
		$settings                 = myp_telegram_settings()->get_settings();
		$post_types               = myp_telegram_settings()->get_available_post_types();
		$integration_availability = MYP_Telegram_Integrations::get_availability();
		$this->page_header( __( 'Trigger Manager', 'telegram-bot-trigger-notifications' ), __( 'Choose which WordPress activity is forwarded to Telegram.', 'telegram-bot-trigger-notifications' ) );
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
		$this->page_header( __( 'Alerts', 'telegram-bot-trigger-notifications' ), __( 'Configure user-action and system-update alerts.', 'telegram-bot-trigger-notifications' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/alerts.php';
	}

	/**
	 * Render date and time format settings.
	 *
	 * @return void
	 */
	public function render_datetime() {
		$settings = myp_telegram_settings()->get_settings();
		$preview  = myp_telegram_format_datetime();
		$timezone = myp_telegram_settings()->get_datetime_timezone_label();
		$this->page_header( __( 'Date & Time Format', 'telegram-bot-trigger-notifications' ), __( 'Choose how dates and times appear in Telegram notifications and plugin logs.', 'telegram-bot-trigger-notifications' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/datetime.php';
	}

	/**
	 * Render editable Telegram message formats.
	 *
	 * @return void
	 */
	public function render_message_formats() {
		$settings    = myp_telegram_settings()->get_settings();
		$definitions = MYP_Telegram_Template_Manager::instance()->get_format_definitions();
		$this->page_header( __( 'Telegram Message Format', 'telegram-bot-trigger-notifications' ), __( 'Customize icons, wording, placeholders, and optional fields in outgoing Telegram messages.', 'telegram-bot-trigger-notifications' ) );
		$this->render_notice();
		include MYP_TELEGRAM_DIR . 'admin/views/message-format.php';
	}

	/**
	 * Render templates and developer page.
	 *
	 * @return void
	 */
	public function render_templates() {
		$this->page_header( __( 'Templates & Developer', 'telegram-bot-trigger-notifications' ), __( 'Customize outgoing messages and integrate custom events.', 'telegram-bot-trigger-notifications' ) );
		include MYP_TELEGRAM_DIR . 'admin/views/templates.php';
	}

	/**
	 * Render logs.
	 *
	 * @return void
	 */
	public function render_logs() {
		$logs = MYP_Telegram_Logger::instance()->get_logs( 100 );
		$this->page_header( __( 'Logs', 'telegram-bot-trigger-notifications' ), __( 'Recent diagnostic entries. No bot tokens or private data are logged.', 'telegram-bot-trigger-notifications' ) );
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
			'publish'       => __( 'Published', 'telegram-bot-trigger-notifications' ),
			'update'        => __( 'Edited without status change', 'telegram-bot-trigger-notifications' ),
			'create'        => __( 'Created as draft/pending/private', 'telegram-bot-trigger-notifications' ),
			'trash'         => __( 'Moved to trash', 'telegram-bot-trigger-notifications' ),
			'restore'       => __( 'Restored from trash', 'telegram-bot-trigger-notifications' ),
			'delete'        => __( 'Permanently deleted', 'telegram-bot-trigger-notifications' ),
			'status_change' => __( 'Other status changes', 'telegram-bot-trigger-notifications' ),
		);
	}

	/**
	 * Media event labels.
	 *
	 * @return array<string, string>
	 */
	private function media_events() {
		return array(
			'upload' => __( 'Media uploaded', 'telegram-bot-trigger-notifications' ),
		);
	}

	/**
	 * Comment event labels.
	 *
	 * @return array<string, string>
	 */
	private function comment_events() {
		return array(
			'new'      => __( 'New approved comment', 'telegram-bot-trigger-notifications' ),
			'pending'  => __( 'New pending comment', 'telegram-bot-trigger-notifications' ),
			'approved' => __( 'Comment approved/restored', 'telegram-bot-trigger-notifications' ),
			'trash'    => __( 'Comment moved to trash', 'telegram-bot-trigger-notifications' ),
			'spam'     => __( 'Comment marked as spam', 'telegram-bot-trigger-notifications' ),
			'delete'   => __( 'Comment permanently deleted', 'telegram-bot-trigger-notifications' ),
		);
	}

	/**
	 * User event labels.
	 *
	 * @return array<string, string>
	 */
	private function user_events() {
		return array(
			'register'       => __( 'New user registered', 'telegram-bot-trigger-notifications' ),
			'profile_update' => __( 'Profile updated', 'telegram-bot-trigger-notifications' ),
			'role_change'    => __( 'Role changed', 'telegram-bot-trigger-notifications' ),
			'login'          => __( 'Successful login', 'telegram-bot-trigger-notifications' ),
			'logout'         => __( 'Logout', 'telegram-bot-trigger-notifications' ),
			'failed_login'   => __( 'Failed login attempt', 'telegram-bot-trigger-notifications' ),
			'password_reset' => __( 'Password reset', 'telegram-bot-trigger-notifications' ),
			'delete'         => __( 'Account deleted', 'telegram-bot-trigger-notifications' ),
		);
	}

	/**
	 * System event labels.
	 *
	 * @return array<string, string>
	 */
	private function system_events() {
		return array(
			'plugin_activate'    => __( 'Plugin activated', 'telegram-bot-trigger-notifications' ),
			'plugin_deactivate'  => __( 'Plugin deactivated', 'telegram-bot-trigger-notifications' ),
			'plugin_delete'      => __( 'Plugin deleted', 'telegram-bot-trigger-notifications' ),
			'plugin_update'      => __( 'Plugin installed/updated', 'telegram-bot-trigger-notifications' ),
			'theme_switch'       => __( 'Active theme switched', 'telegram-bot-trigger-notifications' ),
			'theme_delete'       => __( 'Theme deleted', 'telegram-bot-trigger-notifications' ),
			'theme_update'       => __( 'Theme installed/updated', 'telegram-bot-trigger-notifications' ),
			'core_update'        => __( 'WordPress core updated', 'telegram-bot-trigger-notifications' ),
			'translation_update' => __( 'Language pack updated', 'telegram-bot-trigger-notifications' ),
		);
	}

	/**
	 * Integration event labels.
	 *
	 * @return array<string, string>
	 */
	private function integration_events() {
		return array(
			'woocommerce_order' => __( 'WooCommerce new order / status change', 'telegram-bot-trigger-notifications' ),
			'woocommerce_stock' => __( 'WooCommerce low stock / out of stock', 'telegram-bot-trigger-notifications' ),
			'contact_form_7'    => __( 'Contact Form 7 submission', 'telegram-bot-trigger-notifications' ),
			'wpforms'           => __( 'WPForms submission', 'telegram-bot-trigger-notifications' ),
			'fluentforms'       => __( 'Fluent Forms submission', 'telegram-bot-trigger-notifications' ),
			'ninja_forms'       => __( 'Ninja Forms submission', 'telegram-bot-trigger-notifications' ),
			'elementor_forms'   => __( 'Elementor Forms submission', 'telegram-bot-trigger-notifications' ),
			'gravity_forms'     => __( 'Gravity Forms submission', 'telegram-bot-trigger-notifications' ),
		);
	}

	/**
	 * Render a checkbox grid.
	 *
	 * @param string                $prefix  Prefix.
	 * @param array<string, string> $events  Labels.
	 * @param array<int, string>    $current      Current values.
	 * @param array<string, bool>   $availability Optional availability keyed by event.
	 * @return void
	 */
	private function render_event_checkboxes( $prefix, $events, $current, $availability = array() ) {
		$current = (array) $current;
		echo '<div class="myp-check-grid">';

		foreach ( $events as $key => $label ) {
			$is_available = ! array_key_exists( $key, $availability ) || ! empty( $availability[ $key ] );
			$choice_class = $is_available ? 'myp-choice' : 'myp-choice myp-choice--unavailable';

			printf(
				'<label class="%1$s"><input type="checkbox" name="%2$s[events][]" value="%3$s" %4$s %5$s><span>%6$s',
				esc_attr( $choice_class ),
				esc_attr( $prefix ),
				esc_attr( $key ),
				checked( in_array( $key, $current, true ), true, false ),
				disabled( ! $is_available, true, false ),
				esc_html( $label )
			);

			if ( ! $is_available ) {
				echo '<small>' . esc_html__( 'Plugin not active', 'telegram-bot-trigger-notifications' ) . '</small>';
			}

			echo '</span></label>';
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
		$version_label = 'v' . MYP_TELEGRAM_VERSION;

		echo '<div class="wrap myp-wrap">';
		echo '<header class="myp-page-header">';
		echo '<div class="myp-page-header__icon" aria-hidden="true"><span class="dashicons dashicons-format-chat"></span></div>';
		echo '<div class="myp-page-header__copy">';
		echo '<span class="myp-eyebrow">' . esc_html__( 'Telegram Bot Trigger Notifications', 'telegram-bot-trigger-notifications' ) . '</span>';
		echo '<h1>' . esc_html( $title ) . '</h1>';
		echo '<p class="myp-lead">' . esc_html( $description ) . '</p>';
		echo '</div>';
		echo '<span class="myp-version">' . esc_html( $version_label ) . '</span>';
		echo '</header>';
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
