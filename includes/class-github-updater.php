<?php
/**
 * GitHub release updater.
 *
 * Keeps WordPress update checks and the plugin details modal pointed at this
 * project's GitHub releases instead of the public WordPress.org directory.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_GitHub_Updater
 */
class MYP_Telegram_GitHub_Updater {

	/**
	 * GitHub repository owner/name.
	 *
	 * @var string
	 */
	const REPOSITORY = 'neth-ai/Telegram-Trigger-Wordpress';

	/**
	 * Repository homepage.
	 *
	 * @var string
	 */
	const HOMEPAGE = 'https://github.com/neth-ai/Telegram-Trigger-Wordpress';

	/**
	 * Update URI from the main plugin header.
	 *
	 * @var string
	 */
	const UPDATE_URI = 'https://github.com/neth-ai/Telegram-Trigger-Wordpress';

	/**
	 * Unique plugin slug used only for this project's update objects.
	 *
	 * @var string
	 */
	const SLUG = 'telegram-trigger-wordpress';

	/**
	 * Minimum supported WordPress version.
	 *
	 * @var string
	 */
	const REQUIRES_WP = '6.9';

	/**
	 * Latest verified WordPress version.
	 *
	 * @var string
	 */
	const TESTED_WP = '6.9';

	/**
	 * Minimum supported PHP version.
	 *
	 * @var string
	 */
	const REQUIRES_PHP = '8.2';

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_GitHub_Updater|null
	 */
	private static $instance = null;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $basename = '';

	/**
	 * Cached plugin header data.
	 *
	 * @var array<string, string>|null
	 */
	private $plugin_data = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_GitHub_Updater
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register update hooks.
	 */
	private function __construct() {
		$this->basename = plugin_basename( MYP_TELEGRAM_FILE );

		add_filter( 'update_plugins_github.com', array( $this, 'github_update' ), 10, 4 );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'site_transient_update_plugins', array( $this, 'remove_foreign_update_data' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_information' ), 10, 3 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Supply update data through WordPress's Update URI hostname hook.
	 *
	 * @param array|false $update      Existing update data.
	 * @param array       $plugin_data Plugin header data.
	 * @param string      $plugin_file Plugin basename.
	 * @param array       $locales     Installed locales.
	 * @return array|false
	 */
	public function github_update( $update, $plugin_data, $plugin_file, $locales ) {
		unset( $locales );

		$update_uri = isset( $plugin_data['UpdateURI'] ) ? (string) $plugin_data['UpdateURI'] : '';

		if ( $this->basename !== $plugin_file || self::UPDATE_URI !== untrailingslashit( $update_uri ) ) {
			return $update;
		}

		$release   = $this->get_latest_release();
		$installed = ! empty( $plugin_data['Version'] ) ? (string) $plugin_data['Version'] : $this->get_plugin_version();

		if ( ! $release ) {
			return $this->build_no_update_data( $installed );
		}

		$new_version = $this->normalize_version( $release['tag_name'] );

		if ( '' === $new_version || empty( $release['download_url'] ) ) {
			return $this->build_no_update_data( $installed );
		}

		return $this->build_update_data( $release, $new_version );
	}

	/**
	 * Inject GitHub release data into the plugin update transient.
	 *
	 * @param object $transient Update transient.
	 * @return object
	 */
	public function check_for_updates( $transient ) {
		if (
			! is_object( $transient ) ||
			empty( $transient->checked ) ||
			! is_array( $transient->checked ) ||
			! array_key_exists( $this->basename, $transient->checked )
		) {
			return $transient;
		}

		if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
			$transient->response = array();
		}

		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		// Always discard any WordPress.org result previously attached to our basename.
		unset( $transient->response[ $this->basename ], $transient->no_update[ $this->basename ] );

		$release   = $this->get_latest_release();
		$installed = $this->get_plugin_version();

		if ( ! $installed ) {
			return $transient;
		}

		if ( ! $release ) {
			$transient->no_update[ $this->basename ] = (object) $this->build_no_update_data( $installed );

			return $transient;
		}

		$new_version = $this->normalize_version( $release['tag_name'] );

		if ( '' !== $new_version && ! empty( $release['download_url'] ) && version_compare( $new_version, $installed, '>' ) ) {
			$transient->response[ $this->basename ] = (object) $this->build_update_data( $release, $new_version );
		} else {
			$known_version = '' !== $new_version && ! empty( $release['download_url'] ) ? $new_version : $installed;
			$transient->no_update[ $this->basename ] = (object) $this->build_no_update_data( $known_version );
		}

		return $transient;
	}

	/**
	 * Remove stale update data that did not originate from this updater.
	 *
	 * This protects existing installations immediately, before the next full
	 * update check replaces a cached WordPress.org collision.
	 *
	 * @param mixed $transient Stored update transient.
	 * @return mixed
	 */
	public function remove_foreign_update_data( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		foreach ( array( 'response', 'no_update' ) as $bucket ) {
			if (
				isset( $transient->{$bucket} ) &&
				is_array( $transient->{$bucket} ) &&
				isset( $transient->{$bucket}[ $this->basename ] ) &&
				! $this->is_our_update_data( $transient->{$bucket}[ $this->basename ] )
			) {
				unset( $transient->{$bucket}[ $this->basename ] );
			}
		}

		return $transient;
	}

	/**
	 * Return plugin details for the "View details" modal.
	 *
	 * @param mixed  $result Existing result.
	 * @param string $action Requested action.
	 * @param object $args   API arguments.
	 * @return mixed
	 */
	public function plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		$slug = isset( $args->slug ) ? (string) $args->slug : '';

		if ( ! $this->is_our_slug( $slug ) ) {
			return $result;
		}

		$release = $this->get_latest_release();
		$version = $release ? $this->normalize_version( $release['tag_name'] ) : $this->get_plugin_version();

		$sections = array(
			'description' => __( 'Sends configurable Telegram notifications for WordPress content, comments, users, system/plugin updates, and popular third-party integrations.', 'telegram-bot' ),
			'changelog'   => ! empty( $release['body'] ) ? wp_kses_post( $release['body'] ) : __( 'See the GitHub release notes for the latest changes.', 'telegram-bot' ),
		);

		return (object) array(
			'name'            => 'Telegram Bot Trigger Notifications',
			'slug'            => self::SLUG,
			'version'         => $version,
			'author'          => 'Neth',
			'author_profile'  => 'https://github.com/neth-ai',
			'homepage'        => self::HOMEPAGE,
			'download_link'   => $release && ! empty( $release['download_url'] ) ? $release['download_url'] : '',
			'sections'        => $sections,
			'requires'        => self::REQUIRES_WP,
			'tested'          => self::TESTED_WP,
			'requires_php'    => self::REQUIRES_PHP,
			'last_updated'    => ! empty( $release['published_at'] ) ? $release['published_at'] : '',
			'icons'           => array(),
			'banners'         => array(),
			'external'        => true,
		);
	}

