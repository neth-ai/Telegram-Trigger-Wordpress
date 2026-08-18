<?php
/**
 * Templates and developer view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="myp-card">
	<div class="myp-card__title"><?php esc_html_e( 'Shortcode', 'telegram-bot' ); ?></div>
	<p class="myp-card__desc"><?php esc_html_e( 'Send a custom message anywhere a shortcode is processed:', 'telegram-bot' ); ?></p>
	<pre class="myp-code">[myp_telegram message="Hello from WordPress"]</pre>
</div>

<div class="myp-card">
	<div class="myp-card__title"><?php esc_html_e( 'PHP action hook', 'telegram-bot' ); ?></div>
	<pre class="myp-code">do_action( 'myp_telegram_send', 'Your message here' );</pre>
	<p class="myp-card__desc"><?php esc_html_e( 'Or call the helper directly:', 'telegram-bot' ); ?></p>
	<pre class="myp-code">myp_telegram_send_notification( 'Your message here' );</pre>
</div>

<div class="myp-card">
	<div class="myp-card__title"><?php esc_html_e( 'Customizing message content', 'telegram-bot' ); ?></div>
	<pre class="myp-code">add_filter( 'myp_telegram_message', function ( $message, $context ) {
    return $message . "\n" . 'Extra info: ' . $context['event'];
}, 10, 2 );</pre>
</div>

<div class="myp-card">
	<div class="myp-card__title"><?php esc_html_e( 'Popular integrations worth considering', 'telegram-bot' ); ?></div>
	<ul class="myp-list">
		<li><strong>WooCommerce</strong> — new orders, order status changes, low/out-of-stock products.</li>
		<li><strong>Contact Form 7, WPForms, Fluent Forms, Ninja Forms, Elementor Forms, Gravity Forms</strong> — form submission alerts.</li>
		<li><strong>WP Activity Log</strong> — relay security and activity events through its action hooks.</li>
		<li><strong>Wordfence / Solid Security</strong> — failed logins, malware, firewall, and file-change alerts.</li>
		<li><strong>Members / User Role Editor</strong> — role and capability changes.</li>
		<li><strong>LearnDash / LifterLMS / Tutor LMS</strong> — enrollments, quiz submissions, and course completion.</li>
	</ul>
</div>
