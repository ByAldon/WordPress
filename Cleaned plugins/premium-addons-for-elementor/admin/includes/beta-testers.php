<?php
/**
 * PA Beta Tester.
 */

namespace PremiumAddons\Admin\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Beta_Testers.
 */
class Beta_Testers {

	/**
	 * Class object
	 *
	 * @var instance
	 */
	private static $instance = null;

	/**
	 * Transient key
	 *
	 * @var transient_key
	 */
	private $transient_key;

	/**
	 * Class Constructor
	 */
	public function __construct() {

		$settings = Admin_Helper::get_integrations_settings();

		$is_beta_tester = isset( $settings['is-beta-tester'] ) ? $settings['is-beta-tester'] : 0;

		if ( ! $is_beta_tester ) {
			return;
		}

		$this->transient_key = md5( 'premium_addons_beta_response_key' );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'compare_version' ) );
	}

	/**
	 * Get beta version
	 *
	 * Checks if the version in trunk is beta
	 *
	 * @since 2.1.3
	 * @access public
	 */
	private function get_beta_version() {

		// ByAldon custom hardening: beta update checks are disabled in this update-locked build.
		return 'false';
	}


	/**
	 * Get version
	 *
	 * Checks if the version in trunk is beta
	 *
	 * @since 2.1.3
	 * @access public
	 *
	 * @param object $transient Plugin updates data.
	 *
	 * @return object Plugin updates data.
	 */
	public function compare_version( $transient ) {

		// ByAldon custom hardening: never inject beta update packages for this custom build.
		return $transient;
	}


	/**
	 * Creates and returns an instance of the class
	 *
	 * @since  2.6.8
	 * @access public
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self();

		}
		return self::$instance;
	}
}
