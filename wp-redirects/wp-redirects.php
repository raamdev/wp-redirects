<?php
/*
Version: 140912
Text Domain: wp-redirects
Plugin Name: WP Redirects

Author: WebSharks, Inc.
Author URI: http://www.websharks-inc.com

Plugin URI: http://www.websharks-inc.com/product/wp-redirects/
Description: Create Redirects! This plugin adds a new Post Type. Redirect from anywhere — to anywhere. A very lightweight plugin!
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

if(version_compare(PHP_VERSION, '5.3', '<'))
{
	function wp_redirects_php53_dashboard_notice()
	{
		echo __('<div class="error"><p>Plugin NOT active. This version of WP Redirects requires PHP v5.3+.</p></div>', 'wp-redirects');
	}

	add_action('all_admin_notices', 'wp_redirects_php53_dashboard_notice');
}
else require_once dirname(__FILE__).'/wp-redirects.inc.php';