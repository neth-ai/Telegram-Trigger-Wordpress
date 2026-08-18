<?php
/**
 * Core event triggers.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Trigger_Manager
 */
class MYP_Telegram_Trigger_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Trigger_Manager|null
	 */
	private static $instance = null;

	/**
	 * IDs created in the current request.
	 *
	 * @var array<int, bool>
	 */
	private static $new_user_ids = array();

	/**
	 * IDs whose role changed in the current request.
	 *
	 * @var array<int, bool>
	 */
	private static $role_change_ids = array();

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Trigger_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		add_action( 'transition_post_status', array( $this, 'content_status_changed' ), 10, 3 );
		add_action( 'wp_after_insert_post', array( $this, 'content_created' ), 10, 4 );
		add_action( 'post_updated', array( $this, 'content_updated' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'content_deleted' ), 10, 2 );
		add_action( 'add_attachment', array( $this, 'media_uploaded' ) );

		add_action( 'comment_post', array( $this, 'comment_posted' ), 20, 3 );
		add_action( 'transition_comment_status', array( $this, 'comment_status_changed' ), 20, 3 );
		add_action( 'delete_comment', array( $this, 'comment_deleted' ), 10, 2 );
	}

	/**
	 * Check whether locale is Khmer.
	 *
	 * @return bool
	 */
	private function is_khmer_locale() {
		return 0 === strpos( get_locale(), 'km' );
	}

	/**
	 * Safe text helper.
	 *
	 * @param mixed $value  Value.
	 * @param int   $length Max length.
	 * @return string
	 */
	private function safe_text( $value, $length = 250 ) {
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
	 * Determine whether a post can be tracked by content triggers.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return bool
	 */
	private function is_trackable_content( $post ) {
		$post = get_post( $post );

		if ( ! $post || wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
			return false;
		}

		if ( 'attachment' === $post->post_type ) {
			return false;
		}

		$selected = (array) myp_telegram_settings()->get( 'content.post_types', array() );

		return in_array( $post->post_type, $selected, true );
	}

	/**
	 * Get a localized post-type label.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	private function content_type_label( $post ) {
		if ( 'attachment' === $post->post_type ) {
			$mime = get_post_mime_type( $post->ID );

			return $this->is_khmer_locale()
				? 'មេឌា' . ( $mime ? ' (' . $this->safe_text( $mime, 100 ) . ')' : '' )
				: 'Media' . ( $mime ? ' (' . $this->safe_text( $mime, 100 ) . ')' : '' );
		}

		$labels = array(
			'post'           => array( 'en' => 'Post', 'km' => 'អត្ថបទ' ),
			'page'           => array( 'en' => 'Page', 'km' => 'ទំព័រ' ),
			'attachment'     => array( 'en' => 'Media', 'km' => 'មេឌា' ),
			'news'           => array( 'en' => 'News', 'km' => 'ព័ត៌មាន' ),
			'annoucement'    => array( 'en' => 'Announcement', 'km' => 'សេចក្តីជូនដំណឹង' ),
			'documents'      => array( 'en' => 'Documents', 'km' => 'ឯកសារ' ),
			'applications'   => array( 'en' => 'Applications', 'km' => 'កម្មវិធី' ),
			'macro-economic' => array( 'en' => 'Macro-economic', 'km' => 'ម៉ាក្រូសេដ្ឋកិច្ច' ),
			'minister'       => array( 'en' => 'Minister', 'km' => 'រដ្ឋមន្ត្រី' ),
			'manager-profile'=> array( 'en' => 'Management structure', 'km' => 'រចនាសម្ព័ន្ធនៃការគ្រប់គ្រង' ),
			'services'       => array( 'en' => 'Public services', 'km' => 'សេវាសាធារណៈ' ),
			'under-minister' => array( 'en' => 'Units under management', 'km' => 'អង្គភាពក្រោមការគ្រប់គ្រង ក.ស.ហ.វ' ),
			'slider'         => array( 'en' => 'Slider', 'km' => 'រូបភាពស្លាយ' ),
		);

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
	 * Return a safe same-domain URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	private function safe_public_url( $url ) {
		$url = esc_url_raw( (string) $url, array( 'http', 'https' ) );

		if ( '' === $url ) {
			return '';
		}

		$url_host  = wp_parse_url( $url, PHP_URL_HOST );
		$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( ! $url_host || ! $site_host || 0 !== strcasecmp( $url_host, $site_host ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Get content URL for published posts.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function content_url( $post ) {
		if ( 'publish' !== $post->post_status ) {
			return '';
		}

		return $this->safe_public_url( get_permalink( $post->ID ) ?: '' );
	}

	/**
	 * Get the current action user name.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function action_user( $post ) {
		$user = wp_get_current_user();

		if ( $user->exists() ) {
			return $this->safe_text( $user->display_name, 100 );
		}

		$author = get_userdata( (int) $post->post_author );

		return $author ? $this->safe_text( $author->display_name, 100 ) : $this->current_user_name();
	}

	/**
	 * Current WordPress user or system.
	 *
	 * @return string
	 */
	private function current_user_name() {
		$user = wp_get_current_user();

		return $user->exists()
			? $this->safe_text( $user->display_name, 100 )
			: $this->safe_text( __( 'WordPress/System', 'telegram-bot' ), 100 );
	}

	/**
	 * Content public terms.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function content_terms( $post ) {
		$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
		$groups     = array();

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! $taxonomy->public || 'post_format' === $taxonomy->name ) {
				continue;
			}

			$terms = wp_get_object_terms( $post->ID, $taxonomy->name, array( 'fields' => 'names' ) );

			if ( ! $terms || is_wp_error( $terms ) ) {
				continue;
			}

			$safe_terms = array();

			foreach ( array_slice( $terms, 0, 20 ) as $term ) {
				$safe_term = $this->safe_text( $term, 100 );

				if ( '' !== $safe_term ) {
					$safe_terms[] = $safe_term;
				}
			}

			if ( $safe_terms ) {
				$groups[] = $this->safe_text( $taxonomy->labels->singular_name, 100 ) . ': ' . implode( ', ', $safe_terms );
			}
		}

		return wp_html_excerpt( implode( ' | ', $groups ), 500, '…' );
	}

	/**
	 * Send a content activity alert.
	 *
	 * @param WP_Post|int $post   Post object or ID.
	 * @param string      $action Action label.
	 * @param string|null $url    Optional URL.
	 * @return bool
	 */
	public function send_content_alert( $post, $action, $url = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return false;
		}

		$title       = get_the_title( $post->ID );
		$title       = '' !== $title ? $title : __( 'Untitled', 'telegram-bot' );
		$title       = $this->safe_text( $title, 250 );
		$terms       = $this->content_terms( $post );
		$url         = null === $url ? $this->content_url( $post ) : $this->safe_public_url( (string) $url );
		$action      = $this->safe_text( $action, 150 );
		$is_published = 'publish' === $post->post_status;

		$khmer = $this->is_khmer_locale();
		$lines = array(
			$khmer ? '🔔 Telegram Content Activity' : '🔔 Telegram Content Activity',
			'',
			( $khmer ? 'មុីនុយ: ' : 'Type: ' ) . $this->content_type_label( $post ),
			( $khmer ? 'សកម្មភាព: ' : 'Action: ' ) . $action,
			( $khmer ? 'ចំណងជើង: ' : 'Title: ' ) . $title,
			( $khmer ? 'អនុវត្តដោយ: ' : 'By: ' ) . $this->action_user( $post ),
			( $khmer ? 'ពេលវេលា: ' : 'Time: ' ) . wp_date( 'd F Y, H:i:s', null, wp_timezone() ),
		);

		if ( $is_published && '' !== $terms ) {
			$lines[] = ( $khmer ? 'Categories: ' : 'Categories: ' ) . $terms;
		}

		if ( $is_published && '' !== $url ) {
			$lines[] = ( $khmer ? 'Link: ' : 'Link: ' ) . $url;
		}

		return MYP_Telegram_API::instance()->send( implode( "\n", $lines ) );
	}

	/**
	 * Send a system alert.
	 *
	 * @param string $action    Action.
	 * @param string $component Component type.
	 * @param string $item      Item name.
	 * @param string $version   Version detail.
	 * @return bool
	 */
	public function send_system_alert( $action, $component, $item = '', $version = '' ) {
		$action    = $this->safe_text( $action, 150 );
		$component = $this->safe_text( $component, 100 );
		$item      = $this->safe_text( $item, 500 );
		$version   = $this->safe_text( $version, 300 );

		if ( '' === $action || '' === $component ) {
			return false;
		}

		$khmer = $this->is_khmer_locale();
		$lines = array(
			$khmer ? '🟥 SYSTEM ALERT' : '🟥 SYSTEM ALERT',
			'',
			( $khmer ? 'សកម្មភាព: ' : 'Action: ' ) . $action,
			( $khmer ? 'ប្រភេទ: ' : 'Component: ' ) . $component,
		);

		if ( '' !== $item ) {
			$lines[] = ( $khmer ? 'ឈ្មោះ: ' : 'Item: ' ) . $item;
		}

		if ( '' !== $version ) {
			$lines[] = ( $khmer ? 'កំណែ: ' : 'Version: ' ) . $version;
		}

		$lines[] = ( $khmer ? 'អនុវត្តដោយ: ' : 'By: ' ) . $this->current_user_name();
		$lines[] = ( $khmer ? 'ពេលវេលា: ' : 'Time: ' ) . wp_date( 'd F Y, H:i:s', null, wp_timezone() );

		return MYP_Telegram_API::instance()->send( implode( "\n", $lines ) );
	}

	/**
	 * Send a user alert.
	 *
	 * @param string       $action Action.
	 * @param WP_User|int  $user   User or ID.
	 * @param string       $detail Optional detail.
	 * @param WP_User|null $actor  Acting user.
	 * @return bool
	 */
	public function send_user_alert( $action, $user, $detail = '', $actor = null ) {
		$user = $user instanceof WP_User ? $user : get_userdata( (int) $user );

		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		$action  = $this->safe_text( $action, 150 );
		$detail  = $this->safe_text( $detail, 300 );
		$account = $this->user_account_label( $user );

		if ( '' === $action || '' === $account ) {
			return false;
		}

		$actor_name = $actor instanceof WP_User
			? $this->user_account_label( $actor )
			: $this->current_user_name();

		$khmer = $this->is_khmer_locale();
		$lines = array(
			$khmer ? '🟥 USER ALERT' : '🟥 USER ALERT',
			'',
			( $khmer ? 'សកម្មភាព: ' : 'Action: ' ) . $action,
			( $khmer ? 'គណនី: ' : 'Account: ' ) . $account,
			( $khmer ? 'តួនាទី: ' : 'Role: ' ) . $this->user_roles( $user ),
		);

		if ( '' !== $detail ) {
			$lines[] = ( $khmer ? 'ព័ត៌មាន: ' : 'Detail: ' ) . $detail;
		}

		$lines[] = ( $khmer ? 'អនុវត្តដោយ: ' : 'By: ' ) . $actor_name;
		$lines[] = ( $khmer ? 'ពេលវេលា: ' : 'Time: ' ) . wp_date( 'd F Y, H:i:s', null, wp_timezone() );

		return MYP_Telegram_API::instance()->send( implode( "\n", $lines ) );
	}

	/**
	 * User account label without exposing email.
	 *
	 * @param WP_User $user User.
	 * @return string
	 */
	private function user_account_label( $user ) {
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
	 * Localized roles.
	 *
	 * @param WP_User $user User.
	 * @return string
	 */
	private function user_roles( $user ) {
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

	/**
	 * Readable post status label.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private function status_label( $status ) {
		$status_object = get_post_status_object( $status );

		return $status_object ? $status_object->label : ucfirst( $status );
	}

	/**
	 * Handle content status transition.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post.
	 * @return void
	 */
	public function content_status_changed( $new_status, $old_status, $post ) {
		if ( $new_status === $old_status || 'auto-draft' === $new_status || ! $this->is_trackable_content( $post ) ) {
			return;
		}

		if ( 'attachment' === $post->post_type && 'inherit' === $new_status ) {
			return;
		}

		$khmer = $this->is_khmer_locale();

		if ( 'publish' === $new_status && myp_telegram_settings()->is_event_enabled( 'content', 'publish' ) ) {
			$action = $khmer ? 'Published' : 'Published';
			$this->send_content_alert( $post, $action );
		} elseif ( 'trash' === $new_status && myp_telegram_settings()->is_event_enabled( 'content', 'trash' ) ) {
			$this->send_content_alert( $post, $khmer ? 'Moved to Trash' : 'Moved to Trash' );
		} elseif ( 'trash' === $old_status && myp_telegram_settings()->is_event_enabled( 'content', 'restore' ) ) {
			$this->send_content_alert( $post, $khmer ? 'Restored from Trash' : 'Restored from Trash' );
		} elseif ( myp_telegram_settings()->is_event_enabled( 'content', 'status_change' ) ) {
			$action = sprintf(
				$khmer ? 'Status changed: %1$s → %2$s' : 'Status changed: %1$s → %2$s',
				$this->status_label( $old_status ),
				$this->status_label( $new_status )
			);
			$this->send_content_alert( $post, $action );
		}
	}

	/**
	 * Handle creation of non-published content.
	 *
	 * @param int          $post_id     Post ID.
	 * @param WP_Post      $post        Post.
	 * @param bool         $update      Whether update.
	 * @param WP_Post|null $post_before Previous post.
	 * @return void
	 */
	public function content_created( $post_id, $post, $update, $post_before ) {
		unset( $post_id, $post_before );

		if ( $update || in_array( $post->post_status, array( 'auto-draft', 'publish', 'inherit', 'trash' ), true ) ) {
			return;
		}

		if ( ! myp_telegram_settings()->is_event_enabled( 'content', 'create' ) || ! $this->is_trackable_content( $post ) ) {
			return;
		}

		$this->send_content_alert( $post, $this->is_khmer_locale() ? 'Created' : 'Created' );
	}

	/**
	 * Handle edits without a status change.
	 *
	 * @param int     $post_id     Post ID.
	 * @param WP_Post $post_after  Post after update.
	 * @param WP_Post $post_before Post before update.
	 * @return void
	 */
	public function content_updated( $post_id, $post_after, $post_before ) {
		unset( $post_id );

		if ( $post_after->post_status !== $post_before->post_status || ! $this->is_trackable_content( $post_after ) ) {
			return;
		}

		if ( ! myp_telegram_settings()->is_event_enabled( 'content', 'update' ) ) {
			return;
		}

		$tracked_fields = array(
			'post_title',
			'post_content',
			'post_excerpt',
			'post_name',
			'post_parent',
			'menu_order',
			'comment_status',
			'ping_status',
		);

		foreach ( $tracked_fields as $field ) {
			if ( $post_after->{$field} !== $post_before->{$field} ) {
				$this->send_content_alert( $post_after, $this->is_khmer_locale() ? 'Edited' : 'Edited' );

				return;
			}
		}
	}

	/**
	 * Handle permanent content deletion.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @return void
	 */
	public function content_deleted( $post_id, $post ) {
		unset( $post_id );

		if ( ! myp_telegram_settings()->is_event_enabled( 'content', 'delete' ) || ! $this->is_trackable_content( $post ) ) {
			return;
		}

		$this->send_content_alert( $post, $this->is_khmer_locale() ? 'Permanently deleted' : 'Permanently deleted', '' );
	}

	/**
	 * Handle media upload.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function media_uploaded( $attachment_id ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'media', 'upload' ) ) {
			return;
		}

		$attachment = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return;
		}

		$this->send_content_alert( $attachment, $this->is_khmer_locale() ? 'Uploaded' : 'Uploaded' );
	}

	/**
	 * Handle a newly inserted comment.
	 *
	 * @param int        $comment_id       Comment ID.
	 * @param int|string $comment_approved Approval status.
	 * @param array      $commentdata      Comment data.
	 * @return void
	 */
	public function comment_posted( $comment_id, $comment_approved, $commentdata ) {
		$comment = get_comment( $comment_id );

		if ( ! $comment ) {
			return;
		}

		if ( 1 === (int) $comment_approved || 'approve' === $comment_approved ) {
			if ( myp_telegram_settings()->is_event_enabled( 'comments', 'new' ) ) {
				$this->send_comment_alert( $comment, $this->is_khmer_locale() ? 'New comment' : 'New comment' );
			}
		} elseif ( myp_telegram_settings()->is_event_enabled( 'comments', 'pending' ) ) {
			$this->send_comment_alert( $comment, $this->is_khmer_locale() ? 'Pending comment' : 'Pending comment' );
		}

		unset( $commentdata );
	}

	/**
	 * Handle comment status transitions.
	 *
	 * @param string     $new_status New status.
	 * @param string     $old_status Old status.
	 * @param WP_Comment $comment    Comment.
	 * @return void
	 */
	public function comment_status_changed( $new_status, $old_status, $comment ) {
		if ( $new_status === $old_status ) {
			return;
		}

		$khmer = $this->is_khmer_locale();

		if ( 'approved' === $new_status && myp_telegram_settings()->is_event_enabled( 'comments', 'approved' ) ) {
			$this->send_comment_alert( $comment, $khmer ? 'Comment approved' : 'Comment approved' );
		} elseif ( 'trash' === $new_status && myp_telegram_settings()->is_event_enabled( 'comments', 'trash' ) ) {
			$this->send_comment_alert( $comment, $khmer ? 'Comment moved to trash' : 'Comment moved to trash' );
		} elseif ( 'spam' === $new_status && myp_telegram_settings()->is_event_enabled( 'comments', 'spam' ) ) {
			$this->send_comment_alert( $comment, $khmer ? 'Comment marked as spam' : 'Comment marked as spam' );
		} elseif ( in_array( $old_status, array( 'trash', 'spam' ), true ) && myp_telegram_settings()->is_event_enabled( 'comments', 'approved' ) ) {
			$this->send_comment_alert( $comment, $khmer ? 'Comment restored' : 'Comment restored' );
		}
	}

	/**
	 * Handle permanent comment deletion.
	 *
	 * @param int        $comment_id Comment ID.
	 * @param WP_Comment $comment    Comment.
	 * @return void
	 */
	public function comment_deleted( $comment_id, $comment ) {
		unset( $comment_id );

		if ( ! myp_telegram_settings()->is_event_enabled( 'comments', 'delete' ) || ! $comment ) {
			return;
		}

		$this->send_comment_alert( $comment, $this->is_khmer_locale() ? 'Comment deleted' : 'Comment deleted' );
	}

	/**
	 * Send a comment alert.
	 *
	 * @param WP_Comment $comment Comment.
	 * @param string     $action  Action.
	 * @return bool
	 */
	public function send_comment_alert( $comment, $action ) {
		$post = get_post( $comment->comment_post_ID );
		$khmer = $this->is_khmer_locale();
		$lines = array(
			'💬 ' . ( $khmer ? 'Comment Alert' : 'Comment Alert' ),
			'',
			( $khmer ? 'សកម្មភាព: ' : 'Action: ' ) . $this->safe_text( $action, 150 ),
			( $khmer ? 'មតិ: ' : 'Comment: ' ) . $this->safe_text( $comment->comment_content, 300 ),
			( $khmer ? 'អ្នកសរសេរ: ' : 'Author: ' ) . $this->safe_text( $comment->comment_author, 100 ),
		);

		if ( $post ) {
			$title = get_the_title( $post->ID );
			$lines[] = ( $khmer ? 'លើអត្ថបទ: ' : 'On: ' ) . $this->safe_text( '' !== $title ? $title : __( 'Untitled', 'telegram-bot' ), 250 );

			$url = $this->safe_public_url( get_comment_link( $comment ) ?: '' );
			if ( '' !== $url ) {
				$lines[] = ( $khmer ? 'Link: ' : 'Link: ' ) . $url;
			}
		}

		$lines[] = ( $khmer ? 'ពេលវេលា: ' : 'Time: ' ) . wp_date( 'd F Y, H:i:s', null, wp_timezone() );

		return MYP_Telegram_API::instance()->send( implode( "\n", $lines ) );
	}

	/**
	 * Handle new user registration.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function user_registered( $user_id ) {
		self::$new_user_ids[ (int) $user_id ] = true;

		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'register' ) ) {
			return;
		}

		$this->send_user_alert( $this->is_khmer_locale() ? 'Created new account' : 'Created new account', $user_id );
	}

	/**
	 * Handle profile updates without exposing values.
	 *
	 * @param int     $user_id       User ID.
	 * @param WP_User $old_user_data Old user data.
	 * @return void
	 */
	public function user_profile_updated( $user_id, $old_user_data ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'profile_update' ) ) {
			return;
		}

		$user = get_userdata( (int) $user_id );

		if ( ! $user || ! $old_user_data instanceof WP_User ) {
			return;
		}

		$fields = array(
			'display_name' => $this->is_khmer_locale() ? 'Display name' : 'Display name',
			'user_nicename' => $this->is_khmer_locale() ? 'URL name' : 'URL name',
			'user_email' => $this->is_khmer_locale() ? 'Email address' : 'Email address',
			'user_url' => $this->is_khmer_locale() ? 'Website' : 'Website',
			'first_name' => $this->is_khmer_locale() ? 'First name' : 'First name',
			'last_name' => $this->is_khmer_locale() ? 'Last name' : 'Last name',
			'nickname' => $this->is_khmer_locale() ? 'Nickname' : 'Nickname',
			'description' => $this->is_khmer_locale() ? 'Biographical info' : 'Biographical info',
		);
		$changed = array();

		foreach ( $fields as $field => $label ) {
			if ( (string) $user->{$field} !== (string) $old_user_data->{$field} ) {
				$changed[] = $label;
			}
		}

		if ( ! $changed ) {
			return;
		}

		$this->send_user_alert(
			$this->is_khmer_locale() ? 'Updated account info' : 'Updated account info',
			$user,
			( $this->is_khmer_locale() ? 'Changed: ' : 'Changed: ' ) . implode( ', ', $changed )
		);
	}

	/**
	 * Queue a role change.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function queue_role_change( $user_id ) {
		self::$role_change_ids[ (int) $user_id ] = true;
	}

	/**
	 * Flush consolidated role-change alerts.
	 *
	 * @return void
	 */
	public function flush_role_changes() {
		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'role_change' ) ) {
			self::$role_change_ids = array();

			return;
		}

		foreach ( array_keys( self::$role_change_ids ) as $user_id ) {
			if ( isset( self::$new_user_ids[ $user_id ] ) ) {
				continue;
			}

			$this->send_user_alert( $this->is_khmer_locale() ? 'Changed role' : 'Changed role', $user_id );
		}

		self::$role_change_ids = array();
	}

	/**
	 * Handle successful login.
	 *
	 * @param string  $user_login Login.
	 * @param WP_User $user       User.
	 * @return void
	 */
	public function user_logged_in( $user_login, $user ) {
		unset( $user_login );

		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'login' ) || ! $user instanceof WP_User ) {
			return;
		}

		$this->send_user_alert( $this->is_khmer_locale() ? 'Logged in' : 'Logged in', $user, '', $user );
	}

	/**
	 * Handle failed login.
	 *
	 * @param string $username Attempted username.
	 * @return void
	 */
	public function user_login_failed( $username ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'failed_login' ) ) {
			return;
		}

		$username = $this->safe_text( $username, 100 );
		$khmer    = $this->is_khmer_locale();
		$lines    = array(
			'⚠️ ' . ( $khmer ? 'Failed Login Alert' : 'Failed Login Alert' ),
			'',
			( $khmer ? 'ឈ្មោះដែលបានប៉ុនប៉ង: ' : 'Attempted username: ' ) . $username,
			( $khmer ? 'ពេលវេលា: ' : 'Time: ' ) . wp_date( 'd F Y, H:i:s', null, wp_timezone() ),
		);

		MYP_Telegram_API::instance()->send( implode( "\n", $lines ) );
	}

	/**
	 * Handle logout.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function user_logged_out( $user_id ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'logout' ) ) {
			return;
		}

		$user = get_userdata( (int) $user_id );

		if ( $user ) {
			$this->send_user_alert( $this->is_khmer_locale() ? 'Logged out' : 'Logged out', $user, '', $user );
		}
	}

	/**
	 * Handle password reset.
	 *
	 * @param WP_User $user User.
	 * @return void
	 */
	public function password_reset( $user ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'password_reset' ) || ! $user instanceof WP_User ) {
			return;
		}

		$this->send_user_alert( $this->is_khmer_locale() ? 'Reset password' : 'Reset password', $user, '', $user );
	}

	/**
	 * Handle user deletion.
	 *
	 * @param int          $user_id  User ID.
	 * @param int|null     $reassign Reassigned ID.
	 * @param WP_User|null $user     User.
	 * @return void
	 */
	public function user_deleted( $user_id, $reassign, $user ) {
		unset( $user_id, $reassign );

		if ( ! myp_telegram_settings()->is_event_enabled( 'users', 'delete' ) || ! $user instanceof WP_User ) {
			return;
		}

		$this->send_user_alert( $this->is_khmer_locale() ? 'Deleted account' : 'Deleted account', $user );
	}

	/**
	 * Handle plugin activation.
	 *
	 * @param string $plugin       Plugin basename.
	 * @param bool   $network_wide Network wide.
	 * @return void
	 */
	public function plugin_activated( $plugin, $network_wide ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'system', 'plugin_activate' ) ) {
			return;
		}

		$details = $this->plugin_details( $plugin );
		$this->send_system_alert(
			$this->is_khmer_locale() ? 'Activated' : 'Activated',
			$this->is_khmer_locale() ? 'Plugin' : 'Plugin',
			$details['name'] . ( $network_wide ? ' (Network)' : '' ),
			$details['version']
		);
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @param string $plugin       Plugin basename.
	 * @param bool   $network_wide Network wide.
	 * @return void
	 */
	public function plugin_deactivated( $plugin, $network_wide ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'system', 'plugin_deactivate' ) ) {
			return;
		}

		$details = $this->plugin_details( $plugin );
		$this->send_system_alert(
			$this->is_khmer_locale() ? 'Deactivated' : 'Deactivated',
			$this->is_khmer_locale() ? 'Plugin' : 'Plugin',
			$details['name'] . ( $network_wide ? ' (Network)' : '' ),
			$details['version']
		);
	}

	/**
	 * Handle plugin deletion.
	 *
	 * @param string $plugin_file Plugin basename.
	 * @param bool   $deleted     Deleted.
	 * @return void
	 */
	public function plugin_deleted( $plugin_file, $deleted ) {
		if ( ! $deleted || ! myp_telegram_settings()->is_event_enabled( 'system', 'plugin_delete' ) ) {
			return;
		}

		$name = $this->safe_text( basename( (string) $plugin_file, '.php' ), 200 );
		$this->send_system_alert(
			$this->is_khmer_locale() ? 'Deleted' : 'Deleted',
			$this->is_khmer_locale() ? 'Plugin' : 'Plugin',
			$name
		);
	}

	/**
	 * Handle theme switch.
	 *
	 * @param string   $new_name  New name.
	 * @param WP_Theme $new_theme New theme.
	 * @param WP_Theme $old_theme Old theme.
	 * @return void
	 */
	public function theme_switched( $new_name, $new_theme, $old_theme ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'system', 'theme_switch' ) ) {
			return;
		}

		$old_name = $old_theme instanceof WP_Theme ? $old_theme->get( 'Name' ) : '';
		$new_name = $new_theme instanceof WP_Theme ? $new_theme->get( 'Name' ) : $new_name;
		$item     = ( '' !== $old_name ? $old_name . ' → ' : '' ) . $new_name;
		$version  = $new_theme instanceof WP_Theme ? $new_theme->get( 'Version' ) : '';

		$this->send_system_alert(
			$this->is_khmer_locale() ? 'Switched theme' : 'Switched theme',
			$this->is_khmer_locale() ? 'Theme' : 'Theme',
			$item,
			$version
		);
	}

	/**
	 * Handle theme deletion.
	 *
	 * @param string $stylesheet Stylesheet.
	 * @param bool   $deleted    Deleted.
	 * @return void
	 */
	public function theme_deleted( $stylesheet, $deleted ) {
		if ( ! $deleted || ! myp_telegram_settings()->is_event_enabled( 'system', 'theme_delete' ) ) {
			return;
		}

		$this->send_system_alert(
			$this->is_khmer_locale() ? 'Deleted' : 'Deleted',
			$this->is_khmer_locale() ? 'Theme' : 'Theme',
			$stylesheet
		);
	}

	/**
	 * Return validated plugin details.
	 *
	 * @param string $plugin_file Plugin basename.
	 * @return array{name: string, version: string, key: string}
	 */
	private function plugin_details( $plugin_file ) {
		$plugin_file = wp_normalize_path( (string) $plugin_file );
		$plugin_file = ltrim( $plugin_file, '/' );
		$fallback    = basename( $plugin_file, '.php' );
		$details     = array(
			'name'    => $this->safe_text( $fallback, 200 ),
			'version' => '',
			'key'     => $plugin_file,
		);

		if ( '' === $plugin_file || 0 !== validate_file( $plugin_file ) ) {
			return $details;
		}

		$path = WP_PLUGIN_DIR . '/' . $plugin_file;

		if ( ! function_exists( 'get_plugin_data' ) || ! is_readable( $path ) ) {
			return $details;
		}

		$plugin_data = get_plugin_data( $path, false, false );

		if ( ! empty( $plugin_data['Name'] ) ) {
			$details['name'] = $this->safe_text( $plugin_data['Name'], 200 );
		}

		if ( ! empty( $plugin_data['Version'] ) ) {
			$details['version'] = $this->safe_text( $plugin_data['Version'], 50 );
		}

		return $details;
	}

	/**
	 * Return validated theme details.
	 *
	 * @param string $stylesheet Stylesheet.
	 * @return array{name: string, version: string, key: string}
	 */
	private function theme_details( $stylesheet ) {
		$stylesheet = sanitize_key( (string) $stylesheet );
		$theme      = wp_get_theme( $stylesheet );
		$name       = $stylesheet;
		$version    = '';

		if ( $theme->exists() ) {
			$name    = $theme->get( 'Name' ) ?: $stylesheet;
			$version = $theme->get( 'Version' );
		}

		return array(
			'name'    => $this->safe_text( $name, 200 ),
			'version' => $this->safe_text( $version, 50 ),
			'key'     => $stylesheet,
		);
	}

	/**
	 * Extract upgrader targets.
	 *
	 * @param string           $type       Type.
	 * @param array            $hook_extra Hook extra.
	 * @param WP_Upgrader|null $upgrader   Upgrader.
	 * @return array<int, string>
	 */
	private function upgrade_targets( $type, $hook_extra, $upgrader = null ) {
		$targets = array();

		if ( 'plugin' === $type ) {
			if ( ! empty( $hook_extra['plugin'] ) && is_string( $hook_extra['plugin'] ) ) {
				$targets[] = $hook_extra['plugin'];
			}

			if ( ! empty( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
				$targets = array_merge( $targets, $hook_extra['plugins'] );
			}

			if ( ! $targets && $upgrader && method_exists( $upgrader, 'plugin_info' ) ) {
				$plugin = $upgrader->plugin_info();

				if ( is_string( $plugin ) && '' !== $plugin ) {
					$targets[] = $plugin;
				}
			}
		}

		if ( 'theme' === $type ) {
			if ( ! empty( $hook_extra['theme'] ) && is_string( $hook_extra['theme'] ) ) {
				$targets[] = $hook_extra['theme'];
			}

			if ( ! empty( $hook_extra['themes'] ) && is_array( $hook_extra['themes'] ) ) {
				$targets = array_merge( $targets, $hook_extra['themes'] );
			}

			if ( ! $targets && $upgrader && method_exists( $upgrader, 'theme_info' ) ) {
				$theme = $upgrader->theme_info();

				if ( $theme instanceof WP_Theme ) {
					$targets[] = $theme->get_stylesheet();
				}
			}
		}

		return array_values( array_unique( array_filter( $targets, 'is_string' ) ) );
	}

	/**
	 * Capture old versions before upgrade.
	 *
	 * @param mixed $response   Response.
	 * @param array $hook_extra Hook extra.
	 * @return mixed
	 */
	public function capture_versions_before_upgrade( $response, $hook_extra ) {
		if ( ! is_array( $hook_extra ) ) {
			return $response;
		}

		$type = isset( $hook_extra['type'] ) ? sanitize_key( $hook_extra['type'] ) : '';

		if ( 'core' === $type ) {
			global $wp_version;

			set_site_transient( 'myp_tg_core_old_version', $this->safe_text( $wp_version, 50 ), 2 * HOUR_IN_SECONDS );

			return $response;
		}

		if ( ! in_array( $type, array( 'plugin', 'theme' ), true ) ) {
			return $response;
		}

		foreach ( $this->upgrade_targets( $type, $hook_extra ) as $target ) {
			$details = 'plugin' === $type ? $this->plugin_details( $target ) : $this->theme_details( $target );

			if ( '' !== $details['key'] && '' !== $details['version'] ) {
				$transient = 'myp_tg_' . $type . '_' . hash( 'sha256', $details['key'] );
				set_site_transient( $transient, $details['version'], 2 * HOUR_IN_SECONDS );
			}
		}

		return $response;
	}

	/**
	 * Handle completed upgrades.
	 *
	 * @param WP_Upgrader $upgrader   Upgrader.
	 * @param array       $hook_extra Hook extra.
	 * @return void
	 */
	public function upgrade_completed( $upgrader, $hook_extra ) {
		if ( ! is_array( $hook_extra ) ) {
			return;
		}

		$type   = isset( $hook_extra['type'] ) ? sanitize_key( $hook_extra['type'] ) : '';
		$action = isset( $hook_extra['action'] ) ? sanitize_key( $hook_extra['action'] ) : '';

		if ( 'core' === $type || ! in_array( $action, array( 'install', 'update' ), true ) ) {
			return;
		}

		$khmer = $this->is_khmer_locale();
		$action_labels = array(
			'install' => $khmer ? 'Installed' : 'Installed',
			'update'  => $khmer ? 'Updated' : 'Updated',
		);
		$component_labels = array(
			'plugin'      => $khmer ? 'Plugin' : 'Plugin',
			'theme'       => $khmer ? 'Theme' : 'Theme',
			'translation' => $khmer ? 'Language pack' : 'Language pack',
		);

		if ( ! isset( $component_labels[ $type ] ) ) {
			return;
		}

		if ( 'plugin' === $type && ! myp_telegram_settings()->is_event_enabled( 'system', 'plugin_update' ) ) {
			return;
		}

		if ( 'theme' === $type && ! myp_telegram_settings()->is_event_enabled( 'system', 'theme_update' ) ) {
			return;
		}

		if ( 'translation' === $type && ! myp_telegram_settings()->is_event_enabled( 'system', 'translation_update' ) ) {
			return;
		}

		$names    = array();
		$versions = array();

		if ( in_array( $type, array( 'plugin', 'theme' ), true ) ) {
			foreach ( $this->upgrade_targets( $type, $hook_extra, $upgrader ) as $target ) {
				$details = 'plugin' === $type ? $this->plugin_details( $target ) : $this->theme_details( $target );
				$names[] = $details['name'];
				$transient = 'myp_tg_' . $type . '_' . hash( 'sha256', $details['key'] );
				$old_version = get_site_transient( $transient );
				delete_site_transient( $transient );

				if ( $old_version && $details['version'] ) {
					$versions[] = $details['name'] . ': ' . $old_version . ' → ' . $details['version'];
				} elseif ( $details['version'] ) {
					$versions[] = $details['name'] . ': ' . $details['version'];
				}
			}
		}

		if ( 'translation' === $type && ! empty( $hook_extra['translations'] ) && is_array( $hook_extra['translations'] ) ) {
			foreach ( array_slice( $hook_extra['translations'], 0, 20 ) as $translation ) {
				if ( ! is_array( $translation ) ) {
					continue;
				}

				$slug     = isset( $translation['slug'] ) ? $translation['slug'] : 'WordPress';
				$language = isset( $translation['language'] ) ? $translation['language'] : '';
				$names[]  = $slug . ( '' !== $language ? ' (' . $language . ')' : '' );

				if ( ! empty( $translation['version'] ) ) {
					$versions[] = $slug . ': ' . $translation['version'];
				}
			}
		}

		$this->send_system_alert(
			$action_labels[ $action ],
			$component_labels[ $type ],
			$names ? implode( ', ', $names ) : $component_labels[ $type ],
			implode( ' | ', $versions )
		);
	}

	/**
	 * Handle core update success.
	 *
	 * @param string $new_version New WordPress version.
	 * @return void
	 */
	public function core_updated( $new_version ) {
		if ( ! myp_telegram_settings()->is_event_enabled( 'system', 'core_update' ) ) {
			return;
		}

		$old_version = get_site_transient( 'myp_tg_core_old_version' );
		delete_site_transient( 'myp_tg_core_old_version' );
		$version = $old_version ? $old_version . ' → ' . $new_version : (string) $new_version;

		$this->send_system_alert(
			$this->is_khmer_locale() ? 'Updated' : 'Updated',
			'WordPress Core',
			'WordPress',
			$version
		);
	}

	/**
	 * Send a summary of available core, plugin, and theme updates.
	 *
	 * @return void
	 */
	public function send_available_updates_summary() {
		if ( ! myp_telegram_settings()->is_group_enabled( 'available_updates' ) ) {
			return;
		}

		if ( ! function_exists( 'get_plugin_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}

		$plugin_updates = (array) get_plugin_updates();
		$theme_updates  = (array) get_theme_updates();
		$core_updates   = (array) get_core_updates();
		$has_core       = false;

		foreach ( $core_updates as $core ) {
			if ( ! empty( $core->current ) && isset( $core->response ) && 'upgrade' === $core->response ) {
				$has_core = true;
				break;
			}
		}

		$lines = array(
			'📦 ' . ( $this->is_khmer_locale() ? 'Available Updates' : 'Available Updates' ),
			'',
		);

		$khmer = $this->is_khmer_locale();
		$lines[] = ( $khmer ? 'គេហទំព័រ: ' : 'Site: ' ) . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$lines[] = ( $khmer ? 'WordPress Core: ' : 'WordPress Core: ' ) . ( $has_core ? ( $khmer ? 'មានការធ្វើបច្ចុប្បន្នភាព' : 'update available' ) : ( $khmer ? 'ទាន់សម័យ' : 'up to date' ) );
		$lines[] = ( $khmer ? 'កម្មវិធីបន្ថែម: ' : 'Plugins: ' ) . count( $plugin_updates );
		$lines[] = ( $khmer ? 'រូបរាង: ' : 'Themes: ' ) . count( $theme_updates );

		if ( $plugin_updates ) {
			$lines[] = '';
			$lines[] = $khmer ? 'Plugins:' : 'Plugins:';
			$items   = array();
			foreach ( array_slice( $plugin_updates, 0, 10 ) as $plugin_file => $update ) {
				$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
				$items[] = ( ! empty( $data['Name'] ) ? $data['Name'] : $plugin_file ) . ( ! empty( $update->update->new_version ) ? ' (' . $update->update->new_version . ')' : '' );
			}
			$lines[] = implode( ', ', $items );
		}

		if ( $theme_updates ) {
			$lines[] = '';
			$lines[] = $khmer ? 'Themes:' : 'Themes:';
			$items   = array();
			foreach ( array_slice( $theme_updates, 0, 10 ) as $stylesheet => $update ) {
				$theme = wp_get_theme( $stylesheet );
				$items[] = ( $theme->exists() ? $theme->get( 'Name' ) : $stylesheet ) . ( ! empty( $update->update['new_version'] ) ? ' (' . $update->update['new_version'] . ')' : '' );
			}
			$lines[] = implode( ', ', $items );
		}

		MYP_Telegram_API::instance()->send( implode( "\n", $lines ) );
	}
}
