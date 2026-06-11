<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package   WPS Hide Login
 * @license   GPL-2.0+
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

if ( is_multisite() ) {

	$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
	delete_site_option( 'whl_page' );
	delete_site_option( 'whl_redirect_admin' );

	flush_rewrite_rules();

	if ( $blogs ) {

		foreach ( $blogs as $blog ) {
			switch_to_blog( absint( $blog['blog_id'] ) );
			delete_option( 'whl_page' );
			delete_option( 'whl_redirect_admin' );

			flush_rewrite_rules();
			restore_current_blog();
		}
	}

} else {
	delete_option( 'whl_page' );
	delete_option( 'whl_redirect_admin' );

	flush_rewrite_rules();

}