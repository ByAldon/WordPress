<?php
/*
Plugin Name: WPS Hide Login
Description: Protect your website by changing the login URL and preventing access to wp-login.php page and wp-admin directory while not logged-in
Donate link: https://www.paypal.me/donateKulkaNicolas
Author: WPServeur, NicolasKulka, wpformation
Author URI: https://wpserveur.net
Version: 1.9.18.1
Update URI: https://byaldon.local/wps-hide-login/
Requires at least: 4.1
Tested up to: 6.9
Requires PHP: 7.0
Domain Path: languages
Text Domain: wps-hide-login
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'WPS_HIDE_LOGIN_VERSION', '1.9.18.1' );
define( 'WPS_HIDE_LOGIN_FOLDER', 'wps-hide-login' );

define( 'WPS_HIDE_LOGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS_HIDE_LOGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPS_HIDE_LOGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once WPS_HIDE_LOGIN_DIR . 'autoload.php';

register_activation_hook( __FILE__, array( '\WPS\WPS_Hide_Login\Plugin', 'activate' ) );

add_action( 'plugins_loaded', 'plugins_loaded_wps_hide_login_plugin' );
function plugins_loaded_wps_hide_login_plugin() {
	\WPS\WPS_Hide_Login\Plugin::get_instance();

	load_plugin_textdomain( 'wps-hide-login', false, dirname( WPS_HIDE_LOGIN_BASENAME ) . '/languages' );
}

/**
 * Lock this custom build against silent replacement by WordPress.org updates.
 */
if ( ! function_exists( 'wps_hide_login_custom_update_lock' ) ) {
	function wps_hide_login_custom_update_lock( $transient ) {
		if ( is_object( $transient ) && isset( $transient->response ) && is_array( $transient->response ) ) {
			unset( $transient->response[ WPS_HIDE_LOGIN_BASENAME ] );
		}

		if ( is_object( $transient ) && isset( $transient->no_update ) && is_array( $transient->no_update ) ) {
			unset( $transient->no_update[ WPS_HIDE_LOGIN_BASENAME ] );
		}

		return $transient;
	}
}
add_filter( 'site_transient_update_plugins', 'wps_hide_login_custom_update_lock', 999 );
add_filter( 'pre_set_site_transient_update_plugins', 'wps_hide_login_custom_update_lock', 999 );

if ( ! function_exists( 'wps_hide_login_custom_disable_auto_update' ) ) {
	function wps_hide_login_custom_disable_auto_update( $update, $item ) {
		if ( isset( $item->plugin ) && WPS_HIDE_LOGIN_BASENAME === $item->plugin ) {
			return false;
		}

		return $update;
	}
}
add_filter( 'auto_update_plugin', 'wps_hide_login_custom_disable_auto_update', 999, 2 );

if ( ! function_exists( 'wps_hide_login_custom_block_plugin_information' ) ) {
	function wps_hide_login_custom_block_plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' === $action && isset( $args->slug ) && 'wps-hide-login' === $args->slug ) {
			return new WP_Error(
				'wps_hide_login_update_locked',
				__( 'This custom build of WPS Hide Login is update-locked to prevent silent replacement.', 'wps-hide-login' )
			);
		}

		return $result;
	}
}
add_filter( 'plugins_api', 'wps_hide_login_custom_block_plugin_information', 999, 3 );
