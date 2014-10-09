<?php
/*
Version: 141009
Text Domain: wp-redirects
Plugin Name: WP Redirects

Author: WebSharks, Inc.
Author URI: http://www.websharks-inc.com

Plugin URI: http://www.websharks-inc.com/product/wp-redirects/
Description: Create Redirects! This plugin adds a new Post Type. Redirect from anywhere — to anywhere. A very lightweight plugin!
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

if(require(dirname(__FILE__).'/includes/wp-php53.php')) // TRUE if running PHP v5.3+.
	require_once dirname(__FILE__).'/wp-redirects.inc.php';
else wp_php53_notice('WP Redirects');