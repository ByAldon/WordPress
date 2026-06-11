<?php
/*
Plugin Name: WPS Child Theme Generator
Description:  WPS Child Theme Generator.
Donate link: https://www.paypal.me/donateWPServeur
Version: 1.5.5.4
Tested up to: 6.8
Update URI: https://byaldon.local/wps-child-theme-generator-cleaned/
Author: WPServeur, Benoti, NicolasKulka
Author URI: https://wpserveur.net
License: GPL2
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Plugin constants
define( 'WPS_CHILD_THEME_GENERATOR_VERSION', '1.5.5.4' );
define( 'WPS_CHILD_THEME_GENERATOR_FOLDER', 'wps-child-theme-generator' );
define( 'WPS_CHILD_THEME_GENERATOR_BASENAME', plugin_basename( __FILE__ ) );
if ( ! defined( 'WPS_PUB_API_URL' ) ) {
	define( 'WPS_PUB_API_URL', '' );
}

if ( ! defined( 'WPS_CHILD_THEME_GENERATOR_ALLOW_UPDATES' ) ) {
	define( 'WPS_CHILD_THEME_GENERATOR_ALLOW_UPDATES', false );
}

define( 'WPS_CHILD_THEME_GENERATOR_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS_CHILD_THEME_GENERATOR_DIR', plugin_dir_path( __FILE__ ) );

require_once WPS_CHILD_THEME_GENERATOR_DIR . 'autoload.php';

/**
 * Lock this cleaned/custom build against automatic WordPress updates.
 *
 * The original plugin slug exists publicly, so a normal WordPress update can
 * overwrite this hardened version with upstream code. Updates are blocked by
 * default. A site owner can still replace the plugin manually by uploading a
 * ZIP, or a developer can deliberately opt in by defining
 * WPS_CHILD_THEME_GENERATOR_ALLOW_UPDATES as true before this plugin loads.
 */
function wps_child_theme_generator_updates_are_locked() {
	return ! ( defined( 'WPS_CHILD_THEME_GENERATOR_ALLOW_UPDATES' ) && true === WPS_CHILD_THEME_GENERATOR_ALLOW_UPDATES );
}

function wps_child_theme_generator_disable_automatic_updates( $update, $item ) {
	if ( ! wps_child_theme_generator_updates_are_locked() ) {
		return $update;
	}

	$plugin_file = '';
	if ( is_object( $item ) && isset( $item->plugin ) ) {
		$plugin_file = (string) $item->plugin;
	} elseif ( is_array( $item ) && isset( $item['plugin'] ) ) {
		$plugin_file = (string) $item['plugin'];
	}

	if ( WPS_CHILD_THEME_GENERATOR_BASENAME === $plugin_file ) {
		return false;
	}

	return $update;
}
add_filter( 'auto_update_plugin', 'wps_child_theme_generator_disable_automatic_updates', 10, 2 );

function wps_child_theme_generator_remove_update_offer( $transient ) {
	if ( ! wps_child_theme_generator_updates_are_locked() || ! is_object( $transient ) ) {
		return $transient;
	}

	if ( isset( $transient->response ) && is_array( $transient->response ) ) {
		unset( $transient->response[ WPS_CHILD_THEME_GENERATOR_BASENAME ] );
	}

	if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
		$transient->no_update = array();
	}

	$transient->no_update[ WPS_CHILD_THEME_GENERATOR_BASENAME ] = (object) array(
		'id'            => WPS_CHILD_THEME_GENERATOR_BASENAME,
		'slug'          => WPS_CHILD_THEME_GENERATOR_FOLDER,
		'plugin'        => WPS_CHILD_THEME_GENERATOR_BASENAME,
		'new_version'   => WPS_CHILD_THEME_GENERATOR_VERSION,
		'url'           => '',
		'package'       => '',
		'icons'         => array(),
		'banners'       => array(),
		'banners_rtl'   => array(),
		'requires'      => '5.8',
		'tested'        => '6.8',
		'requires_php' => '7.4',
	);

	return $transient;
}
add_filter( 'site_transient_update_plugins', 'wps_child_theme_generator_remove_update_offer' );
add_filter( 'pre_set_site_transient_update_plugins', 'wps_child_theme_generator_remove_update_offer' );

function wps_child_theme_generator_block_plugin_information_updates( $result, $action, $args ) {
	if ( ! wps_child_theme_generator_updates_are_locked() || 'plugin_information' !== $action || ! is_object( $args ) ) {
		return $result;
	}

	if ( isset( $args->slug ) && WPS_CHILD_THEME_GENERATOR_FOLDER === $args->slug ) {
		return false;
	}

	return $result;
}
add_filter( 'plugins_api', 'wps_child_theme_generator_block_plugin_information_updates', 10, 3 );

add_action( 'plugins_loaded', 'plugins_loaded_wps_child_theme_generator_plugin' );
function plugins_loaded_wps_child_theme_generator_plugin() {
	\WPS\WPS_Child_Theme_Generator\Plugin::get_instance();

	load_plugin_textdomain( 'wps-child-theme-generator', false, basename( rtrim( dirname( __FILE__ ), '/' ) ) . '/languages' );
}