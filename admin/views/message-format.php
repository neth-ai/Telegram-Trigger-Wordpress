<?php
/**
 * Telegram message-format editor.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$format_manager = MYP_Telegram_Template_Manager::instance();
$formats        = isset( $settings['message_formats'] ) && is_array( $settings['message_formats'] ) ? $settings['message_formats'] : array();
?>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="myp-form">
	<input type="hidden" name="action" value="myp_telegram_save">
	<input type="hidden" name="myp_page" value="message-format">
	<?php wp_nonce_field( 'myp_telegram_save' ); ?>

	<section class="myp-card myp-format-help">
		<div class="myp-card__header">
			<div>
				<div class="myp-card__title"><?php esc_html_e( 'How message formats work', 'telegram-trigger' ); ?></div>
				<p class="myp-card__desc"><?php esc_html_e( 'Write labels in any language and place the available dynamic values wherever you want. A complete line is removed automatically when its optional value is empty.', 'telegram-trigger' ); ?></p>
			</div>
		</div>
		<p class="myp-format-help__example"><code>សកម្មភាព: {action_text}</code> → <span><?php esc_html_e( 'The Khmer label stays fixed while the action value changes for each event.', 'telegram-trigger' ); ?></span></p>
	</section>

	<?php foreach ( $definitions as $type => $definition ) : ?>
		<?php
		$format         = isset( $formats[ $type ] ) && is_array( $formats[ $type ] ) ? $formats[ $type ] : array();
		$icon           = isset( $format['icon'] ) ? (string) $format['icon'] : '';
		$template       = isset( $format['template'] ) ? (string) $format['template'] : '';
		$preview_values = $format_manager->get_preview_values( $type );
		$preview        = $format_manager->format_message( $type, $preview_values );
		$preview_data   = wp_json_encode( $preview_values );
		?>
		<section class="myp-card myp-format-card" data-myp-format-card data-preview-values="<?php echo esc_attr( $preview_data ); ?>">
			<div class="myp-card__header">
				<div>
					<div class="myp-card__title"><?php echo esc_html( $definition['title'] ); ?></div>
					<p class="myp-card__desc"><?php echo esc_html( $definition['description'] ); ?></p>
				</div>
			</div>

			<div class="myp-format-layout">
				<div class="myp-format-editor">
					<label class="myp-field myp-format-icon">
						<span><?php esc_html_e( 'Icon or emoji', 'telegram-trigger' ); ?></span>
						<input type="text" name="message_formats[<?php echo esc_attr( $type ); ?>][icon]" value="<?php echo esc_attr( $icon ); ?>" maxlength="40" data-myp-format-icon>
						<small><?php esc_html_e( 'One or more emoji are allowed. Leave empty if the title already contains an icon.', 'telegram-trigger' ); ?></small>
					</label>

					<label class="myp-field">
						<span><?php esc_html_e( 'Message template', 'telegram-trigger' ); ?></span>
						<textarea name="message_formats[<?php echo esc_attr( $type ); ?>][template]" rows="10" maxlength="4000" data-myp-format-template><?php echo esc_textarea( $template ); ?></textarea>
					</label>

					<div class="myp-format-placeholders" aria-label="<?php esc_attr_e( 'Available placeholders', 'telegram-trigger' ); ?>">
						<span><?php esc_html_e( 'Insert value:', 'telegram-trigger' ); ?></span>
						<?php foreach ( $definition['placeholders'] as $placeholder ) : ?>
							<button type="button" class="myp-placeholder" data-myp-insert-placeholder="{<?php echo esc_attr( $placeholder ); ?>}"><code>{<?php echo esc_html( $placeholder ); ?>}</code></button>
						<?php endforeach; ?>
					</div>

					<?php if ( ! empty( $definition['show_role'] ) ) : ?>
						<div class="myp-setting-row myp-setting-row--nested myp-format-option">
							<div class="myp-setting-row__icon myp-setting-row__icon--violet"><span class="dashicons dashicons-admin-users" aria-hidden="true"></span></div>
							<div class="myp-setting-row__copy">
								<strong><?php esc_html_e( 'Show role', 'telegram-trigger' ); ?></strong>
								<span><?php esc_html_e( 'Turn off to remove the complete line containing {role}.', 'telegram-trigger' ); ?></span>
							</div>
							<label class="myp-switch">
								<input type="checkbox" name="message_formats[<?php echo esc_attr( $type ); ?>][show_role]" value="1" <?php checked( ! empty( $format['show_role'] ) ); ?> data-myp-show-role>
								<span class="myp-switch__control" aria-hidden="true"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Show role', 'telegram-trigger' ); ?></span>
							</label>
						</div>
					<?php endif; ?>
				</div>

				<div class="myp-format-preview">
					<span><?php esc_html_e( 'Telegram preview', 'telegram-trigger' ); ?></span>
					<pre data-myp-format-preview><?php echo esc_html( $preview ); ?></pre>
				</div>
			</div>
		</section>
	<?php endforeach; ?>

	<div class="myp-action-bar">
		<div>
			<strong><?php esc_html_e( 'Apply message formats', 'telegram-trigger' ); ?></strong>
			<span><?php esc_html_e( 'New Telegram notifications will use these icons, labels, fields, and placeholders.', 'telegram-trigger' ); ?></span>
		</div>
		<button type="submit" class="button button-primary myp-button">
			<span class="dashicons dashicons-saved" aria-hidden="true"></span>
			<?php esc_html_e( 'Save message formats', 'telegram-trigger' ); ?>
		</button>
	</div>
</form>
</div>
