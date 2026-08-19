<?php
/**
 * Dashboard view.
 *
 * @package MYP_Telegram
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$configured      = myp_telegram_settings()->is_configured();
$enabled         = ! empty( $settings['enabled'] );
$connection_ok   = $configured && $enabled;
$chat_ids        = myp_telegram_settings()->get_valid_chat_ids( $settings['chat_ids'] );
$recipient_count = count( $chat_ids );
$feature_groups  = array( 'content', 'media', 'comments', 'users', 'system', 'integrations' );
$active_groups   = 0;
$completed_steps = (int) ! empty( $settings['bot_token'] ) + (int) ! empty( $settings['chat_ids'] ) + (int) $enabled;

foreach ( $feature_groups as $group ) {
	if ( ! empty( $settings[ $group ]['enabled'] ) ) {
		++$active_groups;
	}
}
?>
<section class="myp-hero <?php echo $connection_ok ? 'myp-hero--ready' : 'myp-hero--attention'; ?>">
	<div class="myp-hero__banner" role="img" aria-label="<?php esc_attr_e( 'Telegram Bot Trigger Notifications banner', 'telegram-trigger' ); ?>"></div>
	<div class="myp-hero__content">
		<div class="myp-hero__message">
			<span class="myp-status-pill">
				<span class="myp-status-dot" aria-hidden="true"></span>
				<?php echo $connection_ok ? esc_html__( 'Ready to send', 'telegram-trigger' ) : esc_html__( 'Setup required', 'telegram-trigger' ); ?>
			</span>
			<h2>
				<?php
				echo $connection_ok
					? esc_html__( 'Your WordPress notifications are connected to Telegram.', 'telegram-trigger' )
					: esc_html__( 'Connect Telegram to start receiving WordPress activity.', 'telegram-trigger' );
				?>
			</h2>
			<p>
				<?php
				echo $connection_ok
					? esc_html__( 'Monitor content, users, comments, and system events without leaving your conversations.', 'telegram-trigger' )
					: esc_html__( 'Add your BotFather token and at least one chat ID. You can test delivery as soon as the connection is saved.', 'telegram-trigger' );
				?>
			</p>
		</div>
		<div class="myp-hero__actions">
			<a class="button button-primary myp-button" href="<?php echo esc_url( admin_url( 'admin.php?page=myp-telegram-settings' ) ); ?>">
				<span class="dashicons <?php echo $configured ? 'dashicons-admin-generic' : 'dashicons-admin-links'; ?>" aria-hidden="true"></span>
				<?php echo $configured ? esc_html__( 'Manage connection', 'telegram-trigger' ) : esc_html__( 'Connect Telegram', 'telegram-trigger' ); ?>
			</a>
			<a class="button myp-button myp-button--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=myp-telegram-triggers' ) ); ?>">
				<span class="dashicons dashicons-filter" aria-hidden="true"></span>
				<?php esc_html_e( 'Configure triggers', 'telegram-trigger' ); ?>
			</a>
		</div>
	</div>
</section>

<div class="myp-stat-grid">
	<div class="myp-stat-card">
		<span class="myp-stat-card__icon myp-stat-card__icon--blue dashicons dashicons-admin-plugins" aria-hidden="true"></span>
		<div>
			<span class="myp-stat-card__value"><?php echo esc_html( $active_groups ); ?>/<?php echo esc_html( count( $feature_groups ) ); ?></span>
			<span class="myp-stat-card__label"><?php esc_html_e( 'Alert groups enabled', 'telegram-trigger' ); ?></span>
		</div>
	</div>
	<div class="myp-stat-card">
		<span class="myp-stat-card__icon myp-stat-card__icon--violet dashicons dashicons-groups" aria-hidden="true"></span>
		<div>
			<span class="myp-stat-card__value"><?php echo esc_html( $recipient_count ); ?></span>
			<span class="myp-stat-card__label"><?php echo 1 === $recipient_count ? esc_html__( 'Telegram recipient', 'telegram-trigger' ) : esc_html__( 'Telegram recipients', 'telegram-trigger' ); ?></span>
		</div>
	</div>
	<div class="myp-stat-card">
		<span class="myp-stat-card__icon myp-stat-card__icon--green dashicons dashicons-shield-alt" aria-hidden="true"></span>
		<div>
			<span class="myp-stat-card__value"><?php echo $enabled ? esc_html__( 'On', 'telegram-trigger' ) : esc_html__( 'Off', 'telegram-trigger' ); ?></span>
			<span class="myp-stat-card__label"><?php esc_html_e( 'Notification delivery', 'telegram-trigger' ); ?></span>
		</div>
	</div>
</div>

<div class="myp-dashboard-grid">
	<section class="myp-card myp-card--setup">
		<div class="myp-card__header">
			<div>
				<div class="myp-card__title"><?php esc_html_e( 'Connection checklist', 'telegram-trigger' ); ?></div>
				<p class="myp-card__desc"><?php esc_html_e( 'Complete these steps before enabling live notifications.', 'telegram-trigger' ); ?></p>
			</div>
			<span class="myp-progress-count"><?php echo esc_html( $completed_steps ); ?>/3</span>
		</div>
		<ul class="myp-checklist">
			<li class="<?php echo ! empty( $settings['bot_token'] ) ? 'is-complete' : ''; ?>">
				<span class="dashicons <?php echo ! empty( $settings['bot_token'] ) ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>" aria-hidden="true"></span>
				<div><strong><?php esc_html_e( 'Bot token', 'telegram-trigger' ); ?></strong><small><?php esc_html_e( 'Authorize requests through your BotFather bot.', 'telegram-trigger' ); ?></small></div>
			</li>
			<li class="<?php echo ! empty( $settings['chat_ids'] ) ? 'is-complete' : ''; ?>">
				<span class="dashicons <?php echo ! empty( $settings['chat_ids'] ) ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>" aria-hidden="true"></span>
				<div><strong><?php esc_html_e( 'Chat recipients', 'telegram-trigger' ); ?></strong><small><?php esc_html_e( 'Choose the users, groups, or channels that receive alerts.', 'telegram-trigger' ); ?></small></div>
			</li>
			<li class="<?php echo $enabled ? 'is-complete' : ''; ?>">
				<span class="dashicons <?php echo $enabled ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>" aria-hidden="true"></span>
				<div><strong><?php esc_html_e( 'Delivery enabled', 'telegram-trigger' ); ?></strong><small><?php esc_html_e( 'Turn on the master notification switch.', 'telegram-trigger' ); ?></small></div>
			</li>
		</ul>
	</section>

	<section class="myp-card myp-card--test">
		<div class="myp-card__title"><?php esc_html_e( 'Test your connection', 'telegram-trigger' ); ?></div>
		<p class="myp-card__desc"><?php esc_html_e( 'Send a test notification to every configured chat ID.', 'telegram-trigger' ); ?></p>
		<div class="myp-test-visual" aria-hidden="true">
			<span class="dashicons dashicons-wordpress"></span>
			<span class="myp-test-line"></span>
			<span class="dashicons dashicons-format-chat"></span>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="myp_telegram_test">
			<?php wp_nonce_field( 'myp_telegram_test' ); ?>
			<p class="myp-submit">
				<button type="submit" class="button button-primary myp-button myp-button--wide" <?php disabled( ! $configured ); ?>>
					<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Send test message', 'telegram-trigger' ); ?>
				</button>
			</p>
			<?php if ( ! $configured ) : ?>
				<p class="myp-form-hint"><?php esc_html_e( 'Save a bot token and chat ID to enable testing.', 'telegram-trigger' ); ?></p>
			<?php endif; ?>
		</form>
	</section>
</div>
</div>
