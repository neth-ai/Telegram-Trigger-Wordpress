<?php
/**
 * Telegram settings view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="myp-card">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="settings">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<div class="myp-card__title"><?php esc_html_e( 'Bot connection', 'telegram-bot' ); ?></div>
	<p class="myp-card__desc"><?php esc_html_e( 'Create a bot with @BotFather, then paste the token and chat ID below. Multiple chat IDs can be comma-separated.', 'telegram-bot' ); ?></p>

	<label class="myp-field">
		<span><?php esc_html_e( 'Enable Telegram notifications', 'telegram-bot' ); ?></span>
		<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
	</label>

	<label class="myp-field">
		<span><?php esc_html_e( 'Bot Token', 'telegram-bot' ); ?></span>
		<input type="password" name="bot_token" value="<?php echo esc_attr( $settings['bot_token'] ); ?>" autocomplete="off" spellcheck="false">
		<small><?php esc_html_e( 'Example: 123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw', 'telegram-bot' ); ?></small>
	</label>

	<label class="myp-field">
		<span><?php esc_html_e( 'Chat ID(s)', 'telegram-bot' ); ?></span>
		<input type="text" name="chat_ids" value="<?php echo esc_attr( $settings['chat_ids'] ); ?>" autocomplete="off" spellcheck="false">
		<small><?php esc_html_e( 'Separate recipients with commas, for example: 123456789,-1001234567890', 'telegram-bot' ); ?></small>
	</label>

	<div class="myp-grid">
		<label class="myp-field">
			<span><?php esc_html_e( 'Parse mode', 'telegram-bot' ); ?></span>
			<select name="parse_mode">
				<option value="" <?php selected( '', $settings['parse_mode'] ); ?>><?php esc_html_e( 'Plain text (recommended)', 'telegram-bot' ); ?></option>
				<option value="HTML" <?php selected( 'HTML', $settings['parse_mode'] ); ?>>HTML</option>
				<option value="MarkdownV2" <?php selected( 'MarkdownV2', $settings['parse_mode'] ); ?>>MarkdownV2</option>
			</select>
		</label>

		<label class="myp-field">
			<span><?php esc_html_e( 'Duplicate suppression (seconds)', 'telegram-bot' ); ?></span>
			<input type="number" min="0" max="3600" name="duplicate_ttl" value="<?php echo esc_attr( (int) $settings['duplicate_ttl'] ); ?>">
		</label>
	</div>

	<label class="myp-field">
		<span><?php esc_html_e( 'Disable web page preview', 'telegram-bot' ); ?></span>
		<input type="checkbox" name="disable_web_page_preview" value="1" <?php checked( ! empty( $settings['disable_web_page_preview'] ) ); ?>>
	</label>

	<p class="myp-submit">
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Save settings', 'telegram-bot' ); ?></button>
	</p>
</form>
