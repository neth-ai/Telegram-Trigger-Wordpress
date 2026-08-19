<?php
/**
 * Trigger manager view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$available_integration_count = count( array_filter( $integration_availability ) );
$integrations_enabled        = 0 < $available_integration_count && ! empty( $settings['integrations']['enabled'] );
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
				<strong><?php esc_html_e( 'Content notifications', 'telegram-trigger' ); ?></strong>
				<small><?php esc_html_e( 'Track publishing and editorial activity across selected post types.', 'telegram-trigger' ); ?></small>
			</span>
		</label>

		<div class="myp-section">
			<div class="myp-section__title"><?php esc_html_e( 'Post types', 'telegram-trigger' ); ?></div>
			<p class="myp-section__desc"><?php esc_html_e( 'All registered post types shown in the WordPress admin menu are detected automatically, including private types added by themes, plugins, and MU plugins.', 'telegram-trigger' ); ?></p>
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
			<div class="myp-section__title"><?php esc_html_e( 'Content events', 'telegram-trigger' ); ?></div>
			<?php $this->render_event_checkboxes( 'content', $this->content_events(), $settings['content']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="media[enabled]" value="1" <?php checked( ! empty( $settings['media']['enabled'] ) ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--violet dashicons dashicons-format-image" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'Media notifications', 'telegram-trigger' ); ?></strong>
				<small><?php esc_html_e( 'Receive an alert whenever a new media file is uploaded.', 'telegram-trigger' ); ?></small>
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
				<strong><?php esc_html_e( 'Comment notifications', 'telegram-trigger' ); ?></strong>
				<small><?php esc_html_e( 'Follow moderation, spam, trash, and deletion events.', 'telegram-trigger' ); ?></small>
			</span>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'comments', $this->comment_events(), $settings['comments']['events'] ); ?>
		</div>
	</div>

	<div class="myp-card myp-card--feature">
		<label class="myp-master">
			<input type="checkbox" name="integrations[enabled]" value="1" <?php checked( $integrations_enabled ); ?> <?php disabled( 0 === $available_integration_count ); ?>>
			<span class="myp-switch__control" aria-hidden="true"></span>
			<span class="myp-master__icon myp-master__icon--orange dashicons dashicons-admin-plugins" aria-hidden="true"></span>
			<span class="myp-master__copy">
				<strong><?php esc_html_e( 'Optional integrations', 'telegram-trigger' ); ?></strong>
				<small>
					<?php
					echo 0 === $available_integration_count
						? esc_html__( 'No supported integration plugin is active. This section is safely disabled.', 'telegram-trigger' )
						: esc_html__( 'Only detected, active commerce, forms, and page-builder plugins can be selected.', 'telegram-trigger' );
					?>
				</small>
			</span>
		</label>
		<div class="myp-section">
			<?php $this->render_event_checkboxes( 'integrations', $this->integration_events(), $settings['integrations']['events'], $integration_availability ); ?>
		</div>
	</div>

	<div class="myp-action-bar">
		<div>
			<strong><?php esc_html_e( 'Apply trigger changes', 'telegram-trigger' ); ?></strong>
			<span><?php esc_html_e( 'Only enabled groups and selected events will send messages.', 'telegram-trigger' ); ?></span>
		</div>
		<button type="submit" class="button button-primary myp-button">
			<span class="dashicons dashicons-saved" aria-hidden="true"></span>
			<?php esc_html_e( 'Save triggers', 'telegram-trigger' ); ?>
		</button>
	</div>
</form>
</div>
