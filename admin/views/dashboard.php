<?php
/**
 * Dashboard view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$configured = myp_telegram_settings()->is_configured();
?>
<div class="myp-card">
	<div class="myp-card__title"><?php esc_html_e( 'Current status', 'telegram-bot' ); ?></div>
	<ul class="myp-list">
		<li><?php esc_html_e( 'Notifications enabled:', 'telegram-bot' ); ?> <strong><?php echo empty( $settings['enabled'] ) ? esc_html__( 'No', 'telegram-bot' ) : esc_html__( 'Yes', 'telegram-bot' ); ?></strong></li>
		<li><?php esc_html_e( 'Telegram configuration:', 'telegram-bot' ); ?> <strong><?php echo $configured ? esc_html__( 'Ready', 'telegram-bot' ) : esc_html__( 'Incomplete', 'telegram-bot' ); ?></strong></li>
		<li><?php esc_html_e( 'Chat IDs:', 'telegram-bot' ); ?> <strong><?php echo esc_html( '' !== trim( (string) $settings['chat_ids'] ) ? $settings['chat_ids'] : __( 'Not set', 'telegram-bot' ) ); ?></strong></li>
	</ul>
</div>

<div class="myp-card">
	<div class="myp-card__title"><?php esc_html_e( 'Quick actions', 'telegram-bot' ); ?></div>
	<p class="myp-card__desc"><?php esc_html_e( 'Test delivery after saving your bot token and chat ID.', 'telegram-bot' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="myp_telegram_test">
		<?php wp_nonce_field( 'myp_telegram_test' ); ?>
		<p class="myp-submit">
			<button type="submit" class="button"><?php esc_html_e( 'Send test message', 'telegram-bot' ); ?></button>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=myp-telegram-settings' ) ); ?>"><?php esc_html_e( 'Open Telegram settings', 'telegram-bot' ); ?></a>
		</p>
	</form>
</div>
