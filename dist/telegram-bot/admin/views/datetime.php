<?php
/**
 * Date and time format settings view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$datetime = $settings['datetime'];
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="myp-form">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="datetime">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<section class="myp-card myp-datetime-preview">
		<div class="myp-datetime-preview__icon"><span class="dashicons dashicons-clock" aria-hidden="true"></span></div>
		<div>
			<span><?php esc_html_e( 'Current format preview', 'telegram-bot' ); ?></span>
			<strong><?php echo esc_html( $preview ); ?></strong>
			<small>
				<?php
				printf(
					/* translators: %s: Selected message timezone. */
					esc_html__( 'Message timezone: %s', 'telegram-bot' ),
					esc_html( $timezone )
				);
				?>
			</small>
		</div>
	</section>

	<section class="myp-card">
		<div class="myp-card__header">
			<div>
				<div class="myp-card__title"><?php esc_html_e( 'Date format', 'telegram-bot' ); ?></div>
				<p class="myp-card__desc"><?php esc_html_e( 'Choose the order and appearance of the day, month, and year.', 'telegram-bot' ); ?></p>
			</div>
		</div>

		<div class="myp-field-grid">
			<label class="myp-field myp-field--full">
				<span><?php esc_html_e( 'Message timezone', 'telegram-bot' ); ?></span>
				<select name="datetime[timezone]">
					<option value="wordpress" <?php selected( 'wordpress', $datetime['timezone'] ); ?>>
						<?php
						printf(
							/* translators: %s: Current WordPress timezone. */
							esc_html__( 'Use WordPress site timezone — %s', 'telegram-bot' ),
							esc_html( wp_timezone_string() )
						);
						?>
					</option>
					<?php echo wp_timezone_choice( $datetime['timezone'], get_user_locale() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted WordPress core-generated options. ?>
				</select>
				<small><?php esc_html_e( 'Asia/Phnom_Penh is the default and displays Cambodia time (UTC+07:00).', 'telegram-bot' ); ?></small>
			</label>

			<label class="myp-field">
				<span><?php esc_html_e( 'Date order', 'telegram-bot' ); ?></span>
				<select name="datetime[date_order]">
					<option value="dmy" <?php selected( 'dmy', $datetime['date_order'] ); ?>><?php esc_html_e( 'DD MM YY — Day, Month, Year', 'telegram-bot' ); ?></option>
					<option value="mdy" <?php selected( 'mdy', $datetime['date_order'] ); ?>><?php esc_html_e( 'MM DD YY — Month, Day, Year', 'telegram-bot' ); ?></option>
					<option value="ymd" <?php selected( 'ymd', $datetime['date_order'] ); ?>><?php esc_html_e( 'YY MM DD — Year, Month, Day', 'telegram-bot' ); ?></option>
				</select>
			</label>

			<label class="myp-field">
				<span><?php esc_html_e( 'Month display', 'telegram-bot' ); ?></span>
				<select name="datetime[month_format]">
					<option value="numeric" <?php selected( 'numeric', $datetime['month_format'] ); ?>><?php esc_html_e( 'Number — 08', 'telegram-bot' ); ?></option>
					<option value="short" <?php selected( 'short', $datetime['month_format'] ); ?>><?php esc_html_e( 'Short name — Aug', 'telegram-bot' ); ?></option>
					<option value="full" <?php selected( 'full', $datetime['month_format'] ); ?>><?php esc_html_e( 'Full name — August', 'telegram-bot' ); ?></option>
				</select>
				<small><?php esc_html_e( 'Month names follow the WordPress site language.', 'telegram-bot' ); ?></small>
			</label>

			<label class="myp-field">
				<span><?php esc_html_e( 'Year display', 'telegram-bot' ); ?></span>
				<select name="datetime[year_format]">
					<option value="four" <?php selected( 'four', $datetime['year_format'] ); ?>><?php esc_html_e( 'Four digits — 2026', 'telegram-bot' ); ?></option>
					<option value="two" <?php selected( 'two', $datetime['year_format'] ); ?>><?php esc_html_e( 'Two digits — 26', 'telegram-bot' ); ?></option>
				</select>
			</label>

			<label class="myp-field">
				<span><?php esc_html_e( 'Date separator', 'telegram-bot' ); ?></span>
				<select name="datetime[date_separator]">
					<option value="space" <?php selected( 'space', $datetime['date_separator'] ); ?>><?php esc_html_e( 'Space — 18 08 2026', 'telegram-bot' ); ?></option>
					<option value="slash" <?php selected( 'slash', $datetime['date_separator'] ); ?>><?php esc_html_e( 'Slash — 18/08/2026', 'telegram-bot' ); ?></option>
					<option value="dash" <?php selected( 'dash', $datetime['date_separator'] ); ?>><?php esc_html_e( 'Dash — 18-08-2026', 'telegram-bot' ); ?></option>
					<option value="dot" <?php selected( 'dot', $datetime['date_separator'] ); ?>><?php esc_html_e( 'Dot — 18.08.2026', 'telegram-bot' ); ?></option>
				</select>
			</label>
		</div>
	</section>

	<section class="myp-card">
		<div class="myp-card__header">
			<div>
				<div class="myp-card__title"><?php esc_html_e( 'Time format', 'telegram-bot' ); ?></div>
				<p class="myp-card__desc"><?php esc_html_e( 'Choose a 12-hour or 24-hour clock and whether seconds are included.', 'telegram-bot' ); ?></p>
			</div>
		</div>

		<div class="myp-field-grid">
			<label class="myp-field">
				<span><?php esc_html_e( 'Clock', 'telegram-bot' ); ?></span>
				<select name="datetime[time_format]">
					<option value="12" <?php selected( '12', $datetime['time_format'] ); ?>><?php esc_html_e( '12-hour — 09:10 PM / 12:00 AM', 'telegram-bot' ); ?></option>
					<option value="24" <?php selected( '24', $datetime['time_format'] ); ?>><?php esc_html_e( '24-hour — 21:10 / 00:00', 'telegram-bot' ); ?></option>
				</select>
			</label>

			<div class="myp-setting-row myp-setting-row--nested myp-setting-row--datetime">
				<div class="myp-setting-row__icon myp-setting-row__icon--violet"><span class="dashicons dashicons-backup" aria-hidden="true"></span></div>
				<div class="myp-setting-row__copy">
					<strong><?php esc_html_e( 'Show seconds', 'telegram-bot' ); ?></strong>
					<span><?php esc_html_e( 'On: hour, minute, second. Off: hour and minute only.', 'telegram-bot' ); ?></span>
				</div>
				<label class="myp-switch">
					<input type="checkbox" name="datetime[show_seconds]" value="1" <?php checked( ! empty( $datetime['show_seconds'] ) ); ?>>
					<span class="myp-switch__control" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Show seconds', 'telegram-bot' ); ?></span>
				</label>
			</div>
		</div>
	</section>

	<div class="myp-action-bar">
		<div>
			<strong><?php esc_html_e( 'Apply date and time format', 'telegram-bot' ); ?></strong>
			<span><?php esc_html_e( 'New Telegram alerts and log entries will use this format.', 'telegram-bot' ); ?></span>
		</div>
		<button type="submit" class="button button-primary myp-button">
			<span class="dashicons dashicons-saved" aria-hidden="true"></span>
			<?php esc_html_e( 'Save format', 'telegram-bot' ); ?>
		</button>
	</div>
</form>
</div>
