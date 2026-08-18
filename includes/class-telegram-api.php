<?php
/**
 * Telegram Bot API sender.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_API
 */
class MYP_Telegram_API {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_API|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_API
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Send a Telegram message.
	 *
	 * @param string               $message Message body.
	 * @param array<string, mixed> $args    Optional overrides.
	 * @return bool
	 */
	public function send( $message, $args = array() ) {
		if ( ! is_string( $message ) ) {
			return false;
		}

		$settings  = myp_telegram_settings();
		$token     = isset( $args['token'] ) && '' !== (string) $args['token'] ? (string) $args['token'] : $settings->get( 'bot_token', '' );
		$chat_list = isset( $args['chat_ids'] ) && '' !== (string) $args['chat_ids'] ? (string) $args['chat_ids'] : $settings->get( 'chat_ids', '' );
		$parse_mode = isset( $args['parse_mode'] ) ? $args['parse_mode'] : $settings->get( 'parse_mode', '' );
		$parse_mode = in_array( $parse_mode, array( 'HTML', 'MarkdownV2' ), true ) ? $parse_mode : '';

		$message = apply_filters(
			'myp_telegram_message',
			$message,
			array(
				'event' => isset( $args['event'] ) ? $args['event'] : 'custom',
				'args'  => $args,
			)
		);
		$message = $this->sanitize_message( $message );
		$chats   = $this->sanitize_chat_ids( $chat_list );

		if ( '' === $message || ! $this->is_valid_token( $token ) || ! $chats ) {
			return false;
		}

		$sent_any = false;

		foreach ( $chats as $chat_id ) {
			if ( $this->send_to_chat( $token, $chat_id, $message, $parse_mode ) ) {
				$sent_any = true;
			}
		}

		return $sent_any;
	}

	/**
	 * Send a test message using stored configuration.
	 *
	 * @return array{ok: bool, message: string}
	 */
	public function send_test() {
		if ( ! myp_telegram_settings()->is_configured() ) {
			return array(
				'ok'      => false,
				'message' => __( 'Please save a valid bot token and at least one chat ID first.', 'telegram-bot' ),
			);
		}

		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$sent = $this->send(
			'✅ ' . sprintf(
				/* translators: %s: website name. */
				__( 'Telegram test message from %s is working.', 'telegram-bot' ),
				$site
			)
		);

		return array(
			'ok'      => $sent,
			'message' => $sent
				? __( 'Test message sent. Check Telegram.', 'telegram-bot' )
				: __( 'Telegram rejected the request. Verify the token, chat ID, and bot permissions.', 'telegram-bot' ),
		);
	}

	/**
	 * Send to one chat.
	 *
	 * @param string $token      Bot token.
	 * @param string $chat_id    Chat ID.
	 * @param string $message    Sanitized message.
	 * @param string $parse_mode Optional parse mode.
	 * @return bool
	 */
	private function send_to_chat( $token, $chat_id, $message, $parse_mode ) {
		$duplicate_key = 'myp_tg_' . hash( 'sha256', $token . "\n" . $chat_id . "\n" . $message );

		if ( false !== get_transient( $duplicate_key ) ) {
			return true;
		}

		$body = array(
			'chat_id'                  => $chat_id,
			'text'                     => $message,
			'disable_web_page_preview' => (bool) myp_telegram_settings()->get( 'disable_web_page_preview', 1 ),
		);

		if ( '' !== $parse_mode ) {
			$body['parse_mode'] = $parse_mode;
		}

		$response = wp_safe_remote_post(
			'https://api.telegram.org/bot' . rawurlencode( $token ) . '/sendMessage',
			array(
				'timeout'             => 8,
				'redirection'         => 0,
				'sslverify'           => true,
				'limit_response_size' => 65536,
				'headers'             => array(
					'Accept' => 'application/json',
				),
				'body'                => $body,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $response_body ) || empty( $response_body['ok'] ) ) {
			return false;
		}

		set_transient( $duplicate_key, 1, max( 1, (int) myp_telegram_settings()->get( 'duplicate_ttl', 15 ) ) );

		return true;
	}

	/**
	 * Validate token format.
	 *
	 * @param string $token Bot token.
	 * @return bool
	 */
	private function is_valid_token( $token ) {
		return (bool) preg_match( '/^[0-9]{5,15}:[A-Za-z0-9_-]{20,}$/', $token );
	}

	/**
	 * Validate chat ID format.
	 *
	 * @param string $chat_id Chat ID.
	 * @return bool
	 */
	private function is_valid_chat_id( $chat_id ) {
		return (bool) preg_match( '/^-?[0-9]{5,20}$/', $chat_id );
	}

	/**
	 * Parse and validate comma-separated chat IDs.
	 *
	 * @param string $chat_list Raw chat list.
	 * @return string[]
	 */
	private function sanitize_chat_ids( $chat_list ) {
		$ids   = array();
		$parts = array_filter( array_map( 'trim', explode( ',', (string) $chat_list ) ) );

		foreach ( $parts as $part ) {
			if ( $this->is_valid_chat_id( $part ) ) {
				$ids[] = $part;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Sanitize and shorten a message.
	 *
	 * @param string $message Raw message.
	 * @return string
	 */
	private function sanitize_message( $message ) {
		$message = wp_check_invalid_utf8( $message, true );
		$message = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', '', $message );
		$message = trim( (string) $message );

		if ( '' === $message ) {
			return '';
		}

		if ( function_exists( 'mb_strlen' ) && mb_strlen( $message, 'UTF-8' ) > 4000 ) {
			$message = rtrim( mb_substr( $message, 0, 3999, 'UTF-8' ) ) . '…';
		} elseif ( strlen( $message ) > 4000 ) {
			$message = substr( $message, 0, 3999 ) . '…';
		}

		return $message;
	}
}
