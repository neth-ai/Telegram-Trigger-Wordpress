<?php
/**
 * Optional integrations with popular WordPress plugins.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MYP_Telegram_Integrations
 */
class MYP_Telegram_Integrations {

	/**
	 * Singleton instance.
	 *
	 * @var MYP_Telegram_Integrations|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return MYP_Telegram_Integrations
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register integration hooks.
	 */
	private function __construct() {
		$available = self::get_availability();

		if ( ! empty( $available['woocommerce_order'] ) || ! empty( $available['woocommerce_stock'] ) ) {
			add_action( 'woocommerce_new_order', array( $this, 'woocommerce_new_order' ), 20, 1 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'woocommerce_order_status_changed' ), 20, 3 );
			add_action( 'woocommerce_low_stock', array( $this, 'woocommerce_low_stock' ), 20, 1 );
			add_action( 'woocommerce_no_stock', array( $this, 'woocommerce_no_stock' ), 20, 1 );
		}

		if ( ! empty( $available['contact_form_7'] ) ) {
			add_action( 'wpcf7_mail_sent', array( $this, 'contact_form_7_sent' ), 20, 1 );
		}

		if ( ! empty( $available['wpforms'] ) ) {
			add_action( 'wpforms_process_complete', array( $this, 'wpforms_submitted' ), 20, 4 );
		}

		if ( ! empty( $available['fluentforms'] ) ) {
			add_action( 'fluentform_submission_inserted', array( $this, 'fluentform_submitted' ), 20, 2 );
		}

		if ( ! empty( $available['ninja_forms'] ) ) {
			add_action( 'ninja_forms_after_submission', array( $this, 'ninja_forms_submitted' ), 20, 1 );
		}

		if ( ! empty( $available['elementor_forms'] ) ) {
			add_action( 'elementor_pro/forms/new_record', array( $this, 'elementor_forms_submitted' ), 20, 2 );
		}

