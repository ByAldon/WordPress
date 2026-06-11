<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$wps_limit_login_white_list_ips = $this->get_option( 'wps_limit_login_whitelist' );
$wps_limit_login_white_list_ips = ( is_array( $wps_limit_login_white_list_ips ) && ! empty( $wps_limit_login_white_list_ips ) ) ? implode( "\n", array_map( 'sanitize_text_field', $wps_limit_login_white_list_ips ) ) : '';
$ip                             = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
?>
<form action="<?php echo esc_url( add_query_arg( 'tab', 'whitelist', $this->get_wps_limit_login_options_page_uri() ) ); ?>" method="post">
	<?php wp_nonce_field( 'wps-limit-login-settings' ); ?>

	<div class="h2"><?php esc_html_e( 'Whitelist', 'wps-limit-login' ); ?></div>
	<p><?php esc_html_e( 'Sets a list of IP addresses that will have no attempt limit and will never be blocked. Only add trusted IP addresses.', 'wps-limit-login' ); ?></p>
	<?php if ( '' !== $ip ) : ?>
		<p><span class="wps-ip"><?php echo esc_html( sprintf( __( 'Add your IP address (%s) to a whitelist.', 'wps-limit-login' ), $ip ) ); ?></span></p>
	<?php endif; ?>
	<p class="description"><?php esc_html_e( 'One IP range or IP address per line.', 'wps-limit-login' ); ?></p>
	<textarea name="wps_limit_login_whitelist_ips" id="wps_limit_login_whitelist_ips" rows="10" cols="50" placeholder="88.88.88.86&#x0a;88.88.88.90&#x0a;88.88.88.*&#x0a;88.88.88.0/24"><?php echo esc_textarea( $wps_limit_login_white_list_ips ); ?></textarea>

	<p class="submit">
		<button type="submit" name="update_options" id="submit" class="button button-primary btn-wps wps-save"><?php esc_html_e( 'Save' ); ?></button>
		<?php if ( '' !== $ip ) : ?>
			<button class="button button-primary btn-wps wps-addip" data-ip="<?php echo esc_attr( $ip ); ?>"><?php echo esc_html( sprintf( __( 'Add my IP: %s', 'wps-limit-login' ), $ip ) ); ?></button>
		<?php endif; ?>
	</p>

	<script>
		jQuery(function ($) {
			$('.wps-addip').on('click', function (event) {
				event.preventDefault();
				$('#wps_limit_login_whitelist_ips').append('\n' + $(this).data('ip'));
			});
		});
	</script>
</form>
