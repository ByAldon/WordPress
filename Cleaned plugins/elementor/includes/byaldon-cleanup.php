<?php
/**
 * Cleaned-build hardening for Elementor.
 *
 * This file only disables unwanted update/telemetry/marketing behavior for this
 * custom build. It does not unlock, bypass, or modify any paid Elementor Pro
 * functionality or license checks.
 *
 * @package Elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'byaldon_elementor_cleaned_plugin_basename' ) ) {
	function byaldon_elementor_cleaned_plugin_basename(): string {
		return defined( 'ELEMENTOR_PLUGIN_BASE' ) ? ELEMENTOR_PLUGIN_BASE : 'elementor/elementor.php';
	}
}

if ( ! function_exists( 'byaldon_elementor_cleaned_is_elementor_update_item' ) ) {
	function byaldon_elementor_cleaned_is_elementor_update_item( $item ): bool {
		$basename = byaldon_elementor_cleaned_plugin_basename();
		$plugin   = '';
		$slug     = '';

		if ( is_object( $item ) ) {
			$plugin = $item->plugin ?? '';
			$slug   = $item->slug ?? '';
		} elseif ( is_array( $item ) ) {
			$plugin = $item['plugin'] ?? '';
			$slug   = $item['slug'] ?? '';
		}

		return $basename === $plugin || 'elementor' === $slug;
	}
}

if ( ! function_exists( 'byaldon_elementor_cleaned_filter_update_transient' ) ) {
	function byaldon_elementor_cleaned_filter_update_transient( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$basename = byaldon_elementor_cleaned_plugin_basename();

		if ( isset( $transient->response ) && is_array( $transient->response ) ) {
			unset( $transient->response[ $basename ] );
		}

		return $transient;
	}
}

add_filter(
	'auto_update_plugin',
	static function ( $update, $item ) {
		return byaldon_elementor_cleaned_is_elementor_update_item( $item ) ? false : $update;
	},
	999,
	2
);

add_filter( 'site_transient_update_plugins', 'byaldon_elementor_cleaned_filter_update_transient', 999 );
add_filter( 'pre_set_site_transient_update_plugins', 'byaldon_elementor_cleaned_filter_update_transient', 999 );

add_filter(
	'plugins_api',
	static function ( $result, $action, $args ) {
		if ( is_object( $args ) && isset( $args->slug ) && 'elementor' === $args->slug ) {
			return new WP_Error(
				'byaldon_elementor_cleaned_update_locked',
				__( 'Plugin information is disabled for this cleaned, update-locked custom Elementor build.', 'elementor' )
			);
		}

		return $result;
	},
	999,
	3
);

add_action(
	'init',
	static function () {
		// Keep tracking disabled and remove any scheduled tracker job from older installs.
		if ( 'no' !== get_option( 'elementor_allow_tracking', 'no' ) ) {
			update_option( 'elementor_allow_tracking', 'no' );
		}

		wp_clear_scheduled_hook( 'elementor/tracker/send_event' );
	},
	20
);
