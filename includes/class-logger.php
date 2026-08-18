<?php
/**
 * Small debug logger.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Logger
 */
class MYP_Telegram_Logger {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Logger|null
	 */
	private static $instance = null;

	/**
	 * Option key.
	 *
	 * @var string
	 */
	private $option_key = 'myp_telegram_logs';

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Logger
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $context Context.
	 * @param string $message Message.
	 * @param string $level   Log level.
	 * @return void
	 */
	public function log( $context, $message, $level = 'info' ) {
		$logs = $this->get_logs( 200 );

		array_unshift(
			$logs,
			array(
				'time'    => myp_telegram_format_datetime(),
				'context' => sanitize_key( (string) $context ),
				'message' => sanitize_text_field( (string) $message ),
				'level'   => in_array( $level, array( 'info', 'warning', 'error' ), true ) ? $level : 'info',
			)
		);

		$logs = array_slice( $logs, 0, 200 );

		update_option( $this->option_key, $logs, 'no' );
	}

	/**
	 * Return recent logs.
	 *
	 * @param int $limit Max number of entries.
	 * @return array<int, array<string, string>>
	 */
	public function get_logs( $limit = 50 ) {
		$logs = get_option( $this->option_key, array() );

		return is_array( $logs ) ? array_slice( $logs, 0, max( 1, (int) $limit ) ) : array();
	}

	/**
	 * Clear logs.
	 *
	 * @return void
	 */
	public function clear_logs() {
		delete_option( $this->option_key );
	}
}
