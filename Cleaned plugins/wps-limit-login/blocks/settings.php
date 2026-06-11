<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$wps_limit_lockout_notify = explode( ',', (string) $this->get_option( 'wps_limit_lockout_notify' ) );
$email_checked            = in_array( 'email', $wps_limit_lockout_notify, true ) ? ' checked ' : '';
$reset_url                = add_query_arg(
	'action',
	'reinitialize',
	wp_nonce_url( $this->get_wps_limit_login_options_page_uri(), 'reinitialize', 'nonce' )
);
?>
<div class="h2"><?php esc_html_e( 'Configuration', 'wps-limit-login' ); ?></div>
<form action="<?php echo esc_url( $this->get_wps_limit_login_options_page_uri() ); ?>" method="post">
	<?php wp_nonce_field( 'wps-limit-login-settings' ); ?>
	<?php if ( is_network_admin() ) : ?>
		<p>
			<input type="checkbox" name="allow_local_options" <?php checked( (bool) $this->get_option( 'wps_limit_login_allow_local_options' ) ); ?> value="1" />
			<?php esc_html_e( 'Let network sites use their own settings', 'wps-limit-login' ); ?>
		</p>
		<p class="description"><?php esc_html_e( 'If disabled, the global settings will be forcibly applied to the entire network.', 'wps-limit-login' ); ?></p>
	<?php elseif ( $this->network_mode ) : ?>
		<p>
			<input type="checkbox" name="use_global_options" <?php checked( ! $this->get_option( 'wps_limit_login_use_local_options' ) ); ?> value="1" class="use_global_options" />
			<?php esc_html_e( 'Use global settings', 'wps-limit-login' ); ?>
		</p>
		<script>
			jQuery(function ($) {
				var first = true;
				$('.use_global_options').change(function () {
					var form = $(this).siblings('table');
					form.stop();

					if (this.checked)
						first ? form.hide() : form.fadeOut();
					else
						first ? form.show() : form.fadeIn();

					first = false;
				}).change();
			});
		</script>
	<?php endif; ?>

	<p>
		<input type="number" min="1" max="20" value="<?php echo esc_attr( absint( $this->get_option( 'wps_limit_login_allowed_retries' ) ) ); ?>" name="allowed_retries" />
		<?php esc_html_e( 'allowed retries', 'wps-limit-login' ); ?> <?php esc_html_e( 'for a period of', 'wps-limit-login' ); ?>
		<input type="number" min="1" max="1440" value="<?php echo esc_attr( absint( $this->get_option( 'wps_limit_login_lockout_duration' ) / 60 ) ); ?>" name="lockout_duration" />
		<?php esc_html_e( 'minutes', 'wps-limit-login' ); ?>
	</p>
	<p>
		<input type="number" min="1" max="168" value="<?php echo esc_attr( absint( $this->get_option( 'wps_limit_login_valid_duration' ) / 3600 ) ); ?>" name="valid_duration" />
		<?php esc_html_e( 'hours until retries are reset', 'wps-limit-login' ); ?>
	</p>
	<p>
		<input type="number" min="1" max="20" value="<?php echo esc_attr( absint( $this->get_option( 'wps_limit_login_allowed_lockouts' ) ) ); ?>" name="allowed_lockouts" />
		<?php esc_html_e( 'lockouts increase lockout time to', 'wps-limit-login' ); ?>
		<input type="number" min="1" max="168" value="<?php echo esc_attr( absint( $this->get_option( 'wps_limit_login_long_duration' ) / 3600 ) ); ?>" name="long_duration" />
		<?php esc_html_e( 'hours', 'wps-limit-login' ); ?>
	</p>
	<p>
		<input type="checkbox" name="lockout_notify_email" id="lockout_notify_email" <?php echo esc_attr( $email_checked ); ?> value="email" />
		<label for="lockout_notify_email"><?php esc_html_e( 'Email to admin after', 'wps-limit-login' ); ?></label>
		<input type="number" min="1" max="20" value="<?php echo esc_attr( absint( $this->get_option( 'wps_limit_login_notify_email_after' ) ) ); ?>" name="notify_email_after" />
		<?php esc_html_e( 'lockouts', 'wps-limit-login' ); ?>
	</p>
	<p class="submit">
		<button type="submit" name="update_options" id="submit" class="button button-primary btn-wps wps-save"><?php esc_html_e( 'Save' ); ?></button>
		<a href="<?php echo esc_url( $reset_url ); ?>" class="button btn-wps wps-reinit"><?php esc_html_e( 'Reset the original settings', 'wps-limit-login' ); ?></a>
	</p>
</form>
