<?php
/**
 * Trigger manager view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="triggers">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<div class="myp-card">
		<label class="myp-master">
			<input type="checkbox" name="content[enabled]" value="1" <?php checked( ! empty( $settings['content']['enabled'] ) ); ?>>
			<strong><?php esc_html_e( 'Content notifications', 'telegram-bot' ); ?></strong>
		</label>

		<div class="myp-section">
			<div class="myp-section__title"><?php esc_html_e( 'Post types', 'telegram-bot' ); ?></div>
			<div class="myp-check-grid">
				<?php foreach ( $post_types as $type => $label ) : ?>
					<label>
						<input type="checkbox" name="content[post_types][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, (array) $settings['content']['post_types'], true ) ); ?>>
						<?php echo esc_html( $label ); ?> <code><?php echo esc_html( $type ); ?></code>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="myp-section">
			<div class="myp-section__title"><?php esc_html_e( 'Content events', 'telegram-bot' ); ?></div>
			<?php $this->render_event_checkboxes( 'content', $this->content_events(), $settings['content']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card">
		<label class="myp-master">
			<input type="checkbox" name="media[enabled]" value="1" <?php checked( ! empty( $settings['media']['enabled'] ) ); ?>>
			<strong><?php esc_html_e( 'Media notifications', 'telegram-bot' ); ?></strong>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'media', $this->media_events(), $settings['media']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card">
		<label class="myp-master">
			<input type="checkbox" name="comments[enabled]" value="1" <?php checked( ! empty( $settings['comments']['enabled'] ) ); ?>>
			<strong><?php esc_html_e( 'Comment notifications', 'telegram-bot' ); ?></strong>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'comments', $this->comment_events(), $settings['comments']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card">
		<label class="myp-master">
			<input type="checkbox" name="integrations[enabled]" value="1" <?php checked( ! empty( $settings['integrations']['enabled'] ) ); ?>>
			<strong><?php esc_html_e( 'Optional integrations', 'telegram-bot' ); ?></strong>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'integrations', $this->integration_events(), $settings['integrations']['events'] ); ?>
		</div>
	</div>

	<p class="myp-submit">
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Save triggers', 'telegram-bot' ); ?></button>
	</p>
</form>
