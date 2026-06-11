<?php
/*
Plugin Name: FileOrganizer
Plugin URI: https://wordpress.org/plugins/fileorganizer/
Description: FileOrganizer is a plugin that helps you to manage all files in your WordPress Site.
Version: 1.2.0.1
Update URI: https://byaldon.local/fileorganizer-custom/
Author: Softaculous Team
Author URI: https://fileorganizer.net
Text Domain: fileorganizer
*/

// We need the ABSPATH
if(!defined('ABSPATH')) exit;

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

$_tmp_plugins = get_option('active_plugins', []);

if(!defined('SITEPAD') && in_array('fileorganizer-pro/fileorganizer-pro.php', $_tmp_plugins)){

	// Was introduced in 1.0.9
	$fileorganizer_pro_info = get_option('fileorganizer_pro_version');
	
	if(!empty($fileorganizer_pro_info) && version_compare($fileorganizer_pro_info, '1.0.9', '>=')){
		// Let Fileorganizer load
	
	// Lets check for older versions
	}else{

		if(!function_exists('get_plugin_data')){
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$fileorganizer_pro_info = get_plugin_data(WP_PLUGIN_DIR . '/fileorganizer-pro/fileorganizer-pro.php');
		
		if(!empty($fileorganizer_pro_info) && version_compare($fileorganizer_pro_info['Version'], '1.0.9', '<')){
			return;
		}
	}
}

// If FILEORGANIZER_VERSION exists then the plugin is loaded already !
if(defined('FILEORGANIZER_VERSION')){
	return;
}

define('FILEORGANIZER_FILE', __FILE__);
define('FILEORGANIZER_VERSION', '1.2.0.1');
define('FILEORGANIZER_UPDATE_LOCKED', true);

/**
 * Custom build guard.
 *
 * This prevents WordPress.org or automatic update routines from silently
 * replacing this cleaned copy with the public FileOrganizer package.
 * Manual uploads by an administrator are still possible by design.
 */
function fileorganizer_locked_plugin_file(){
	return plugin_basename(__FILE__);
}

function fileorganizer_block_auto_update($update, $item){
	$plugin_file = fileorganizer_locked_plugin_file();
	$item_plugin = '';

	if(is_object($item) && !empty($item->plugin)){
		$item_plugin = $item->plugin;
	}elseif(is_array($item) && !empty($item['plugin'])){
		$item_plugin = $item['plugin'];
	}

	if($item_plugin === $plugin_file){
		return false;
	}

	return $update;
}
add_filter('auto_update_plugin', 'fileorganizer_block_auto_update', 10, 2);

function fileorganizer_remove_update_offer($transient){
	if(!is_object($transient)){
		return $transient;
	}

	$plugin_file = fileorganizer_locked_plugin_file();

	if(isset($transient->response) && is_array($transient->response) && isset($transient->response[$plugin_file])){
		unset($transient->response[$plugin_file]);
	}

	if(isset($transient->no_update) && is_array($transient->no_update) && isset($transient->no_update[$plugin_file])){
		unset($transient->no_update[$plugin_file]);
	}

	return $transient;
}
add_filter('site_transient_update_plugins', 'fileorganizer_remove_update_offer', 100);
add_filter('pre_set_site_transient_update_plugins', 'fileorganizer_remove_update_offer', 100);

function fileorganizer_block_plugin_information($result, $action, $args){
	if($action === 'plugin_information' && is_object($args) && !empty($args->slug) && $args->slug === 'fileorganizer'){
		return new WP_Error('fileorganizer_update_locked', __('Plugin information is disabled for this custom locked build.', 'fileorganizer'));
	}

	return $result;
}
add_filter('plugins_api', 'fileorganizer_block_plugin_information', 10, 3);

include_once(dirname(__FILE__).'/init.php');
