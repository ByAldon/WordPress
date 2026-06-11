<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$wps_limit_login_black_list_ips = $this->get_option( 'wps_limit_login_blacklist' );
$wps_limit_login_black_list_ips = ( is_array( $wps_limit_login_black_list_ips ) && ! empty( $wps_limit_login_black_list_ips ) ) ? implode( "\n", array_map( 'sanitize_text_field', $wps_limit_login_black_list_ips ) ) : '';
?>
<form action="<?php echo esc_url( add_query_arg( 'tab', 'blacklist', $this->get_wps_limit_login_options_page_uri() ) ); ?>" method="post">
	<?php wp_nonce_field( 'wps-limit-login-settings' ); ?>

	<div class="h2"><?php esc_html_e( 'Blacklist', 'wps-limit-login' ); ?></div>
	<p><?php esc_html_e( 'Defines a list of IP addresses for which you want to completely block access to the login page.', 'wps-limit-login' ); ?></p>
	<p class="description"><?php esc_html_e( 'One IP range or IP address per line.', 'wps-limit-login' ); ?></p>
	<textarea name="wps_limit_login_blacklist_ips" rows="10" cols="50" placeholder="88.88.88.86&#x0a;88.88.88.90&#x0a;88.88.88.*&#x0a;88.88.88.0/24"><?php echo esc_textarea( $wps_limit_login_black_list_ips ); ?></textarea>

	<p class="submit">
		<button type="submit" name="update_options" id="submit" class="button button-primary btn-wps wps-save"><?php esc_html_e( 'Save' ); ?></button>
	</p>
</form>
