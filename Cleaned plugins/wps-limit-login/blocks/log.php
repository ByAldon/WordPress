<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$lockouts                  = (array) $this->get_option( 'wps_limit_login_lockouts' );
$lockouts_now              = count( $lockouts );
$wps_limit_lockouts_total  = absint( $this->get_option( 'wps_limit_lockouts_total' ) );
$log                       = $this->get_option( 'wps_limit_login_logged' );
$log                       = \WPS\WPS_Limit_Login\Plugin::sorted_log_by_date( $log );
$options_log_url           = add_query_arg( 'tab', 'log', $this->get_wps_limit_login_options_page_uri() );
?>
<form action="<?php echo esc_url( $options_log_url ); ?>" method="post">
	<?php wp_nonce_field( 'wps-limit-login-settings' ); ?>
	<?php if ( $wps_limit_lockouts_total > 0 ) : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
					_n( '%d lockout since last reset', '%d lockouts since last reset', $wps_limit_lockouts_total, 'wps-limit-login' ),
					$wps_limit_lockouts_total
				)
			);
			?>
		</p>
	<?php else : ?>
		<p><?php esc_html_e( 'No lockouts yet', 'wps-limit-login' ); ?></p>
	<?php endif; ?>

	<?php if ( $lockouts_now > 0 ) : ?>
		<p>
			<?php
			echo esc_html(
				sprintf(
					_n( '%d IP is currently blocked from trying to log in', '%d IPs are currently blocked from trying to log in', $lockouts_now, 'wps-limit-login' ),
					$lockouts_now
				)
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( $wps_limit_lockouts_total > 0 ) : ?>
		<button class="button btn-wps wps-reinit" name="reset_total" type="submit"><?php echo esc_html( sprintf( __( 'Reset Counter (%d)', 'wps-limit-login' ), $wps_limit_lockouts_total ) ); ?></button>
	<?php endif; ?>

	<?php if ( $lockouts_now > 0 ) : ?>
		<button class="button btn-wps wps-reset-lockouts" name="reset_current" type="submit"><?php echo esc_html( sprintf( __( 'Restore Lockouts (%d)', 'wps-limit-login' ), $lockouts_now ) ); ?></button>
	<?php endif; ?>
</form>

<div class="wps-credit">
	<div class="h2" id="wps_lockout_log"><?php esc_html_e( 'Lockout log', 'wps-limit-login' ); ?></div>
	<?php if ( is_array( $log ) && ! empty( $log ) ) : ?>
		<p><?php esc_html_e( 'You can unlock an IP address individually by clicking the Unlock button.', 'wps-limit-login' ); ?></p>
	<?php else : ?>
		<p><?php esc_html_e( 'No lockouts yet', 'wps-limit-login' ); ?></p>
	<?php endif; ?>
</div>

<?php if ( is_array( $log ) && ! empty( $log ) ) : ?>
	<form action="<?php echo esc_url( $options_log_url ); ?>" method="post">
		<?php wp_nonce_field( 'wps-limit-login-settings' ); ?>
		<input type="hidden" value="true" name="clear_log" />
		<p class="submit">
			<button class="button btn-wps wps-clear" name="submit" type="submit"><?php esc_html_e( 'Clear Log', 'wps-limit-login' ); ?></button>
		</p>
	</form>

	<div class="wps-limit-login-log">
		<table class="form-table">
			<tr class="hide-mobile">
				<th scope="col"><?php esc_html_e( 'Date', 'wps-limit-login' ); ?></th>
				<th scope="col"><?php echo esc_html_x( 'IP', 'Internet address', 'wps-limit-login' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Users', 'wps-limit-login' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Gateway', 'wps-limit-login' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Action', 'wps-limit-login' ); ?></th>
			</tr>

			<?php foreach ( $log as $date => $user_info ) : ?>
				<?php
				$ip       = isset( $user_info['ip'] ) ? sanitize_text_field( $user_info['ip'] ) : '';
				$username = isset( $user_info['username'] ) ? sanitize_user( $user_info['username'], true ) : '';
				$counter  = isset( $user_info['counter'] ) ? absint( $user_info['counter'] ) : 0;
				$gateway  = isset( $user_info['gateway'] ) ? sanitize_text_field( $user_info['gateway'] ) : '-';
				?>
				<tr>
					<td class="limit-login-date"><span class="display-mobile"><?php esc_html_e( 'Date', 'wps-limit-login' ); ?>: </span><?php echo esc_html( date_i18n( 'F d, Y H:i', absint( $date ) ) ); ?></td>
					<td class="limit-login-ip"><span class="display-mobile"><?php echo esc_html_x( 'IP', 'Internet address', 'wps-limit-login' ); ?>: </span><?php echo esc_html( $ip ); ?></td>
					<td class="limit-login-max"><span class="display-mobile"><?php esc_html_e( 'Users', 'wps-limit-login' ); ?>: </span><?php echo esc_html( sprintf( _n( '%1$s (%2$d lockout)', '%1$s (%2$d lockouts)', $counter, 'wps-limit-login' ), $username, $counter ) ); ?></td>
					<td class="limit-login-gateway"><span class="display-mobile"><?php esc_html_e( 'Gateway', 'wps-limit-login' ); ?>: </span><?php echo esc_html( $gateway ); ?></td>
					<?php if ( ! empty( $lockouts[ $ip ] ) && $lockouts[ $ip ] > time() ) : ?>
						<td class="wps_unlock"><a href="#" class="button wps-limit-login-unlock" data-ip="<?php echo esc_attr( $ip ); ?>" data-username="<?php echo esc_attr( $username ); ?>"><?php esc_html_e( 'Unlock', 'wps-limit-login' ); ?></a></td>
					<?php else : ?>
						<td class="wps_unlocked"><span><?php esc_html_e( 'Unlocked', 'wps-limit-login' ); ?></span></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>
	<script>
		jQuery(function ($) {
			$('.wps-limit-login-log .wps-limit-login-unlock').click(function () {
				var btn = $(this);

				if (btn.hasClass('disabled'))
					return false;
				btn.addClass('disabled');

				$.post(ajaxurl, {
					action: 'wps-limit-login-unlock',
					nonce: '<?php echo esc_js( wp_create_nonce( 'wps-limit-login-unlock' ) ); ?>',
					ip: btn.data('ip'),
					username: btn.data('username')
				})
				.done(function (data) {
					if (data === true)
						btn.fadeOut(function () {
							$(this).parent().removeClass('wps_unlock').addClass('wps_unlocked');
							$(this).parent().html('<?php echo esc_js( '<span>' . __( 'Unlocked', 'wps-limit-login' ) . '</span>' ); ?>');
						});
					else
						fail();
				}).fail(fail);

				function fail() {
					alert('<?php echo esc_js( __( 'Connection error', 'wps-limit-login' ) ); ?>');
					btn.removeClass('disabled');
				}

				return false;
			});
		});
	</script>
<?php endif; ?>
