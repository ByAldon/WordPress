<?php
/*
Plugin Name: Premium Addons for Elementor
Description: Premium Addons for Elementor plugin includes widgets and addons like Blog Post Grid, Megamenu, Post Carousel, Advanced Slider, Modal Popup, Google Maps, SVG Draw, Lottie Animations, Countdown, Testimonials.
Plugin URI: https://premiumaddons.com
Version: 4.11.81.1
Update URI: https://byaldon.local/update-locked/premium-addons-for-elementor
Requires at least: 6.6
Requires PHP: 7.4
Elementor tested up to: 4.1
Elementor Pro tested up to: 4.1
Author: Leap13
Author URI: https://leap13.com/
Text Domain: premium-addons-for-elementor
Domain Path: /languages
License: GNU General Public License v3.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No access of directly access.
}


// Define Constants.
define( 'PREMIUM_ADDONS_VERSION', '4.11.81.1' );
define( 'PREMIUM_ADDONS_URL', plugins_url( '/', __FILE__ ) );
define( 'PREMIUM_ADDONS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PREMIUM_ASSETS_PATH', set_url_scheme( wp_upload_dir()['basedir'] . '/premium-addons-elementor' ) );
define( 'PREMIUM_ASSETS_URL', set_url_scheme( wp_upload_dir()['baseurl'] . '/premium-addons-elementor' ) );
define( 'PREMIUM_ADDONS_FILE', __FILE__ );
define( 'PREMIUM_ADDONS_BASENAME', plugin_basename( PREMIUM_ADDONS_FILE ) );
define( 'PREMIUM_ADDONS_STABLE_VERSION', '4.11.81.1' );


/**
 * ByAldon custom hardening: lock this cleaned build against unintended updates.
 *
 * This keeps WordPress.org/external update offers from silently replacing the
 * reviewed custom package. A site administrator can still replace the plugin
 * manually by uploading another ZIP or changing the files over FTP/SFTP.
 */
define( 'PREMIUM_ADDONS_UPDATE_LOCKED', true );
define( 'PREMIUM_ADDONS_ORIGINAL_VERSION', '4.11.81' );

if ( ! function_exists( 'premium_addons_byaldon_remove_update_offer' ) ) {
	/**
	 * Remove update data for this custom plugin build from update transients.
	 *
	 * @param object|mixed $transient WordPress update transient.
	 * @return object|mixed
	 */
	function premium_addons_byaldon_remove_update_offer( $transient ) {
		if ( is_object( $transient ) ) {
			if ( isset( $transient->response ) && is_array( $transient->response ) ) {
				unset( $transient->response[ PREMIUM_ADDONS_BASENAME ] );
			}

			if ( isset( $transient->no_update ) && is_array( $transient->no_update ) ) {
				unset( $transient->no_update[ PREMIUM_ADDONS_BASENAME ] );
			}
		}

		return $transient;
	}
}

if ( ! function_exists( 'premium_addons_byaldon_disable_auto_update' ) ) {
	/**
	 * Disable automatic updates for this custom plugin build only.
	 *
	 * @param bool|null $update Whether to update.
	 * @param object    $item   Plugin update item.
	 * @return bool|null
	 */
	function premium_addons_byaldon_disable_auto_update( $update, $item ) {
		if ( is_object( $item ) && isset( $item->plugin ) && PREMIUM_ADDONS_BASENAME === $item->plugin ) {
			return false;
		}

		return $update;
	}
}

if ( ! function_exists( 'premium_addons_byaldon_block_plugin_info' ) ) {
	/**
	 * Prevent the WordPress.org plugin-information modal from offering this slug.
	 *
	 * @param mixed  $result API result.
	 * @param string $action API action.
	 * @param object $args   API arguments.
	 * @return mixed
	 */
	function premium_addons_byaldon_block_plugin_info( $result, $action, $args ) {
		if ( isset( $args->slug ) && 'premium-addons-for-elementor' === $args->slug ) {
			return new WP_Error(
				'premium_addons_update_locked',
				__( 'This custom cleaned build is update-locked. Replace it manually only after reviewing the new package.', 'premium-addons-for-elementor' )
			);
		}

		return $result;
	}
}

add_filter( 'site_transient_update_plugins', 'premium_addons_byaldon_remove_update_offer', 999 );
add_filter( 'pre_set_site_transient_update_plugins', 'premium_addons_byaldon_remove_update_offer', 999 );
add_filter( 'auto_update_plugin', 'premium_addons_byaldon_disable_auto_update', 999, 2 );
add_filter( 'plugins_api', 'premium_addons_byaldon_block_plugin_info', 999, 3 );

/*
 * Load autoloader
 */
require_once PREMIUM_ADDONS_PATH . 'autoload.php';

/*
 * Load plugin core file
 */
require_once PREMIUM_ADDONS_PATH . 'includes/class-pa-core.php';
