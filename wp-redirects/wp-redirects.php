<?php
/*
Version: 131121
Text Domain: wp-redirects
Plugin Name: WP Redirects

Author URI: http://www.websharks-inc.com/
Author: WebSharks, Inc. (Jason Caldwell)

Plugin URI: http://www.websharks-inc.com/product/wp-redirects/
Description: Create Redirects! This plugin adds a new Post Type. Redirect from anywhere — to anywhere. A very lightweight plugin!
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit('Do NOT access this file directly: '.basename(__FILE__));

if(!defined('WP_REDIRECT_ROLES_ALL_CAPS')) define('WP_REDIRECT_ROLES_ALL_CAPS', 'administrator');
if(!defined('WP_REDIRECT_ROLES_EDIT_CAPS')) define('WP_REDIRECT_ROLES_EDIT_CAPS', 'administrator,editor,author');

class wp_redirects // WP Redirects; from anywhere — to anywhere.
{
	public static $roles_all_caps = array(); // WP Roles; as array.
	public static $roles_edit_caps = array(); // WP Roles; as array.

	public static function init() // Initialize WP Redirects.
	{
		load_plugin_textdomain('wp-redirects');

		if(WP_REDIRECT_ROLES_ALL_CAPS) // Specific Roles?
			wp_redirects::$roles_all_caps = // Convert these to an array.
				preg_split('/[\s;,]+/', WP_REDIRECT_ROLES_ALL_CAPS, NULL, PREG_SPLIT_NO_EMPTY);
		wp_redirects::$roles_all_caps = apply_filters('wp_redirect_roles_all_caps', wp_redirects::$roles_all_caps);

		if(WP_REDIRECT_ROLES_EDIT_CAPS) // Specific Roles?
			wp_redirects::$roles_edit_caps = // Convert these to an array.
				preg_split('/[\s;,]+/', WP_REDIRECT_ROLES_EDIT_CAPS, NULL, PREG_SPLIT_NO_EMPTY);
		wp_redirects::$roles_edit_caps = apply_filters('wp_redirect_roles_edit_caps', wp_redirects::$roles_edit_caps);

		wp_redirects::register();

		wp_redirects::redirect_uri_patterns();
		add_action('wp', 'wp_redirects::redirect_redirects', 1);

		add_action('add_meta_boxes_'.($post_type = 'redirect'), 'wp_redirects::meta_boxes');
		add_action('save_post', 'wp_redirects::meta_boxes_save');
	}

	public static function register()
	{
		$post_type_args           = array
		(
			'public'       => TRUE, 'exclude_from_search' => TRUE,
			'map_meta_cap' => TRUE, 'capability_type' => array('redirect', 'redirects'),
			'rewrite'      => array('slug' => 'r', 'with_front' => FALSE),
			'supports'     => array('title', 'author', 'revisions')
		);
		$post_type_args['labels'] = array
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
			'not_found'          => __('No Redirects found', 'wp-redirects'),
			'not_found_in_trash' => __('No Redirects found in Trash', 'wp-redirects')
		);
		register_post_type('redirect', $post_type_args);

		$taxonomy_args = array // Categories.
		(
		                       'public'       => TRUE, 'show_admin_column' => TRUE,
		                       'hierarchical' => TRUE, // This will use category labels.
		                       'rewrite'      => array('slug' => 'redirect-category', 'with_front' => FALSE),
		                       'capabilities' => array('assign_terms' => 'edit_redirects',
		                                               'edit_terms'   => 'edit_redirects',
		                                               'manage_terms' => 'edit_others_redirects',
		                                               'delete_terms' => 'delete_others_redirects')
		);
		register_taxonomy('redirect_category', array('redirect'), $taxonomy_args);
	}

	public static function caps($action)
	{
		$all_caps = array // The ability to manage (all caps).
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
		if($action === 'deactivate') // All on deactivate.
			$_roles = array_keys($GLOBALS['wp_roles']->roles);
		else $_roles = wp_redirects::$roles_all_caps;

		foreach($_roles as $_role) if(is_object($_role = get_role($_role)))
			foreach($all_caps as $_cap) switch($action)
			{
				case 'activate':
					$_role->add_cap($_cap);
					break;

				case 'deactivate':
					$_role->remove_cap($_cap);
					break;
			}
		unset($_roles, $_role, $_cap); // Housekeeping.

		$edit_caps = array // The ability to edit/publish/delete.
		(
		                   'edit_redirects',
		                   'edit_published_redirects',

		                   'publish_redirects',

		                   'delete_redirects',
		                   'delete_published_redirects'
		);
		if($action === 'deactivate') // All on deactivate.
			$_roles = array_keys($GLOBALS['wp_roles']->roles);
		else $_roles = wp_redirects::$roles_edit_caps;

		foreach($_roles as $_role) if(is_object($_role = get_role($_role)))
			foreach((($action === 'deactivate') ? $all_caps : $edit_caps) as $_cap) switch($action)
			{
				case 'activate':
					$_role->add_cap($_cap);
					break;

				case 'deactivate':
					$_role->remove_cap($_cap);
					break;
			}
		unset($_roles, $_role, $_cap); // Housekeeping.
	}

	public static function redirect_redirects()
	{
		if(!($is_singular_redirect = is_singular('redirect')))
			return; // Nothing to do in this case.

		$redirect_id = get_the_ID(); // Pull this one time only.

		$to = (string)get_post_meta($redirect_id, 'wp_redirect_to', TRUE);
		$to = preg_replace_callback('/%%\\\$([^\[]+?)(.+?)%%/i', 'wp_redirects::_url_e_gprcs_value', $to);
		$to = preg_replace('/%%(.+?)%%/i', '', $to); // Ditch any remaining replacement codes.

		$to = // Cleanup any double slashes left over by replacement codes.
			wp_redirects::trim(preg_replace('/(?<!\:)\/+/', '/', $to), 0, '?&=#');

		if($to && !empty($_GET) && get_post_meta($redirect_id, 'wp_redirect_to_w_query_vars', TRUE))
			$to = add_query_arg(urlencode_deep(wp_redirects::trim_strip_deep($_GET)), $to);

		$status = (is_numeric($status = get_post_meta($redirect_id, 'wp_redirect_status', TRUE))) ? (integer)$status : 301;

		if($to && $status) // Redirection URL w/ a possible custom status code.
			wp_redirect($to, $status).exit(); // It's a good day in Eureka :-)

		wp_redirect(home_url('/'), 301).exit(); // Default redirection (ALWAYS redirect).
	}

	public static function redirect_uri_patterns()
	{
		$patterns = // URI patterns.
			wp_redirects::wpdb()->get_results(
				"SELECT `post_id`, `meta_value` AS `pattern`". // Meta value is pattern (possibly a regex pattern).
				" FROM `".wp_redirects::wpdb()->postmeta."` WHERE `meta_key` = 'wp_redirect_from_uri_pattern' AND `meta_value` != ''".
				" AND `post_id` IN(SELECT `ID` FROM `".wp_redirects::wpdb()->posts."`". // Every `redirect` ID.
				"                    WHERE `post_type` = 'redirect' AND `post_status` = 'publish')");
		if(!is_array($patterns) || !$patterns) return; // Nothing to do in this case.

		foreach($patterns as $_pattern) // Iterate all redirection patterns.
		{
			$_pattern_matches = $_is_regex = $_is_regex_matches = FALSE; // Initialize.
			$_is_regex        = (stripos($_pattern->pattern, 'regex:') === 0) ? TRUE : FALSE;

			if(!$_is_regex && trim($_pattern->pattern, '/') === trim($_SERVER['REQUEST_URI'], '/'))
				$_pattern_matches = TRUE; // Exact match in this case (after trimming).

			else if($_is_regex && @preg_match(trim(preg_replace('/^regex\:/i', '', $_pattern->pattern)),
			                                  $_SERVER['REQUEST_URI'], $_is_regex_matches)
			) $_pattern_matches = TRUE; // A matching regular expression.

			else continue; // Nothing to do in this case; NOT a matching pattern.

			if(!($_to = (string)get_post_meta($_pattern->post_id, 'wp_redirect_to', TRUE)))
				continue; // Stop here if there is nothing to redirect to.

			$_to = (($_is_regex && $_is_regex_matches) // Regex replacement codes for captured groups.
				? preg_replace_callback('/%%([0-9]+)%%/i', function ($m) use ($_is_regex_matches)
				{
					return urlencode((string)@$_is_regex_matches[$m[1]]);
				}, $_to) : $_to);
			$_to = preg_replace_callback('/%%\\\$([^\[]+?)(.+?)%%/i', 'wp_redirects::_url_e_gprcs_value', $_to);
			$_to = preg_replace('/%%(.+?)%%/i', '', $_to); // Ditch any remaining replacement codes.

			$_to = // Cleanup any double slashes left over by replacement codes.
				wp_redirects::trim(preg_replace('/(?<!\:)\/+/', '/', $_to), 0, '?&=#');

			if($_to && !empty($_GET) && get_post_meta($_pattern->post_id, 'wp_redirect_to_w_query_vars', TRUE))
				$_to = add_query_arg(urlencode_deep(wp_redirects::trim_strip_deep($_GET)), $_to);

			$_status = (is_numeric($_status = get_post_meta($_pattern->post_id, 'wp_redirect_status', TRUE))) ? (integer)$_status : 301;

			wp_redirect($_to, $_status).exit(); // Redirection URL w/ a possible custom status code.
		}
		unset($_pattern, $_is_regex, $_pattern_matches, $_is_regex_matches, $_to, $_status); // Housekeeping.
	}

	public static function _url_e_gprcs_value($m)
	{
		if(isset($m[1], $m[2]) && in_array(($gprcs = strtoupper($m[1])), array('_GET', '_POST', '_REQUEST', '_COOKIE', '_SESSION'), TRUE))
			if(strlen($element_w_brackets = $m[2]) && preg_match('/^(?:(?:\[(["\'])[a-z0-9 \._\-]+?\\1\])|(?:\[[0-9]+\]))+$/i', $element_w_brackets))
				eval('$value = urlencode(trim(stripslashes((string)@$'.$gprcs.$element_w_brackets.')));');

		return (!empty($value)) ? $value : ''; // Default to empty string.
	}

	public static function meta_boxes()
	{
		add_meta_box('wp-redirect', __('Redirect Configuration', 'wp-redirects'), // One meta box (for now).
		             'wp_redirects::redirect_meta_box', 'redirect', 'normal', 'high');
	}

	public static function redirect_meta_box($post)
	{
		if(!is_object($post) || empty($post->ID) || !($post_id = $post->ID))
			return; // No can-do. Should NOT happen; but just in case.

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

		if(is_array($value) || is_object($value)) // Deeply.
		{
			foreach($value as &$_value)
				$_value = wp_redirects::trim_deep($_value, $chars);
			unset($_value); // Housekeeping.

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
		wp_redirects::init();
		wp_redirects::caps('activate');
		flush_rewrite_rules();
	}

	public static function deactivate()
	{
		wp_redirects::caps('deactivate');
		flush_rewrite_rules();
	}

	/** @return \wpdb */
	public static function wpdb()
	{
		return $GLOBALS['wpdb'];
	}
}

add_action('init', 'wp_redirects::init', 1);
register_activation_hook(__FILE__, 'wp_redirects::activate');
register_deactivation_hook(__FILE__, 'wp_redirects::deactivate');