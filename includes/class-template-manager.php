<?php
/**
 * Message template and localization helpers.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Template_Manager
 */
class MYP_Telegram_Template_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Template_Manager|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Template_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Check whether the current locale is Khmer.
	 *
	 * @return bool
	 */
	public function is_khmer_locale() {
		return 0 === strpos( get_locale(), 'km' );
	}

	/**
	 * Message-format definitions used by the editor.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_format_definitions() {
		return array(
			'content'      => array(
				'title'        => __( 'Content activity', 'telegram-bot' ),
				'description'  => __( 'Posts, pages, media, and custom post type activity.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'type', 'action_text', 'content_title', 'by', 'time', 'categories', 'link' ),
			),
			'user'         => array(
				'title'        => __( 'User alerts', 'telegram-bot' ),
				'description'  => __( 'Registration, profile, role, login, logout, password, and deletion alerts.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'action_text', 'account_user', 'role', 'detail', 'by', 'time' ),
				'show_role'    => true,
			),
			'system'       => array(
				'title'        => __( 'System alerts', 'telegram-bot' ),
				'description'  => __( 'Plugin, theme, WordPress core, and language update activity.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'action_text', 'component', 'item', 'version', 'by', 'time' ),
			),
			'comment'      => array(
				'title'        => __( 'Comment alerts', 'telegram-bot' ),
				'description'  => __( 'New, pending, restored, spam, trash, and deleted comments.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'action_text', 'comment', 'author', 'content_title', 'link', 'time' ),
			),
			'failed_login' => array(
				'title'        => __( 'Failed login alerts', 'telegram-bot' ),
				'description'  => __( 'Unsuccessful WordPress login attempts.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'account_user', 'time' ),
			),
			'integration'  => array(
				'title'        => __( 'Optional integrations', 'telegram-bot' ),
				'description'  => __( 'WooCommerce and supported form-plugin notifications.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'integration_title', 'details', 'time' ),
			),
			'updates'      => array(
				'title'        => __( 'Available updates digest', 'telegram-bot' ),
				'description'  => __( 'Scheduled summaries of WordPress, plugin, and theme updates.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'details', 'time' ),
			),
			'test'         => array(
				'title'        => __( 'Test message', 'telegram-bot' ),
				'description'  => __( 'The connection-test message sent from the dashboard.', 'telegram-bot' ),
				'placeholders' => array( 'icon', 'site', 'time' ),
			),
		);
	}

	/**
	 * Example values for a message-format preview.
	 *
	 * @param string $type Format type.
	 * @return array<string, string>
	 */
	public function get_preview_values( $type ) {
		$values = array(
			'type'              => 'Post',
			'action_text'       => 'Deleted account',
			'content_title'     => 'Example title',
			'account_user'      => 'test',
			'role'              => 'Subscriber',
			'detail'            => 'Example detail',
			'by'                => 'admin',
			'time'              => myp_telegram_format_datetime(),
			'categories'        => 'News',
			'link'              => home_url( '/' ),
			'component'         => 'Plugin',
			'item'              => 'Telegram Bot Trigger Notifications',
			'version'           => MYP_TELEGRAM_VERSION,
			'comment'           => 'Example comment',
			'author'            => 'Visitor',
			'integration_title' => 'WooCommerce Order Update',
			'details'           => "Order: #1234\nStatus: Processing",
			'site'              => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		);

		if ( 'system' === $type ) {
			$values['action_text'] = 'Deactivated';
		} elseif ( 'content' === $type ) {
			$values['action_text'] = 'Moved to Trash';
		}

		return $values;
	}

	/**
	 * Render one configured Telegram message.
	 *
	 * Empty optional values remove their complete line. Unknown placeholders are
	 * left visible so an administrator can spot a template typo in a preview.
	 *
	 * @param string               $type   Format type.
	 * @param array<string, mixed> $values Dynamic values.
	 * @return string
	 */
	public function format_message( $type, $values = array() ) {
		$definitions = $this->get_format_definitions();
		$defaults    = myp_telegram_settings()->get_message_format_defaults();

		if ( ! isset( $definitions[ $type ], $defaults[ $type ] ) ) {
			return '';
		}

		$format   = myp_telegram_settings()->get( 'message_formats.' . $type, $defaults[ $type ] );
		$format   = is_array( $format ) ? array_merge( $defaults[ $type ], $format ) : $defaults[ $type ];
		$template = (string) $format['template'];
		$values   = is_array( $values ) ? $values : array();
		$values['icon'] = (string) $format['icon'];
		$values['time'] = isset( $values['time'] ) ? $values['time'] : myp_telegram_format_datetime();

		if ( 'user' === $type && empty( $format['show_role'] ) ) {
			$values['role'] = '';
		}

		$optional = array( 'categories', 'link', 'detail', 'role', 'item', 'version', 'content_title', 'details' );
		$lines    = explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $template ) );
		$output   = array();

		foreach ( $lines as $line ) {
			$omit = false;

			foreach ( $optional as $key ) {
				if ( false !== strpos( $line, '{' . $key . '}' ) && ( ! isset( $values[ $key ] ) || '' === trim( (string) $values[ $key ] ) ) ) {
					$omit = true;
					break;
				}
			}

			if ( ! $omit ) {
				$output[] = $line;
			}
		}

		$replacements = array();
		foreach ( $definitions[ $type ]['placeholders'] as $placeholder ) {
			$value = isset( $values[ $placeholder ] ) && is_scalar( $values[ $placeholder ] ) ? (string) $values[ $placeholder ] : '';
			$replacements[ '{' . $placeholder . '}' ] = $value;
		}

		$message = strtr( implode( "\n", $output ), $replacements );
		$message = preg_replace( "/\n{3,}/", "\n\n", trim( $message ) );

		/**
		 * Filter a formatted notification before Telegram delivery.
		 *
		 * @param string               $message Formatted message.
		 * @param string               $type    Format type.
		 * @param array<string, mixed> $values  Dynamic values.
		 */
		return (string) apply_filters( 'myp_telegram_formatted_message', $message, $type, $values );
	}

	/**
	 * Sanitize a scalar value for Telegram.
	 *
	 * @param mixed $value  Value.
	 * @param int   $length Max length.
	 * @return string
	 */
	public function safe_text( $value, $length = 250 ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = sanitize_text_field( $value );
		$value = preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', $value );
		$value = is_string( $value ) ? preg_replace( '/\s+/u', ' ', trim( $value ) ) : '';

		return wp_html_excerpt( (string) $value, max( 1, $length ), '…' );
	}

	/**
	 * Return a localized content type label.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public function content_type_label( $post ) {
		if ( 'attachment' === $post->post_type ) {
			$mime = get_post_mime_type( $post->ID );
			$base = $this->is_khmer_locale() ? 'មេឌា' : 'Media';

			return $mime ? $base . ' (' . $this->safe_text( $mime, 100 ) . ')' : $base;
		}

		$labels = array(
			'post'            => array( 'en' => 'Post', 'km' => 'អត្ថបទ' ),
			'page'            => array( 'en' => 'Page', 'km' => 'ទំព័រ' ),
			'attachment'      => array( 'en' => 'Media', 'km' => 'មេឌា' ),
			'news'            => array( 'en' => 'News', 'km' => 'ព័ត៌មាន' ),
			'annoucement'     => array( 'en' => 'Announcement', 'km' => 'សេចក្តីជូនដំណឹង' ),
			'documents'       => array( 'en' => 'Documents', 'km' => 'ឯកសារ' ),
			'applications'    => array( 'en' => 'Applications', 'km' => 'កម្មវិធី' ),
			'macro-economic'  => array( 'en' => 'Macro-economic', 'km' => 'ម៉ាក្រូសេដ្ឋកិច្ច' ),
			'minister'        => array( 'en' => 'Minister', 'km' => 'រដ្ឋមន្ត្រី' ),
			'manager-profile' => array( 'en' => 'Management structure', 'km' => 'រចនាសម្ព័ន្ធនៃការគ្រប់គ្រង' ),
			'services'        => array( 'en' => 'Public services', 'km' => 'សេវាសាធារណៈ' ),
			'under-minister'  => array( 'en' => 'Units under management', 'km' => 'អង្គភាពក្រោមការគ្រប់គ្រង ក.ស.ហ.វ' ),
			'slider'          => array( 'en' => 'Slider', 'km' => 'រូបភាពស្លាយ' ),
		);

		$labels = apply_filters( 'myp_telegram_content_type_labels', $labels );

		if ( isset( $labels[ $post->post_type ] ) ) {
			return $this->is_khmer_locale() ? $labels[ $post->post_type ]['km'] : $labels[ $post->post_type ]['en'];
		}

		$post_type = get_post_type_object( $post->post_type );

		if ( ! $post_type ) {
			return $this->safe_text( $post->post_type, 100 );
		}

		return $this->safe_text( $post_type->labels->singular_name ?: $post_type->label, 100 );
	}

	/**
	 * Readable post status label.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	public function status_label( $status ) {
		$status_object = get_post_status_object( $status );

		return $status_object ? $status_object->label : ucfirst( $status );
	}

	/**
	 * Safe user account label without exposing email.
	 *
	 * @param WP_User $user User.
	 * @return string
	 */
	public function user_account_label( $user ) {
		if ( ! $user instanceof WP_User || ! $user->exists() ) {
			return '';
		}

		$display_name = $this->safe_text( $user->display_name, 100 );
		$login_name   = $this->safe_text( $user->user_login, 60 );

		if ( '' === $display_name ) {
			return $login_name;
		}

		if ( '' === $login_name || $display_name === $login_name ) {
			return $display_name;
		}

		return $display_name . ' (@' . $login_name . ')';
	}

	/**
	 * Return localized roles.
	 *
	 * @param WP_User $user User.
	 * @return string
	 */
	public function user_roles( $user ) {
		if ( ! $user instanceof WP_User || ! $user->exists() ) {
			return '';
		}

		$role_registry = wp_roles();
		$labels        = array();

		foreach ( array_slice( $user->roles, 0, 10 ) as $role ) {
			$label = isset( $role_registry->roles[ $role ]['name'] )
				? translate_user_role( $role_registry->roles[ $role ]['name'] )
				: $role;
			$label = $this->safe_text( $label, 100 );

			if ( '' !== $label ) {
				$labels[] = $label;
			}
		}

		return $labels ? implode( ', ', $labels ) : __( 'No role', 'telegram-bot' );
	}
}
