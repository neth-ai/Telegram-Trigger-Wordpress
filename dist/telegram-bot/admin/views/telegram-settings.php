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
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="myp-form">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="settings">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<section class="myp-card myp-card--connection-state">
		<div class="myp-setting-row">
			<div class="myp-setting-row__icon myp-setting-row__icon--blue"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span></div>
			<div class="myp-setting-row__copy">
				<strong><?php esc_html_e( 'Telegram delivery', 'telegram-bot' ); ?></strong>
				<span><?php esc_html_e( 'Master switch for all outgoing Telegram notifications.', 'telegram-bot' ); ?></span>
			</div>
			<label class="myp-switch">
				<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
				<span class="myp-switch__control" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Enable Telegram notifications', 'telegram-bot' ); ?></span>
			</label>
		</div>
	</section>

	<section class="myp-card">
		<div class="myp-card__header">
			<div>
				<div class="myp-card__title"><?php esc_html_e( 'Bot credentials', 'telegram-bot' ); ?></div>
				<p class="myp-card__desc"><?php esc_html_e( 'Create a bot with @BotFather, then connect it to one or more Telegram conversations.', 'telegram-bot' ); ?></p>
			</div>
			<span class="myp-card__badge"><?php esc_html_e( 'Required', 'telegram-bot' ); ?></span>
		</div>

		<div class="myp-field-grid">
			<div class="myp-field myp-field--full">
				<label for="myp-bot-token"><?php esc_html_e( 'Bot token', 'telegram-bot' ); ?></label>
				<span class="myp-input-group">
					<input id="myp-bot-token" type="password" name="bot_token" value="<?php echo esc_attr( $settings['bot_token'] ); ?>" placeholder="123456789:AA..." autocomplete="off" spellcheck="false">
					<button type="button" class="myp-input-action" data-myp-password-toggle="myp-bot-token" data-label-show="<?php esc_attr_e( 'Show bot token', 'telegram-bot' ); ?>" data-label-hide="<?php esc_attr_e( 'Hide bot token', 'telegram-bot' ); ?>" aria-label="<?php esc_attr_e( 'Show bot token', 'telegram-bot' ); ?>">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					</button>
				</span>
				<small><?php esc_html_e( 'Keep this token private. It authorizes anyone who has it to control your bot.', 'telegram-bot' ); ?></small>
			</div>

			<label class="myp-field myp-field--full">
				<span><?php esc_html_e( 'Chat ID(s)', 'telegram-bot' ); ?></span>
				<input type="text" name="chat_ids" value="<?php echo esc_attr( $settings['chat_ids'] ); ?>" placeholder="123456789, -1001234567890" autocomplete="off" spellcheck="false">
				<small><?php esc_html_e( 'Separate multiple users, groups, or channels with commas.', 'telegram-bot' ); ?></small>
			</label>
		</div>
	</section>

	<section class="myp-card">
		<div class="myp-card__header">
			<div>
				<div class="myp-card__title"><?php esc_html_e( 'Delivery preferences', 'telegram-bot' ); ?></div>
				<p class="myp-card__desc"><?php esc_html_e( 'Control formatting and duplicate protection for outgoing messages.', 'telegram-bot' ); ?></p>
			</div>
		</div>

		<div class="myp-field-grid">
			<label class="myp-field">
				<span><?php esc_html_e( 'Parse mode', 'telegram-bot' ); ?></span>
				<select name="parse_mode">
					<option value="" <?php selected( '', $settings['parse_mode'] ); ?>><?php esc_html_e( 'Plain text (recommended)', 'telegram-bot' ); ?></option>
					<option value="HTML" <?php selected( 'HTML', $settings['parse_mode'] ); ?>>HTML</option>
					<option value="MarkdownV2" <?php selected( 'MarkdownV2', $settings['parse_mode'] ); ?>>MarkdownV2</option>
				</select>
				<small><?php esc_html_e( 'Plain text is the safest option for dynamic WordPress content.', 'telegram-bot' ); ?></small>
			</label>

			<label class="myp-field">
				<span><?php esc_html_e( 'Duplicate suppression', 'telegram-bot' ); ?></span>
				<span class="myp-input-suffix">
					<input type="number" min="0" max="3600" name="duplicate_ttl" value="<?php echo esc_attr( (int) $settings['duplicate_ttl'] ); ?>">
					<span><?php esc_html_e( 'seconds', 'telegram-bot' ); ?></span>
				</span>
				<small><?php esc_html_e( 'Ignore identical notifications during this time window.', 'telegram-bot' ); ?></small>
			</label>
		</div>

		<div class="myp-setting-row myp-setting-row--nested">
			<div class="myp-setting-row__icon myp-setting-row__icon--violet"><span class="dashicons dashicons-hidden" aria-hidden="true"></span></div>
			<div class="myp-setting-row__copy">
				<strong><?php esc_html_e( 'Disable link previews', 'telegram-bot' ); ?></strong>
				<span><?php esc_html_e( 'Keep alerts compact by hiding website preview cards in Telegram.', 'telegram-bot' ); ?></span>
			</div>
			<label class="myp-switch">
				<input type="checkbox" name="disable_web_page_preview" value="1" <?php checked( ! empty( $settings['disable_web_page_preview'] ) ); ?>>
				<span class="myp-switch__control" aria-hidden="true"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Disable web page preview', 'telegram-bot' ); ?></span>
			</label>
		</div>
	</section>

	<div class="myp-action-bar">
		<div>
			<strong><?php esc_html_e( 'Save your connection', 'telegram-bot' ); ?></strong>
			<span><?php esc_html_e( 'Changes take effect immediately for new events.', 'telegram-bot' ); ?></span>
		</div>
		<button type="submit" class="button button-primary myp-button">
			<span class="dashicons dashicons-saved" aria-hidden="true"></span>
			<?php esc_html_e( 'Save settings', 'telegram-bot' ); ?>
		</button>
	</div>
</form>
</div>
