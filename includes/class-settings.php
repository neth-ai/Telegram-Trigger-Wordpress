<?php
/**
 * Settings storage and defaults.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Settings
 */
class MYP_Telegram_Settings {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Settings|null
	 */
	private static $instance = null;

	/**
	 * Raw settings cache.
	 *
	 * @var array<string, mixed>|null
	 */
	private $settings = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Settings
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Ensure a stored option exists.
	 *
	 * @return void
	 */
	public function ensure_defaults() {
		if ( false === get_option( MYP_TELEGRAM_OPTION, false ) ) {
			add_option( MYP_TELEGRAM_OPTION, $this->get_defaults(), '', 'no' );
		} else {
			$stored   = get_option( MYP_TELEGRAM_OPTION, array() );
			$defaults = $this->get_defaults();
			$merged   = $this->deep_merge( $defaults, is_array( $stored ) ? $stored : array() );
			update_option( MYP_TELEGRAM_OPTION, $merged, 'no' );
		}

		$this->settings = null;
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings() {
		if ( null === $this->settings ) {
			$stored        = get_option( MYP_TELEGRAM_OPTION, array() );
			$this->settings = $this->deep_merge( $this->get_defaults(), is_array( $stored ) ? $stored : array() );
		}

		return $this->settings;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Dot-notation key, e.g. content.enabled.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	public function get( $key, $fallback = null ) {
		$value  = $this->get_settings();
		$pieces = explode( '.', $key );

		foreach ( $pieces as $piece ) {
			if ( ! is_array( $value ) || ! array_key_exists( $piece, $value ) ) {
				return $fallback;
			}

			$value = $value[ $piece ];
		}

		return $value;
	}

	/**
	 * Save a partial settings payload.
	 *
	 * @param array<string, mixed> $input Sanitized partial input.
	 * @return void
	 */
	public function save_partial( $input ) {
		$current = $this->get_settings();
		$merged  = $this->deep_merge( $current, $input );
		$merged  = $this->sanitize_full( $merged );

		update_option( MYP_TELEGRAM_OPTION, $merged, 'no' );
		$this->settings = $merged;
	}

	/**
	 * Check whether the bot configuration is complete.
	 *
	 * @return bool
	 */
	public function is_configured() {
		$token = $this->get( 'bot_token', '' );
		$chats = $this->get( 'chat_ids', '' );

		return '' !== trim( (string) $token ) && '' !== trim( (string) $chats );
	}

	/**
	 * Check whether a feature group is enabled.
	 *
	 * @param string $group Settings group.
	 * @return bool
	 */
	public function is_group_enabled( $group ) {
		if ( ! $this->get( 'enabled', 1 ) || ! $this->is_configured() ) {
			return false;
		}

		return (bool) $this->get( $group . '.enabled', 0 );
	}

	/**
	 * Check whether an event inside a group is enabled.
	 *
	 * @param string $group Group name.
	 * @param string $event Event key.
	 * @return bool
	 */
	public function is_event_enabled( $group, $event ) {
		if ( ! $this->is_group_enabled( $group ) ) {
			return false;
		}

		$events = (array) $this->get( $group . '.events', array() );

		return in_array( $event, $events, true );
	}

	/**
	 * Schedule the optional available-updates cron.
	 *
	 * @return void
	 */
	public function schedule_available_updates_cron() {
		if ( ! wp_next_scheduled( 'myp_telegram_available_updates' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'myp_telegram_available_updates' );
		}
	}

	/**
	 * Clear the optional available-updates cron.
	 *
	 * @return void
	 */
	public function clear_available_updates_cron() {
		wp_clear_scheduled_hook( 'myp_telegram_available_updates' );
	}

	/**
	 * Reschedule the available-updates cron using the saved frequency.
	 *
	 * @return void
	 */
	public function reschedule_available_updates_cron() {
		$this->clear_available_updates_cron();

		if ( ! $this->get( 'available_updates.enabled', 0 ) ) {
			return;
		}

		$schedule = $this->get( 'available_updates.schedule', 'daily' );
		$schedule = in_array( $schedule, array( 'hourly', 'twicedaily', 'daily' ), true ) ? $schedule : 'daily';

		wp_schedule_event( time() + HOUR_IN_SECONDS, $schedule, 'myp_telegram_available_updates' );
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults() {
		return array(
			'enabled'                 => 1,
			'bot_token'               => '',
			'chat_ids'                => '',
			'parse_mode'              => '',
			'disable_web_page_preview'=> 1,
			'duplicate_ttl'           => 15,
			'content'                 => array(
				'enabled'    => 1,
				'post_types' => array( 'post', 'page' ),
				'events'     => array( 'publish', 'update', 'create', 'trash', 'restore', 'delete', 'status_change' ),
			),
			'media'                   => array(
				'enabled' => 1,
				'events'  => array( 'upload' ),
			),
			'comments'                => array(
				'enabled' => 1,
				'events'  => array( 'new', 'pending', 'approved', 'trash', 'spam', 'delete' ),
			),
			'users'                   => array(
				'enabled' => 1,
				'events'  => array( 'register', 'profile_update', 'role_change', 'login', 'logout', 'failed_login', 'password_reset', 'delete' ),
			),
			'system'                  => array(
				'enabled' => 1,
				'events'  => array( 'plugin_activate', 'plugin_deactivate', 'plugin_delete', 'plugin_update', 'theme_switch', 'theme_delete', 'theme_update', 'core_update', 'translation_update' ),
			),
			'available_updates'       => array(
				'enabled' => 1,
				'schedule' => 'daily',
			),
			'integrations'            => array(
				'enabled' => 0,
				'events'  => array(
					'woocommerce_order',
					'woocommerce_stock',
					'contact_form_7',
					'wpforms',
					'fluentforms',
					'ninja_forms',
					'elementor_forms',
					'gravity_forms',
				),
			),
		);
	}

	/**
	 * Sanitize the full settings array.
	 *
	 * @param array<string, mixed> $settings Raw settings.
	 * @return array<string, mixed>
	 */
	private function sanitize_full( $settings ) {
		$defaults = $this->get_defaults();
		$settings = $this->deep_merge( $defaults, $settings );

		$settings['enabled']                  = empty( $settings['enabled'] ) ? 0 : 1;
		$settings['bot_token']                = sanitize_text_field( (string) $settings['bot_token'] );
		$settings['chat_ids']                 = sanitize_text_field( (string) $settings['chat_ids'] );
		$settings['parse_mode']               = $this->sanitize_parse_mode( $settings['parse_mode'] );
		$settings['disable_web_page_preview'] = empty( $settings['disable_web_page_preview'] ) ? 0 : 1;
		$settings['duplicate_ttl']            = max( 0, min( 3600, (int) $settings['duplicate_ttl'] ) );

		foreach ( array( 'content', 'media', 'comments', 'users', 'system', 'available_updates', 'integrations' ) as $group ) {
			if ( ! isset( $settings[ $group ] ) || ! is_array( $settings[ $group ] ) ) {
				$settings[ $group ] = $defaults[ $group ];
			}

			$settings[ $group ]['enabled'] = empty( $settings[ $group ]['enabled'] ) ? 0 : 1;

			if ( isset( $settings[ $group ]['events'] ) ) {
				$allowed                     = array_values( $defaults[ $group ]['events'] );
				$settings[ $group ]['events'] = array_values( array_intersect( (array) $settings[ $group ]['events'], $allowed ) );
			}
		}

		$allowed_post_types = array_keys( $this->get_available_post_types() );
		$settings['content']['post_types'] = array_values( array_intersect( (array) $settings['content']['post_types'], $allowed_post_types ) );

		$allowed_schedules = array( 'hourly', 'twicedaily', 'daily' );
		$schedule          = isset( $settings['available_updates']['schedule'] ) ? $settings['available_updates']['schedule'] : 'daily';

		if ( ! in_array( $schedule, $allowed_schedules, true ) ) {
			$schedule = 'daily';
		}

		$settings['available_updates']['schedule'] = $schedule;

		return $settings;
	}

	/**
	 * Return available trackable post types.
	 *
	 * @return array<string, string>
	 */
	public function get_available_post_types() {
		$types = get_post_types(
			array(
				'public'             => true,
				'publicly_queryable' => true,
			),
			'objects'
		);

		$result = array();

		foreach ( $types as $name => $object ) {
			$result[ $name ] = $object->labels->singular_name ?: $object->label;
		}

		$common = array(
			'post'        => __( 'Posts', 'telegram-bot' ),
			'page'        => __( 'Pages', 'telegram-bot' ),
			'news'        => __( 'News', 'telegram-bot' ),
			'documents'   => __( 'Documents', 'telegram-bot' ),
			'annoucement' => __( 'Announcements', 'telegram-bot' ),
		);

		foreach ( $common as $name => $label ) {
			$result[ $name ] = $label;
		}

		asort( $result );

		return $result;
	}

	/**
	 * Sanitize Telegram parse mode.
	 *
	 * @param mixed $mode Mode value.
	 * @return string
	 */
	private function sanitize_parse_mode( $mode ) {
		$mode       = sanitize_text_field( (string) $mode );
		$normalized = strtolower( $mode );

		if ( 'html' === $normalized ) {
			return 'HTML';
		}

		if ( 'markdownv2' === $normalized ) {
			return 'MarkdownV2';
		}

		return '';
	}

	/**
	 * Merge arrays recursively while preserving scalar values.
	 *
	 * @param array<string, mixed> $base Base values.
	 * @param array<string, mixed> $new  New values.
	 * @return array<string, mixed>
	 */
	private function deep_merge( $base, $new ) {
		$merged = $base;

		foreach ( $new as $key => $value ) {
			if (
				is_array( $value ) &&
				! wp_is_numeric_array( $value ) &&
				isset( $merged[ $key ] ) &&
				is_array( $merged[ $key ] )
			) {
				$merged[ $key ] = $this->deep_merge( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}
}
