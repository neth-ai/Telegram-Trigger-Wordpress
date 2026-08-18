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
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="myp-form">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="alerts">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="users[enabled]" value="1" <?php checked( ! empty( $settings['users']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--blue dashicons dashicons-admin-users" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'User action alerts', 'telegram-bot' ); ?></strong>
				<small><?php esc_html_e( 'Monitor registrations, access activity, roles, and account changes.', 'telegram-bot' ); ?></small>
			</span>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'users', $this->user_events(), $settings['users']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="system[enabled]" value="1" <?php checked( ! empty( $settings['system']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--violet dashicons dashicons-admin-tools" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'System alerts', 'telegram-bot' ); ?></strong>
				<small><?php esc_html_e( 'Track plugin, theme, WordPress core, and language changes.', 'telegram-bot' ); ?></small>
			</span>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'system', $this->system_events(), $settings['system']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="available_updates[enabled]" value="1" <?php checked( ! empty( $settings['available_updates']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--green dashicons dashicons-update" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'Available updates digest', 'telegram-bot' ); ?></strong>
				<small><?php esc_html_e( 'Receive a scheduled summary when WordPress updates are waiting.', 'telegram-bot' ); ?></small>
			</span>
		</label>
		<label class="myp-field myp-field--compact">
			<span><?php esc_html_e( 'Digest frequency', 'telegram-bot' ); ?></span>
			<select name="available_updates[schedule]">
				<option value="daily" <?php selected( 'daily', $settings['available_updates']['schedule'] ); ?>><?php esc_html_e( 'Daily', 'telegram-bot' ); ?></option>
				<option value="twicedaily" <?php selected( 'twicedaily', $settings['available_updates']['schedule'] ); ?>><?php esc_html_e( 'Twice daily', 'telegram-bot' ); ?></option>
				<option value="hourly" <?php selected( 'hourly', $settings['available_updates']['schedule'] ); ?>><?php esc_html_e( 'Hourly', 'telegram-bot' ); ?></option>
			</select>
		</label>
	</div>

	<div class="myp-action-bar">
		<div>
			<strong><?php esc_html_e( 'Apply alert changes', 'telegram-bot' ); ?></strong>
			<span><?php esc_html_e( 'New user and system activity will use this configuration.', 'telegram-bot' ); ?></span>
		</div>
		<button type="submit" class="button button-primary myp-button">
			<span class="dashicons dashicons-saved" aria-hidden="true"></span>
			<?php esc_html_e( 'Save alerts', 'telegram-bot' ); ?>
		</button>
	</div>
</form>
</div>
