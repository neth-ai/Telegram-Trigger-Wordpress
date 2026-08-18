<?php
/**
 * News post-type module.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_News_Module
 */
class MYP_Telegram_News_Module {

	/**
	 * Register the module.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'myp_telegram_content_type_labels', array( __CLASS__, 'labels' ) );
	}

	/**
	 * Ensure News has a friendly localized label.
	 *
	 * @param array<string, array<string, string>> $labels Existing labels.
	 * @return array<string, array<string, string>>
	 */
	public static function labels( $labels ) {
		$labels['news'] = array(
			'en' => 'News',
			'km' => 'ព័ត៌មាន',
		);

		return $labels;
	}
}
