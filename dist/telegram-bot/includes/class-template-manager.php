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
