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
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="myp-form">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="triggers">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="content[enabled]" value="1" <?php checked( ! empty( $settings['content']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--blue dashicons dashicons-admin-post" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'Content notifications', 'telegram-bot' ); ?></strong>
				<small><?php esc_html_e( 'Track publishing and editorial activity across selected post types.', 'telegram-bot' ); ?></small>
			</span>
		</label>

		<div class="myp-section">
			<div class="myp-section__title"><?php esc_html_e( 'Post types', 'telegram-bot' ); ?></div>
			<div class="myp-check-grid myp-check-grid--post-types">
				<?php foreach ( $post_types as $type => $label ) : ?>
					<label class="myp-choice myp-choice--post-type">
						<input type="checkbox" name="content[post_types][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, (array) $settings['content']['post_types'], true ) ); ?>>
						<span><strong><?php echo esc_html( $label ); ?></strong><code><?php echo esc_html( $type ); ?></code></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="myp-section">
			<div class="myp-section__title"><?php esc_html_e( 'Content events', 'telegram-bot' ); ?></div>
			<?php $this->render_event_checkboxes( 'content', $this->content_events(), $settings['content']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="media[enabled]" value="1" <?php checked( ! empty( $settings['media']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--violet dashicons dashicons-format-image" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'Media notifications', 'telegram-bot' ); ?></strong>
				<small><?php esc_html_e( 'Receive an alert whenever a new media file is uploaded.', 'telegram-bot' ); ?></small>
			</span>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'media', $this->media_events(), $settings['media']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="comments[enabled]" value="1" <?php checked( ! empty( $settings['comments']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--green dashicons dashicons-admin-comments" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'Comment notifications', 'telegram-bot' ); ?></strong>
				<small><?php esc_html_e( 'Follow moderation, spam, trash, and deletion events.', 'telegram-bot' ); ?></small>
			</span>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'comments', $this->comment_events(), $settings['comments']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="integrations[enabled]" value="1" <?php checked( ! empty( $settings['integrations']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--orange dashicons dashicons-admin-plugins" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'Optional integrations', 'telegram-bot' ); ?></strong>
				<small><?php esc_html_e( 'Connect supported commerce, forms, and page-builder plugins.', 'telegram-bot' ); ?></small>
			</span>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'integrations', $this->integration_events(), $settings['integrations']['events'] ); ?>
		</div>
	</div>

	<div class="myp-action-bar">
		<div>
			<strong><?php esc_html_e( 'Apply trigger changes', 'telegram-bot' ); ?></strong>
			<span><?php esc_html_e( 'Only enabled groups and selected events will send messages.', 'telegram-bot' ); ?></span>
		</div>
		<button type="submit" class="button button-primary myp-button">
			<span class="dashicons dashicons-saved" aria-hidden="true"></span>
			<?php esc_html_e( 'Save triggers', 'telegram-bot' ); ?>
		</button>
	</div>
</form>
</div>
