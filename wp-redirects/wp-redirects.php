<?php
/*
Version: 130206
Text Domain: wp-redirects
Plugin Name: WP Redirects

Author URI: http://www.s2member.com
Author: s2Member® / WebSharks, Inc.

Plugin URI: http://www.s2member.com/kb/wp-redirects-plugin
Description: Create Redirects! This plugin adds a new Post Type. Redirect from anywhere — to anywhere. A very lightweight plugin!
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

add_action('init', 'wp_redirects::init', 1);
register_activation_hook(__FILE__, 'wp_redirects::activate');
register_deactivation_hook(__FILE__, 'wp_redirects::deactivate');

class wp_redirects
{
	public static function init()
		{
			wp_redirects::register();
			wp_redirects::create_taxonomy(); // @TODO Review this.

			wp_redirects::redirect_uri_patterns();
			add_action('wp', 'wp_redirects::redirect_redirects', 1);

			add_action('add_meta_boxes_'.($post_type = 'redirect'), 'wp_redirects::meta_boxes');
			add_action('save_post', 'wp_redirects::meta_boxes_save');

			add_filter('ws_plugin__s2member_add_meta_boxes_excluded_types', 'wp_redirects::s2');
		}

	public static function register()
		{
			$args           = array
			(
				'public'          => TRUE,
				'map_meta_cap'    => TRUE,
				'capability_type' => array('redirect', 'redirects'),
				'rewrite'         => array('slug' => 'r', 'with_front' => FALSE),
				'supports'        => array('title', 'author', 'revisions')
			);
			$args['labels'] = array
			(
				'name'               => __('Redirects', 'wp-redirects'),
				'singular_name'      => __('Redirect', 'wp-redirects'),
				'add_new'            => __('Add Redirect', 'wp-redirects'),
				'add_new_item'       => __('Add New Redirect', 'wp-redirects'),
				'edit_item'          => __('Edit Redirect', 'wp-redirects'),
				'new_item'           => __('New Redirect', 'wp-redirects'),
				'all_items'          => __('All Redirects', 'wp-redirects'),
				'view_item'          => __('View Redirect', 'wp-redirects'),
				'search_items'       => __('Search Redirects', 'wp-redirects'),
				'not_found'          => __('No Redirect found', 'wp-redirects'),
				'not_found_in_trash' => __('No Redirects found in Trash', 'wp-redirects')
			);
			register_post_type('redirect', $args);
			#print_r($GLOBALS['wp_post_types']['redirect']);
		}

	public static function create_taxonomy()
		{
			$labels = array
			(
				'name'              => __('Categories', 'wp-redirects'),
				'singular_name'     => __('Category', 'wp-redirects'),
				'search_items'      => __('Search Categories', 'wp-redirects'),
				'all_items'         => __('All Categories', 'wp-redirects'),
				'parent_item'       => __('Parent Category', 'wp-redirects'),
				'parent_item_colon' => __('Parent Category:', 'wp-redirects'),
				'edit_item'         => __('Edit Category', 'wp-redirects'),
				'update_item'       => __('Update Category', 'wp-redirects'),
				'add_new_item'      => __('Add New Category', 'wp-redirects'),
				'new_item_name'     => __('New Category Name', 'wp-redirects'),
				'menu_name'         => __('Categories', 'wp-redirects'),
			);
			$args   = array
			(
				'hierarchical'      => TRUE,
				'labels'            => $labels,
				'show_ui'           => TRUE,
				'show_admin_column' => TRUE,
				'query_var'         => TRUE,
				'rewrite'           => array('slug' => 'rcat'),
			);
			register_taxonomy('redirect_category', array('redirect'), $args);
		}

	public static function caps($action)
		{
			$caps = array
			(
				'edit_redirects',
				'edit_others_redirects',
				'edit_published_redirects',
				'edit_private_redirects',
				'publish_redirects',
				'delete_redirects',
				'delete_private_redirects',
				'delete_published_redirects',
				'delete_others_redirects',
				'read_private_redirects'
			);
			foreach(array('administrator') as $_role)
				if(is_object($_role = & get_role($_role)))
					foreach($caps as $_cap) switch($action)
					{
						case 'activate':
								$_role->add_cap($_cap);
								break;

						case 'deactivate':
								$_role->remove_cap($_cap);
								break;
					}
			unset($_role, $_cap); // Housekeeping.
		}

	public static function s2($exclued_post_types)
		{
			return array_merge($exclued_post_types, array('redirect'));
		}

	public static function redirect_redirects()
		{
			$is_redirection = is_singular('redirect');

			if($is_redirection && ($status = (is_numeric($status = get_post_meta(get_the_ID(), 'wp_redirect_status', TRUE))) ? $status : 301))
				if(($to = (string)get_post_meta(get_the_ID(), 'wp_redirect_to', TRUE)) && ($to = preg_replace('/%%(.+?)%%/i', '', preg_replace_callback('/%%\\\$([^\[]+?)(.+?)%%/i', 'wp_redirects::_url_e_gprcs_value', $to))))
					if(empty($_GET) || !get_post_meta(get_the_ID(), 'wp_redirect_to_w_query_vars', TRUE) || ($to = add_query_arg(urlencode_deep(wp_redirects::trim_strip_deep($_GET)), $to)))
						if(($to = wp_redirects::trim(preg_replace('/(?<!\:)\/+/', '/', $to), 0, '?&=')))
							wp_redirect($to, $status).exit();

			if($is_redirection) wp_redirect(home_url('/'), 301).exit();
		}

	public static function redirect_uri_patterns()
		{
			global $wpdb; // Database object reference.
			/** @var $wpdb WPDB Database object reference. */

			$where = "`meta_key` = 'wp_redirect_from_uri_pattern' AND `meta_value` != ''";
			$where .= " AND `post_id` IN(SELECT `ID` FROM `".$wpdb->posts."` WHERE `post_type` = 'redirect' AND `post_status` = 'publish')";

			if(is_array($patterns = $wpdb->get_results("SELECT `post_id`, `meta_value` AS `pattern` FROM `".$wpdb->postmeta."` WHERE ".$where)))
				{
					foreach($patterns as $pattern)
						{
							$pattern_matches = $_m = FALSE; // Initialize.
							$is_regex        = (stripos($pattern->pattern, 'regex:') === 0) ? TRUE : FALSE;

							if(!$is_regex && trim($pattern->pattern, '/') === trim($_SERVER['REQUEST_URI'], '/')) $pattern_matches = TRUE;
							else if($is_regex && @preg_match(trim(preg_replace('/^regex\:/i', '', $pattern->pattern)), $_SERVER['REQUEST_URI'], $_m)) $pattern_matches = TRUE;

							if($pattern_matches && ($status = (is_numeric($status = get_post_meta($pattern->post_id, 'wp_redirect_status', TRUE))) ? $status : 301))
								if(($to = (string)get_post_meta($pattern->post_id, 'wp_redirect_to', TRUE)) && ($to = preg_replace('/%%(.+?)%%/i', '', preg_replace_callback('/%%\\\$([^\[]+?)(.+?)%%/i', 'wp_redirects::_url_e_gprcs_value', (($is_regex && $_m) ? preg_replace('/%%([0-9]+)%%/ie', 'urlencode(@$_m[$1])', $to) : $to)))))
									if(empty($_GET) || !get_post_meta($pattern->post_id, 'wp_redirect_to_w_query_vars', TRUE) || ($to = add_query_arg(urlencode_deep(wp_redirects::trim_strip_deep($_GET)), $to)))
										if(($to = wp_redirects::trim(preg_replace('/(?<!\:)\/+/', '/', $to), 0, '?&=')))
											wp_redirect($to, $status).exit();
						}
				}
		}

	public static function _url_e_gprcs_value($_m)
		{
			if(is_array($_m) && in_array(array('_GET', '_POST', '_REQUEST', '_COOKIE', '_SESSION'), ($gprcs = strtoupper($_m[1])), TRUE))
				if(strlen($element_w_brackets = $_m[2]) && preg_match('/^(?:(?:\[(["\'])[a-z0-9 \._\-]+?\\1\])|(?:\[[0-9]+\]))+$/i', $element_w_brackets))
					eval('$value = urlencode(trim(stripslashes((string)@$'.$gprcs.$element_w_brackets.')));');

			return (isset($value)) ? urlencode(maybe_serialize($value)) : '';
		}

	public static function meta_boxes()
		{
			add_meta_box('wp-redirect', __('Redirect Configuration', 'wp-redirects'), 'wp_redirects::redirect_meta_box', 'redirect', 'normal', 'high');
		}

	public static function redirect_meta_box($post)
		{
			if(is_object($post) && !empty($post->ID) && ($post_id = $post->ID))
				{
					echo '<label for="wp-redirect-status">Redirection Status (optional HTTP status code):</label><br />'."\n";
					echo '<input type="text" id="wp-redirect-status" name="wp_redirect_status" style="width:100%;" value="'.esc_attr(get_post_meta($post_id, 'wp_redirect_status', TRUE)).'" /><br />'."\n";
					echo __('This is optional. It defaults to a value of <code>301</code> for redirection.', 'wp-redirects')."\n";

					echo '<div style="margin:10px 0 10px 0; line-height:1px; height:1px; background:#EEEEEE;"></div>'."\n";

					echo '<label for="wp-redirect-to">'.__('<strong>Redirection URL *</strong> (a full URL, absolutely required):', 'wp-redirects').'</label><br />'."\n";
					echo '<input type="text" id="wp-redirect-to" name="wp_redirect_to" style="width:100%;" value="'.esc_attr(get_post_meta($post_id, 'wp_redirect_to', TRUE)).'" /><br />'."\n";
					echo '<input type="checkbox" id="wp-redirect-to-w-query-vars" name="wp_redirect_to_w_query_vars" value="1" '.((get_post_meta($post_id, 'wp_redirect_to_w_query_vars', TRUE)) ? ' checked="checked"' : '').' /> <label for="wp-redirect-to-w-query-vars">'.__('Yes, pass all <code>$_GET</code> query string variables to this URL.', 'wp-redirects').'</label> <a href="#" onclick="alert(\''.__('If checked, all `$_GET` query string variables will be passed to the Redirection URL (adding to any that already exist).', 'wp-redirects').'\\n\\n'.__('It is also possible to use specific Replacement Codes in your Redirection URL, referencing `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`, or `$_SESSION` values.', 'wp-redirects').'\\n\\n'.__('Example Replacement Codes:', 'wp-redirects').'\\n%%$_REQUEST[\\\'my_var\\\']%%\\n%%$_POST[\\\'my-array\\\'][0]%%\\n%%$_GET[\\\'my\\\'][\\\'var\\\']%%\\n\\n'.__('* If you\\\'re using an advanced regular expression, there are Replacement Codes to reference parenthesized subpatterns.', 'wp-redirects').'\\n\\n'.__('Example Replacement Codes:', 'wp-redirects').'\\n%%0%% '.__('(string matching the full pattern)', 'wp-redirects').'\\n%%1%% '.__('(1st parenthesized subpattern)', 'wp-redirects').'\\n%%2%% '.__('(2nd parenthesized subpattern)', 'wp-redirects').'\'); return false;" tabindex="-1">[?]</a>'."\n";

					echo '<div style="margin:10px 0 10px 0; line-height:1px; height:1px; background:#EEEEEE;"></div>'."\n";

					echo '<label for="wp-redirect-from-uri-pattern">'.__('Additional Redirections From (optional pattern matching):', 'wp-redirects').'</label><br />'."\n";
					echo '<input type="text" id="wp-redirect-from-uri-pattern" name="wp_redirect_from_uri_pattern" style="width:100%; font-family:monospace;" value="'.esc_attr(get_post_meta($post_id, 'wp_redirect_from_uri_pattern', TRUE)).'" /><br />'."\n";
					echo __('This is optional. By default, redirection simply occurs <strong>from</strong> the Permalink for this Redirection.', 'wp-redirects').'<br /><br />'."\n";
					echo __('<strong>Redirecting from additional locations:</strong> This can be accomplished here with a pattern. By default, a pattern supplied here is caSe sensitive, using one exact comparison against <code>$_SERVER[\'REQUEST_URI\']</code>. However, it is possible to precede your pattern with: <code>regex:</code> to enable advanced regular expression pattern matching. Example: <code>regex: /pattern/i</code>. It is also possible to use regex Replacement Codes in your Redirection URL above, referencing any parenthesized subpatterns. For example: <code>%%0%%</code>, <code>%%1%%</code>, <code>%%2%%</code>.', 'wp-redirects')."\n";

					wp_nonce_field('wp-redirect-meta-boxes', 'wp_redirect_meta_boxes');
				}
		}

	public static function meta_boxes_save($post_id)
		{
			if(is_numeric($post_id) && (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) && ($_p = wp_redirects::trim_strip_deep($_POST)))
				if(!empty($_p['wp_redirect_meta_boxes']) && wp_verify_nonce($_p['wp_redirect_meta_boxes'], 'wp-redirect-meta-boxes'))
					if(!empty($_p['post_type']) && $_p['post_type'] === 'redirect' && current_user_can('edit_redirect', $post_id))
						{
							if(isset($_p['wp_redirect_status']))
								update_post_meta($post_id, 'wp_redirect_status', (string)$_p['wp_redirect_status']);

							if(isset($_p['wp_redirect_to']))
								update_post_meta($post_id, 'wp_redirect_to', (string)$_p['wp_redirect_to']);

							if(isset($_p)) // A checkbox may (or may NOT) be set; we update in either case.
								update_post_meta($post_id, 'wp_redirect_to_w_query_vars', (int)@$_p['wp_redirect_to_w_query_vars']);

							if(isset($_p['wp_redirect_from_uri_pattern']))
								update_post_meta($post_id, 'wp_redirect_from_uri_pattern', addslashes((string)$_p['wp_redirect_from_uri_pattern']));
						}
		}

	public static function trim($value, $chars = NULL, $extra_chars = NULL)
		{
			return wp_redirects::trim_deep($value, $chars, $extra_chars);
		}

	public static function trim_deep($value, $chars = NULL, $extra_chars = NULL)
		{
			$chars = (is_string($chars)) ? $chars : " \r\n\t\0\x0B";
			$chars = (is_string($extra_chars)) ? $chars.$extra_chars : $chars;

			if(is_array($value))
				{
					foreach($value as &$r)
						$r = wp_redirects::trim_deep($r, $chars);
					return $value;
				}
			return trim((string)$value, $chars);
		}

	public static function trim_strip_deep($value, $chars = NULL, $extra_chars = NULL)
		{
			return wp_redirects::trim_deep(stripslashes_deep($value), $chars, $extra_chars);
		}

	public static function activate()
		{
			wp_redirects::register();
			wp_redirects::caps('activate');
			flush_rewrite_rules();
		}

	public static function deactivate()
		{
			wp_redirects::caps('deactivate');
			flush_rewrite_rules();
		}
}