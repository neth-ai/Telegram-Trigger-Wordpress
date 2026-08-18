<?php
/**
 * Alerts view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="alerts">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<div class="myp-card">
		<label class="myp-master">
			<input type="checkbox" name="users[enabled]" value="1" <?php checked( ! empty( $settings['users']['enabled'] ) ); ?>>
			<strong><?php esc_html_e( 'User action alerts', 'telegram-bot' ); ?></strong>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'users', $this->user_events(), $settings['users']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card">
		<label class="myp-master">
			<input type="checkbox" name="system[enabled]" value="1" <?php checked( ! empty( $settings['system']['enabled'] ) ); ?>>
			<strong><?php esc_html_e( 'System alerts', 'telegram-bot' ); ?></strong>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'system', $this->system_events(), $settings['system']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card">
		<label class="myp-master">
			<input type="checkbox" name="available_updates[enabled]" value="1" <?php checked( ! empty( $settings['available_updates']['enabled'] ) ); ?>>
			<strong><?php esc_html_e( 'Available updates digest', 'telegram-bot' ); ?></strong>
		</label>
		<label class="myp-field">
			<span><?php esc_html_e( 'Digest frequency', 'telegram-bot' ); ?></span>
			<select name="available_updates[schedule]">
				<option value="daily" <?php selected( 'daily', $settings['available_updates']['schedule'] ); ?>><?php esc_html_e( 'Daily', 'telegram-bot' ); ?></option>
				<option value="twicedaily" <?php selected( 'twicedaily', $settings['available_updates']['schedule'] ); ?>><?php esc_html_e( 'Twice daily', 'telegram-bot' ); ?></option>
				<option value="hourly" <?php selected( 'hourly', $settings['available_updates']['schedule'] ); ?>><?php esc_html_e( 'Hourly', 'telegram-bot' ); ?></option>
			</select>
		</label>
	</div>

	<p class="myp-submit">
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Save alerts', 'telegram-bot' ); ?></button>
	</p>
</form>
