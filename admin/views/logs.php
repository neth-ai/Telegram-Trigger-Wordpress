<?php
/**
 * Logs view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="myp-card">
	<div class="myp-card__title"><?php esc_html_e( 'Recent entries', 'telegram-bot' ); ?></div>

	<?php if ( empty( $logs ) ) : ?>
		<p class="myp-card__desc"><?php esc_html_e( 'No diagnostic entries have been recorded yet.', 'telegram-bot' ); ?></p>
	<?php else : ?>
		<div class="myp-table-scroll" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Recent Telegram Bot log entries', 'telegram-bot' ); ?>">
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'telegram-bot' ); ?></th>
						<th><?php esc_html_e( 'Context', 'telegram-bot' ); ?></th>
						<th><?php esc_html_e( 'Level', 'telegram-bot' ); ?></th>
						<th><?php esc_html_e( 'Message', 'telegram-bot' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( $log['time'] ); ?></td>
							<td><?php echo esc_html( $log['context'] ); ?></td>
							<td><?php echo esc_html( $log['level'] ); ?></td>
							<td><?php echo esc_html( $log['message'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="myp-card">
	<input type="hidden" name="action" value="myp_telegram_clear_logs">
	<?php wp_nonce_field( 'myp_telegram_clear_logs' ); ?>
	<p class="myp-submit">
		<button type="submit" class="button"><?php esc_html_e( 'Clear logs', 'telegram-bot' ); ?></button>
	</p>
</form>
</div>
