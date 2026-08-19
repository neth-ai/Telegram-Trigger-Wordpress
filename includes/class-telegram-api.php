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
	 * Most recent delivery error for the current request.
	 *
	 * @var string
	 */
	private $last_error = '';

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
		$this->last_error = '';

		if ( ! is_string( $message ) ) {
			$this->set_last_error( __( 'The Telegram message must be plain text.', 'telegram-bot' ) );

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
		$chats   = $settings->get_valid_chat_ids( $chat_list );

		if ( '' === $message ) {
			$this->set_last_error( __( 'The Telegram message is empty.', 'telegram-bot' ) );

			return false;
		}

		if ( ! $this->is_valid_token( $token ) ) {
			$this->set_last_error( __( 'The bot token format is invalid.', 'telegram-bot' ) );

			return false;
		}

		if ( ! $chats ) {
			$this->set_last_error( __( 'No valid Chat ID was found. Enter a numeric group or channel ID; the plugin adds one leading minus sign automatically.', 'telegram-bot' ) );

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
	 * Return the most recent delivery error for the current request.
	 *
	 * @return string
	 */
	public function get_last_error() {
		return $this->last_error;
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

		$message = MYP_Telegram_Template_Manager::instance()->format_message(
			'test',
			array(
				'site' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'time' => myp_telegram_format_datetime(),
			)
		);
		$sent = $this->send( $message );

		$error = $this->get_last_error();

		return array(
			'ok'      => $sent,
			'message' => $sent
				? __( 'Test message sent. Check Telegram.', 'telegram-bot' )
				: ( '' !== $error ? $error : __( 'Telegram rejected the request. Verify the token, Chat ID, and bot permissions.', 'telegram-bot' ) ),
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

		$has_urls = (bool) preg_match( '~https?://[^\s<>"\']+~iu', $message );
		$entities = $this->get_url_entities( $message );

		if ( $entities ) {
			$body['entities'] = wp_json_encode( $entities );
		} elseif ( ! $has_urls && '' !== $parse_mode ) {
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

		if ( is_wp_error( $response ) ) {
			$this->set_last_error( __( 'WordPress could not connect to Telegram. Check the server connection and try again.', 'telegram-bot' ) );

			return false;
		}

		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_array( $response_body ) || empty( $response_body['ok'] ) ) {
			$description = is_array( $response_body ) && isset( $response_body['description'] ) && is_scalar( $response_body['description'] )
				? sanitize_text_field( (string) $response_body['description'] )
				: '';

			$this->set_last_error( $this->friendly_telegram_error( $description ) );

			return false;
		}

		set_transient( $duplicate_key, 1, max( 1, (int) myp_telegram_settings()->get( 'duplicate_ttl', 15 ) ) );

		return true;
	}

	/**
	 * Build explicit Telegram URL entities for plain-text messages.
	 *
	 * Telegram entity offsets use UTF-16 code units, so byte offsets cannot be
	 * sent directly when emoji or Khmer text appears before a link.
	 *
	 * @param string $message Sanitized message.
	 * @return array<int, array<string, int|string>>
	 */
	private function get_url_entities( $message ) {
		$matched  = preg_match_all( '~https?://[^\s<>"\']+~iu', $message, $matches, PREG_OFFSET_CAPTURE );
		$entities = array();

		if ( ! $matched || empty( $matches[0] ) ) {
			return $entities;
		}

		foreach ( $matches[0] as $match ) {
			$url         = rtrim( $match[0], '.,;:!?' );
			$byte_offset = (int) $match[1];

			if ( '' === $url || ! $this->is_public_link_url( $url ) ) {
				continue;
			}

			$entities[] = array(
				'type'   => 'text_link',
				'offset' => $this->utf16_length( substr( $message, 0, $byte_offset ) ),
				'length' => $this->utf16_length( $url ),
				'url'    => $url,
			);
		}

		return $entities;
	}

	/**
	 * Check whether Telegram can use a URL as a text-link destination.
	 *
	 * Localhost, development TLDs, and private/reserved IP addresses are kept as
	 * plain text so Telegram does not reject the complete notification.
	 *
	 * @param string $url Candidate HTTP(S) URL.
	 * @return bool
	 */
	private function is_public_link_url( $url ) {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		if ( '' === $host || 'localhost' === $host || false === strpos( $host, '.' ) ) {
			return false;
		}

		foreach ( array( '.localhost', '.local', '.test', '.invalid', '.example' ) as $development_suffix ) {
			if ( str_ends_with( $host, $development_suffix ) ) {
				return false;
			}
		}

		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return (bool) filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}

		return true;
	}

	/**
	 * Count UTF-16 code units as required by Telegram MessageEntity offsets.
	 *
	 * @param string $text UTF-8 text.
	 * @return int
	 */
	private function utf16_length( $text ) {
		if ( function_exists( 'mb_convert_encoding' ) ) {
			return (int) ( strlen( mb_convert_encoding( $text, 'UTF-16LE', 'UTF-8' ) ) / 2 );
		}

		$characters = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$length     = 0;

		foreach ( is_array( $characters ) ? $characters : array() as $character ) {
			$length += 4 === strlen( $character ) ? 2 : 1;
		}

		return $length;
	}

	/**
	 * Convert a Telegram API description into useful setup guidance.
	 *
	 * @param string $description Telegram API description.
	 * @return string
	 */
	private function friendly_telegram_error( $description ) {
		$normalized = strtolower( $description );

		if ( false !== strpos( $normalized, 'chat not found' ) ) {
			return __( 'Telegram could not access this group or channel. Add the bot to that chat, grant posting permission, and verify the numeric ID.', 'telegram-bot' );
		}

		if ( false !== strpos( $normalized, 'bot was blocked' ) ) {
			return __( 'The Telegram user blocked this bot. Unblock it, open the bot, and send /start before testing again.', 'telegram-bot' );
		}

		if ( false !== strpos( $normalized, 'not enough rights' ) || false !== strpos( $normalized, 'not a member' ) ) {
			return __( 'The bot does not have permission to post in this group or channel. Add the bot and grant permission, then test again.', 'telegram-bot' );
		}

		if ( false !== strpos( $normalized, 'unauthorized' ) ) {
			return __( 'Telegram rejected the bot token. Copy the current token from @BotFather and save it again.', 'telegram-bot' );
		}

		if ( '' !== $description ) {
			return sprintf(
				/* translators: %s: Error description returned by the Telegram Bot API. */
				__( 'Telegram API error: %s', 'telegram-bot' ),
				$description
			);
		}

		return __( 'Telegram rejected the request. Verify the token, Chat ID, and bot permissions.', 'telegram-bot' );
	}

	/**
	 * Store and log a safe delivery error without tokens or Chat IDs.
	 *
	 * @param string $message Safe error message.
	 * @return void
	 */
	private function set_last_error( $message ) {
		$this->last_error = sanitize_text_field( (string) $message );

		if ( '' !== $this->last_error ) {
			MYP_Telegram_Logger::instance()->log( 'telegram_api', $this->last_error, 'error' );
		}
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
