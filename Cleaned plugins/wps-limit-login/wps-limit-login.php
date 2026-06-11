<?php
/*
Plugin Name: WPS Limit Login
Description: Limit connection attempts by IP address
Donate link: https://www.paypal.me/donateWPServeur
Author: WPServeur, NicolasKulka, wpformation
Author URI: https://wpserveur.net
Version: 1.5.9.3
Update URI: byaldon-local/wps-limit-login
Requires at least: 4.2
Tested up to: 6.8
Domain Path: languages
Text Domain: wps-limit-login
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'WPS_LIMIT_LOGIN_VERSION', '1.5.9.3' );
define( 'WPS_LIMIT_LOGIN_FOLDER', 'wps-limit-login' );
define( 'WPS_LIMIT_LOGIN_BASENAME', plugin_basename( __FILE__ ) );
if ( ! defined( 'WPS_PUB_API_URL' ) ) {
	define( 'WPS_PUB_API_URL', '' );
}

define( 'WPS_LIMIT_LOGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS_LIMIT_LOGIN_DIR', plugin_dir_path( __FILE__ ) );

define( 'WPS_LIMIT_LOGIN_REMOTE_ADDR', 'REMOTE_ADDR' );

/**
 * ByAldon custom build: prevent this cleaned build from being silently replaced
 * by WordPress.org or by automatic plugin updates.
 */
if ( ! function_exists( 'byaldon_wps_limit_login_lock_updates' ) ) {
	function byaldon_wps_limit_login_lock_updates( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		if ( isset( $transient->response[ WPS_LIMIT_LOGIN_BASENAME ] ) ) {
			unset( $transient->response[ WPS_LIMIT_LOGIN_BASENAME ] );
		}

		if ( isset( $transient->no_update[ WPS_LIMIT_LOGIN_BASENAME ] ) ) {
			unset( $transient->no_update[ WPS_LIMIT_LOGIN_BASENAME ] );
		}

		return $transient;
	}

	function byaldon_wps_limit_login_disable_auto_update( $update, $item ) {
		if ( isset( $item->plugin ) && WPS_LIMIT_LOGIN_BASENAME === $item->plugin ) {
			return false;
		}

		return $update;
	}

	function byaldon_wps_limit_login_block_plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' === $action && isset( $args->slug ) && 'wps-limit-login' === $args->slug ) {
			return new WP_Error( 'byaldon_update_locked', __( 'Plugin information is disabled for this cleaned custom build.', 'wps-limit-login' ) );
		}

		return $result;
	}

	add_filter( 'site_transient_update_plugins', 'byaldon_wps_limit_login_lock_updates', 9999 );
	add_filter( 'transient_update_plugins', 'byaldon_wps_limit_login_lock_updates', 9999 );
	add_filter( 'auto_update_plugin', 'byaldon_wps_limit_login_disable_auto_update', 9999, 2 );
	add_filter( 'plugins_api', 'byaldon_wps_limit_login_block_plugin_info', 9999, 3 );
}


$wps_limit_login_my_error_shown       = false;
$wps_limit_login_just_lockedout       = false;
$wps_limit_login_notempty_credentials = false;

require_once WPS_LIMIT_LOGIN_DIR . 'autoload.php';

// register_activation_hook( __FILE__, array( '\WPS\WPS_Limit_Login\Plugin', 'activate' ) );

if ( ! function_exists( 'plugins_loaded_wps_limit_login_plugin' ) ) {
	add_action( 'plugins_loaded', 'plugins_loaded_wps_limit_login_plugin' );
	function plugins_loaded_wps_limit_login_plugin() {
		\WPS\WPS_Limit_Login\Plugin::get_instance();

		load_plugin_textdomain( 'wps-limit-login', false, basename( rtrim( dirname( __FILE__ ), '/' ) ) . '/languages' );
	}
}