<?php
// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<h1 class="wps-title">
	<div class="wps-logo-img">
		<img src="<?php echo esc_url( WPS_CHILD_THEME_GENERATOR_URL . 'assets/img/wps-child-theme-generator-logo.png' ); ?>" alt="<?php esc_attr_e( 'Child Theme Generator', 'wps-child-theme-generator' ); ?>" />
	</div>
	<p class="wps_header_text"><?php esc_html_e( 'WPS Child Theme Generator, create your child theme with options.', 'wps-child-theme-generator' ); ?></p>
</h1>