	/**
	 * Add a GitHub link to the plugin row.
	 *
	 * @param array  $links Row links.
	 * @param string $file  Plugin basename.
	 * @return array
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $this->basename !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener">%2$s</a>',
			esc_url( self::HOMEPAGE ),
			esc_html__( 'View on GitHub', 'telegram-bot' )
		);

		return $links;
	}

	/**
	 * Build the update response object.
	 *
	 * @param array<string, string> $release     Release data.
	 * @param string                $new_version New version.
	 * @return array<string, mixed>
	 */
	private function build_update_data( $release, $new_version ) {
		$plugin_data = $this->get_plugin_data();

		return array(
			'id'           => self::UPDATE_URI,
			'slug'         => self::SLUG,
			'plugin'       => $this->basename,
			'version'      => $new_version,
			'new_version'  => $new_version,
			'url'          => self::HOMEPAGE,
			'package'      => $release['download_url'],
			'tested'       => self::TESTED_WP,
			'requires'     => ! empty( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : self::REQUIRES_WP,
			'requires_php' => ! empty( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : self::REQUIRES_PHP,
			'icons'        => array(),
			'banners'      => array(),
		);
	}

	/**
	 * Build the no-update response object.
	 *
	 * @param string $version Current release version.
	 * @return array<string, mixed>
	 */
	private function build_no_update_data( $version ) {
		$plugin_data = $this->get_plugin_data();

		return array(
			'id'           => self::UPDATE_URI,
			'slug'         => self::SLUG,
			'plugin'       => $this->basename,
			'version'      => $version,
			'new_version'  => $version,
			'url'          => self::HOMEPAGE,
			'package'      => '',
			'tested'       => self::TESTED_WP,
			'requires'     => ! empty( $plugin_data['RequiresWP'] ) ? $plugin_data['RequiresWP'] : self::REQUIRES_WP,
			'requires_php' => ! empty( $plugin_data['RequiresPHP'] ) ? $plugin_data['RequiresPHP'] : self::REQUIRES_PHP,
			'icons'        => array(),
			'banners'      => array(),
		);
	}

	/**
	 * Check whether the requested slug belongs to this plugin.
	 *
	 * @param string $slug Requested slug.
	 * @return bool
	 */
	private function is_our_slug( $slug ) {
		return self::SLUG === $slug;
	}

	/**
	 * Check whether an update item has this project's identity.
	 *
	 * @param array|object $update Update item.
	 * @return bool
	 */
	private function is_our_update_data( $update ) {
		$update = (array) $update;

		return isset( $update['slug'], $update['plugin'], $update['id'] ) &&
			self::SLUG === $update['slug'] &&
			$this->basename === $update['plugin'] &&
			self::UPDATE_URI === untrailingslashit( (string) $update['id'] );
	}

	/**
	 * Fetch the latest GitHub release with a 6 hour cache.
	 *
	 * @return array<string, string>|null
	 */
	private function get_latest_release() {
		$cached = get_transient( 'myp_telegram_github_release' );

		if ( is_array( $cached ) ) {
			if ( ! empty( $cached['error'] ) ) {
				return null;
			}

			if ( isset( $cached['tag_name'], $cached['download_url'] ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'               => 'application/vnd.github+json',
					'X-GitHub-Api-Version' => '2022-11-28',
					'User-Agent'           => 'Telegram-Trigger-Wordpress',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( 'myp_telegram_github_release', array( 'error' => true ), 10 * MINUTE_IN_SECONDS );

			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || empty( $body['tag_name'] ) ) {
			set_transient( 'myp_telegram_github_release', array( 'error' => true ), 10 * MINUTE_IN_SECONDS );

			return null;
		}

		$tag          = sanitize_text_field( (string) $body['tag_name'] );
		$version      = $this->normalize_version( $tag );
		$download_url = '';

		if ( '' === $version ) {
			set_transient( 'myp_telegram_github_release', array( 'error' => true ), 10 * MINUTE_IN_SECONDS );

			return null;
		}

		$valid_asset_names = array(
			'telegram-bot-v' . strtolower( $version ) . '.zip',
			'telegram-bot-' . strtolower( $version ) . '.zip',
		);

		if ( ! empty( $body['assets'] ) && is_array( $body['assets'] ) ) {
			foreach ( $body['assets'] as $asset ) {
				if ( empty( $asset['browser_download_url'] ) || empty( $asset['name'] ) ) {
					continue;
				}

				$asset_name = strtolower( (string) $asset['name'] );

				if ( in_array( $asset_name, $valid_asset_names, true ) ) {
					$download_url = esc_url_raw( (string) $asset['browser_download_url'] );
					break;
				}
			}
		}

		$release = array(
			'tag_name'      => $tag,
			'download_url'  => $download_url,
			'release_url'   => ! empty( $body['html_url'] ) ? esc_url_raw( (string) $body['html_url'] ) : self::HOMEPAGE . '/releases',
			'body'          => ! empty( $body['body'] ) ? (string) $body['body'] : '',
			'published_at'  => ! empty( $body['published_at'] ) ? (string) $body['published_at'] : '',
		);

		set_transient( 'myp_telegram_github_release', $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Normalize a version from a Git tag.
	 *
	 * @param string $version Raw version.
	 * @return string
	 */
	private function normalize_version( $version ) {
		$version = preg_replace( '/^[vV]/', '', trim( sanitize_text_field( (string) $version ) ) );

		if ( ! is_string( $version ) || ! preg_match( '/^\d+(?:\.\d+)*(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
			return '';
		}

		return $version;
	}

	/**
	 * Return the installed plugin version.
	 *
	 * @return string
	 */
	private function get_plugin_version() {
		$data = $this->get_plugin_data();

		return isset( $data['Version'] ) ? $data['Version'] : '';
	}

	/**
	 * Read and cache plugin header data.
	 *
	 * @return array<string, string>
	 */
	private function get_plugin_data() {
		if ( null !== $this->plugin_data ) {
			return $this->plugin_data;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->plugin_data = get_plugin_data( MYP_TELEGRAM_FILE, false, false );

		return $this->plugin_data;
	}
}
