<?php
namespace WPS\WPS_Child_Theme_Generator;

// Do not load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Promo/API helper kept as a safe no-op for backward compatibility.
 * The cleaned build does not perform remote promotional API calls.
 */
class Pub {
	public static function wps_ip_check_return_pf() { return ''; }
	public static function is_plugin_installed( $plugin ) { return false; }
	public static function get_api_result() { return ''; }
	public static function get_json_array( $response ) { return array(); }
}