		if ( ! empty( $available['gravity_forms'] ) ) {
			add_action( 'gform_after_submission', array( $this, 'gravity_forms_submitted' ), 20, 2 );
		}
	}

	/**
	 * Detect supported integrations without calling third-party APIs.
	 *
	 * @return array<string, bool>
	 */
	public static function get_availability() {
		$woocommerce = function_exists( 'WC' ) || class_exists( 'WooCommerce' ) || defined( 'WC_VERSION' );
		$availability = array(
			'woocommerce_order' => $woocommerce,
			'woocommerce_stock' => $woocommerce,
			'contact_form_7'    => class_exists( 'WPCF7' ) || defined( 'WPCF7_VERSION' ),
			'wpforms'           => function_exists( 'wpforms' ) || defined( 'WPFORMS_VERSION' ),
			'fluentforms'       => function_exists( 'wpFluentForm' ) || defined( 'FLUENTFORM_VERSION' ),
			'ninja_forms'       => function_exists( 'Ninja_Forms' ) || class_exists( 'Ninja_Forms' ),
			'elementor_forms'   => class_exists( 'ElementorPro\\Plugin' ) || defined( 'ELEMENTOR_PRO_VERSION' ),
			'gravity_forms'     => class_exists( 'GFForms' ) || defined( 'GF_VERSION' ),
		);

		/**
		 * Filter detected optional-integration availability.
		 *
		 * @param array<string, bool> $availability Availability keyed by integration event.
		 */
		$availability = apply_filters( 'myp_telegram_integration_availability', $availability );

		return is_array( $availability ) ? array_map( 'boolval', $availability ) : array();
	}

	/**
	 * Check whether an integration event is enabled.
	 *
	 * @param string $event Event key.
	 * @return bool
	 */
	private function integration_enabled( $event ) {
		return myp_telegram_settings()->is_event_enabled( 'integrations', $event );
	}

	/**
	 * Send an integration alert.
	 *
	 * @param string $header Header line.
	 * @param array  $lines  Body lines.
	 * @return bool
	 */
	private function send_integration_alert( $header, $lines ) {
		$details = array_filter( $lines, 'is_string' );
		$message = MYP_Telegram_Template_Manager::instance()->format_message(
			'integration',
			array(
				'integration_title' => $this->safe_value( $header ),
				'details'           => implode( "\n", $details ),
				'time'              => myp_telegram_format_datetime(),
			)
		);

		return MYP_Telegram_API::instance()->send( $message );
	}

	/**
	 * Convert an integration value to safe plain text.
	 *
	 * @param mixed $value Third-party value.
	 * @return string
	 */
	private function safe_value( $value ) {
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	/**
	 * New WooCommerce order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function woocommerce_new_order( $order_id ) {
		if ( ! $this->integration_enabled( 'woocommerce_order' ) || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$lines = array(
			'Order: #' . $this->safe_value( $order->get_order_number() ),
			'Status: ' . $this->safe_value( $order->get_status() ),
			'Total: ' . $this->safe_value( $order->get_total() ) . ' ' . $this->safe_value( $order->get_currency() ),
			'Customer: ' . trim( $this->safe_value( $order->get_billing_first_name() ) . ' ' . $this->safe_value( $order->get_billing_last_name() ) ),
		);

		$this->send_integration_alert( '🛒 New WooCommerce Order', $lines );
	}

	/**
	 * WooCommerce order status change.
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @return void
	 */
	public function woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {
		if ( ! $this->integration_enabled( 'woocommerce_order' ) || ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->send_integration_alert(
			'🛒 WooCommerce Order Update',
			array(
				'Order: #' . $this->safe_value( $order->get_order_number() ),
				'Status: ' . $this->safe_value( $old_status ) . ' → ' . $this->safe_value( $new_status ),
				'Total: ' . $this->safe_value( $order->get_total() ) . ' ' . $this->safe_value( $order->get_currency() ),
			)
		);
	}

	/**
	 * WooCommerce low stock.
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public function woocommerce_low_stock( $product ) {
		if ( ! $this->integration_enabled( 'woocommerce_stock' ) || ! is_object( $product ) ) {
			return;
		}

		$name  = method_exists( $product, 'get_name' ) ? $this->safe_value( $product->get_name() ) : '';
		$stock = method_exists( $product, 'get_stock_quantity' ) ? $this->safe_value( $product->get_stock_quantity() ) : '';

		$this->send_integration_alert(
			'📉 WooCommerce Low Stock',
			array(
				'Product: ' . $name,
				'Remaining: ' . $stock,
			)
		);
	}

	/**
	 * WooCommerce out of stock.
	 *
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public function woocommerce_no_stock( $product ) {
		if ( ! $this->integration_enabled( 'woocommerce_stock' ) || ! is_object( $product ) ) {
			return;
		}

		$name = method_exists( $product, 'get_name' ) ? $this->safe_value( $product->get_name() ) : '';

		$this->send_integration_alert(
			'🚫 WooCommerce Out of Stock',
			array(
				'Product: ' . $name,
			)
		);
	}

	/**
	 * Contact Form 7 sent.
	 *
	 * @param WPCF7_ContactForm $contact_form Contact form.
	 * @return void
	 */
	public function contact_form_7_sent( $contact_form ) {
		if ( ! $this->integration_enabled( 'contact_form_7' ) || ! is_object( $contact_form ) ) {
			return;
		}

		$title = method_exists( $contact_form, 'title' ) ? $this->safe_value( $contact_form->title() ) : '';
		$id    = method_exists( $contact_form, 'id' ) ? $this->safe_value( $contact_form->id() ) : '';

		$this->send_integration_alert(
			'📨 Contact Form 7 Submission',
			array(
				'Form: ' . $title . ( '' !== $id ? ' (#' . $id . ')' : '' ),
			)
		);
	}

	/**
	 * WPForms submitted.
	 *
	 * @param array $fields    Fields.
	 * @param array $entry     Entry.
	 * @param array $form_data Form data.
	 * @param int   $entry_id  Entry ID.
	 * @return void
	 */
	public function wpforms_submitted( $fields, $entry, $form_data, $entry_id ) {
		unset( $fields, $entry );

		if ( ! $this->integration_enabled( 'wpforms' ) || ! is_array( $form_data ) ) {
			return;
		}

		$settings = isset( $form_data['settings'] ) && is_array( $form_data['settings'] ) ? $form_data['settings'] : array();
		$title    = $this->safe_value( isset( $settings['form_title'] ) ? $settings['form_title'] : '' );
		$id       = $this->safe_value( isset( $form_data['id'] ) ? $form_data['id'] : '' );
		$entry_id = $this->safe_value( $entry_id );

		$this->send_integration_alert(
			'📨 WPForms Submission',
			array(
				'Form: ' . $title . ( '' !== $id ? ' (#' . $id . ')' : '' ),
				'Entry ID: ' . $entry_id,
			)
		);
	}

	/**
	 * Fluent Forms submitted.
	 *
	 * @param int   $entry_id  Entry ID.
	 * @param array $form_data Form data.
	 * @return void
	 */
	public function fluentform_submitted( $entry_id, $form_data ) {
		if ( ! $this->integration_enabled( 'fluentforms' ) || ! is_array( $form_data ) ) {
			return;
		}

		$title    = $this->safe_value( isset( $form_data['title'] ) ? $form_data['title'] : '' );
		$id       = $this->safe_value( isset( $form_data['id'] ) ? $form_data['id'] : '' );
		$entry_id = $this->safe_value( $entry_id );

		$this->send_integration_alert(
			'📨 Fluent Forms Submission',
			array(
				'Form: ' . $title . ( '' !== $id ? ' (#' . $id . ')' : '' ),
				'Entry ID: ' . $entry_id,
			)
		);
	}

	/**
	 * Ninja Forms submitted.
	 *
	 * @param array $form_data Form data.
	 * @return void
	 */
	public function ninja_forms_submitted( $form_data ) {
		if ( ! $this->integration_enabled( 'ninja_forms' ) || ! is_array( $form_data ) ) {
			return;
		}

		$settings = isset( $form_data['settings'] ) && is_array( $form_data['settings'] ) ? $form_data['settings'] : array();
		$title    = $this->safe_value( isset( $settings['title'] ) ? $settings['title'] : '' );
		$id       = $this->safe_value( isset( $form_data['form_id'] ) ? $form_data['form_id'] : '' );

		$this->send_integration_alert(
			'📨 Ninja Forms Submission',
			array(
				'Form: ' . $title . ( '' !== $id ? ' (#' . $id . ')' : '' ),
			)
		);
	}

	/**
	 * Elementor Pro Forms submitted.
	 *
	 * @param object $record Form record.
	 * @param object $handler Handler.
	 * @return void
	 */
	public function elementor_forms_submitted( $record, $handler ) {
		unset( $handler );

		if ( ! $this->integration_enabled( 'elementor_forms' ) || ! is_object( $record ) ) {
			return;
		}

		$title = '';

		if ( method_exists( $record, 'get_form_settings' ) ) {
			$form_name = $record->get_form_settings( 'form_name' );

			if ( is_string( $form_name ) && '' !== $form_name ) {
				$title = $this->safe_value( $form_name );
			}
		}

		$this->send_integration_alert(
			'📨 Elementor Forms Submission',
			array(
				'Form: ' . $title,
			)
		);
	}

	/**
	 * Gravity Forms submitted.
	 *
	 * @param array $entry Entry.
	 * @param array $form  Form.
	 * @return void
	 */
	public function gravity_forms_submitted( $entry, $form ) {
		unset( $entry );

		if ( ! $this->integration_enabled( 'gravity_forms' ) || ! is_array( $form ) ) {
			return;
		}

		$title = $this->safe_value( isset( $form['title'] ) ? $form['title'] : '' );
		$id    = $this->safe_value( isset( $form['id'] ) ? $form['id'] : '' );

		$this->send_integration_alert(
			'📨 Gravity Forms Submission',
			array(
				'Form: ' . $title . ( '' !== $id ? ' (#' . $id . ')' : '' ),
			)
		);
	}
}
