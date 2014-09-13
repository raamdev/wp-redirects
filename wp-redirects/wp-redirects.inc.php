<?php
namespace wp_redirects // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!defined('WP_REDIRECT_ROLES_ALL_CAPS')) define('WP_REDIRECT_ROLES_ALL_CAPS', 'administrator');
	if(!defined('WP_REDIRECT_ROLES_EDIT_CAPS')) define('WP_REDIRECT_ROLES_EDIT_CAPS', 'administrator,editor,author');

	if(!class_exists('\\'.__NAMESPACE__.'\\plugin'))
	{
		class plugin // Base plugin class.
		{
			public $is_pro = FALSE; // Lite version flag.

			public $file = ''; // Defined by class constructor.

			public $version = '140912'; // See: `readme.txt` file.

			public $text_domain = ''; // Defined by class constructor.

			public $default_options = array(); // Defined @ setup.

			public $options = array(); // Defined @ setup.

			public $cap = ''; // Defined @ setup.

			public $roles_all_caps = array(); // WP Roles; as array.

			public $roles_edit_caps = array(); // WP Roles; as array.

			public function __construct() // Constructor.
			{
				if(strpos(__NAMESPACE__, '\\') !== FALSE) // Sanity check.
					throw new \exception('Not a root namespace: `'.__NAMESPACE__.'`.');

				$this->file        = preg_replace('/\.inc\.php$/', '.php', __FILE__);
				$this->text_domain = str_replace('_', '-', __NAMESPACE__);

				add_action('after_setup_theme', array($this, 'setup'));
				register_activation_hook($this->file, array($this, 'activate'));
				register_deactivation_hook($this->file, array($this, 'deactivate'));
			}

			public function setup()
			{
				do_action('before__'.__METHOD__, get_defined_vars());

				load_plugin_textdomain($this->text_domain);

				$this->default_options = array( // Default options.
				                                'version' => $this->version
				); // Default options are merged with those defined by the site owner.
				$options               = (is_array($options = get_option(__NAMESPACE__.'_options'))) ? $options : array();
				$this->default_options = apply_filters(__METHOD__.'__default_options', $this->default_options, get_defined_vars());
				$this->options         = array_merge($this->default_options, $options); // This considers old options also.
				$this->options         = apply_filters(__METHOD__.'__options', $this->options, get_defined_vars());

				$this->cap = apply_filters(__METHOD__.'__cap', 'activate_plugins');

				if(WP_REDIRECT_ROLES_ALL_CAPS) // Specific Roles?
					$this->roles_all_caps = // Convert these to an array.
						preg_split('/[\s;,]+/', WP_REDIRECT_ROLES_ALL_CAPS, NULL, PREG_SPLIT_NO_EMPTY);
				$this->roles_all_caps = apply_filters('wp_redirect_roles_all_caps', $this->roles_all_caps);

				if(WP_REDIRECT_ROLES_EDIT_CAPS) // Specific Roles?
					$this->roles_edit_caps = // Convert these to an array.
						preg_split('/[\s;,]+/', WP_REDIRECT_ROLES_EDIT_CAPS, NULL, PREG_SPLIT_NO_EMPTY);
				$this->roles_edit_caps = apply_filters('wp_redirect_roles_edit_caps', $this->roles_edit_caps);

				add_action('wp_loaded', array($this, 'actions'));

				$this->register();

				$this->redirect_uri_patterns();
				add_action('wp', 'wp_redirects\plugin::redirect_redirects', 1);

				add_action('add_meta_boxes_'.($post_type = 'redirect'), 'wp_redirects\plugin::meta_boxes');
				add_action('save_post', 'wp_redirects\plugin::meta_boxes_save');

				add_action('manage_redirect_posts_custom_column', 'wp_redirects\plugin::show_admin_column_value', 10, 2);
				add_action('pre_get_posts', 'wp_redirects\plugin::admin_column_orderby', 10, 1);
				add_filter('manage_redirect_posts_columns', 'wp_redirects\plugin::add_admin_columns', 10, 1);
				add_filter('manage_edit-redirect_sortable_columns', 'wp_redirects\plugin::register_sortable_admin_columns', 10, 1);

				add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

				add_action('admin_init', array($this, 'check_version'));
				add_action('all_admin_notices', array($this, 'all_admin_notices'));
				add_action('all_admin_notices', array($this, 'all_admin_errors'));

				do_action('after__'.__METHOD__, get_defined_vars());
				do_action(__METHOD__.'_complete', get_defined_vars());
			}

			public function register()
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

			public function url($file = '', $scheme = '')
			{
				static $plugin_directory; // Static cache.

				if(!isset($plugin_directory)) // Not cached yet?
					$plugin_directory = rtrim(plugin_dir_url($this->file), '/');

				$url = $plugin_directory.(string)$file;

				if($scheme) // A specific URL scheme?
					$url = set_url_scheme($url, (string)$scheme);

				return apply_filters(__METHOD__, $url, get_defined_vars());
			}

			public function caps($action)
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
				else $_roles = $this->roles_all_caps;

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
				else $_roles = $this->roles_edit_caps;

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
				$to = preg_replace_callback('/%%\\\$([^\[]+?)(.+?)%%/i', 'wp_redirects\plugin::_url_e_gprcs_value', $to);
				$to = preg_replace('/%%(.+?)%%/i', '', $to); // Ditch any remaining replacement codes.

				$to = // Cleanup any double slashes left over by replacement codes.
					plugin::trim(preg_replace('/(?<!\:)\/+/', '/', $to), 0, '?&=#');

				if($to && !empty($_GET) && get_post_meta($redirect_id, 'wp_redirect_to_w_query_vars', TRUE))
					$to = add_query_arg(urlencode_deep(plugin::trim_strip_deep($_GET)), $to);

				$status = (is_numeric($status = get_post_meta($redirect_id, 'wp_redirect_status', TRUE))) ? (integer)$status : 301;

				if($to && $status) // Redirection URL w/ a possible custom status code.
				{
					// Update hit counter for this redirect
					$redirect_hits = (int)get_post_meta($redirect_id, 'wp_redirect_hits', TRUE) + 1;
					update_post_meta($redirect_id, 'wp_redirect_hits', $redirect_hits);

					// Update last access time for this redirect
					update_post_meta($redirect_id, 'wp_redirect_last_access', time());

					wp_redirect($to, $status).exit(); // It's a good day in Eureka :-)
				}
				wp_redirect(home_url('/'), 301).exit(); // Default redirection (ALWAYS redirect).
			}

			public function redirect_uri_patterns()
			{
				$patterns = // URI patterns.
					$this->wpdb()->get_results(
						"SELECT `post_id`, `meta_value` AS `pattern`". // Meta value is pattern (possibly a regex pattern).
						" FROM `".$this->wpdb()->postmeta."` WHERE `meta_key` = 'wp_redirect_from_uri_pattern' AND `meta_value` != ''".
						" AND `post_id` IN(SELECT `ID` FROM `".$this->wpdb()->posts."`". // Every `redirect` ID.
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
					$_to = preg_replace_callback('/%%\\\$([^\[]+?)(.+?)%%/i', 'wp_redirects\plugin::_url_e_gprcs_value', $_to);
					$_to = preg_replace('/%%(.+?)%%/i', '', $_to); // Ditch any remaining replacement codes.

					$_to = // Cleanup any double slashes left over by replacement codes.
						plugin::trim(preg_replace('/(?<!\:)\/+/', '/', $_to), 0, '?&=#');

					if($_to && !empty($_GET) && get_post_meta($_pattern->post_id, 'wp_redirect_to_w_query_vars', TRUE))
						$_to = add_query_arg(urlencode_deep(plugin::trim_strip_deep($_GET)), $_to);

					$_status = (is_numeric($_status = get_post_meta($_pattern->post_id, 'wp_redirect_status', TRUE))) ? (integer)$_status : 301;

					// Update hit counter for this redirect
					$redirect_hits = (int)get_post_meta($_pattern->post_id, 'wp_redirect_hits', TRUE) + 1;
					update_post_meta($_pattern->post_id, 'wp_redirect_hits', $redirect_hits);

					// Update last access time for this redirect
					update_post_meta($_pattern->post_id, 'wp_redirect_last_access', time());

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
				add_meta_box('wp-redirect', __('Redirect Configuration', 'wp-redirects'),
				             'wp_redirects\plugin::redirect_meta_box', 'redirect', 'normal', 'high');
				add_meta_box('wp-redirect-stats', __('Redirection Statistics', 'wp-redirects'), 'wp_redirects\plugin::redirect_stats_meta_box', 'redirect', 'normal', 'high');
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

			public static function redirect_stats_meta_box($post)
			{
				if(is_object($post) && !empty($post->ID) && ($post_id = $post->ID))
				{
					echo __('<strong>Total Hits:</strong>', 'wp-redirects').'&nbsp;<code><span id="wp-redirect-hit-count">'.((get_post_meta($post_id, 'wp_redirect_hits', TRUE)) ? esc_attr(get_post_meta($post_id, 'wp_redirect_hits', TRUE)) : '0').'</span></code>&nbsp;(<a href="#" onclick="document.getElementById(\'wp-redirect-hits\').value =\'0\';document.getElementById(\'wp-redirect-hit-count\').innerHTML =\'0\';document.getElementById(\'wp-redirect-hit-count-reset\').style.display = \'block\';">reset</a>)<br />'."\n";
					echo '<input type="hidden" id="wp-redirect-hits" name="wp_redirect_hits" value="'.((get_post_meta($post_id, 'wp_redirect_hits', TRUE)) ? esc_attr(get_post_meta($post_id, 'wp_redirect_hits', TRUE)) : '0').'" /><br />'."\n";
					echo '<div style="display:none;color:red;" id="wp-redirect-hit-count-reset">'.__('The hit count for this redirect has been reset. To save these changes, click Update.', 'wp-redirects').'</div>';

					wp_nonce_field('wp-redirect-meta-boxes', 'wp_redirect_meta_boxes');
				}
			}

			public static function meta_boxes_save($post_id)
			{
				if(is_numeric($post_id) && (!defined('DOING_AUTOSAVE') || !DOING_AUTOSAVE) && ($_p = plugin::trim_strip_deep($_POST)))
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

							if(isset($_p['wp_redirect_hits']))
								update_post_meta($post_id, 'wp_redirect_hits', (int)$_p['wp_redirect_hits']);

							if(isset($_p['wp_redirect_last_access']))
								update_post_meta($post_id, 'wp_redirect_last_access', (int)$_p['wp_redirect_last_access']);
						}
			}

			public static function trim($value, $chars = NULL, $extra_chars = NULL)
			{
				return plugin::trim_deep($value, $chars, $extra_chars);
			}

			public static function trim_deep($value, $chars = NULL, $extra_chars = NULL)
			{
				$chars = (is_string($chars)) ? $chars : " \r\n\t\0\x0B";
				$chars = (is_string($extra_chars)) ? $chars.$extra_chars : $chars;

				if(is_array($value) || is_object($value)) // Deeply.
				{
					foreach($value as &$_value)
						$_value = plugin::trim_deep($_value, $chars);
					unset($_value); // Housekeeping.

					return $value;
				}
				return trim((string)$value, $chars);
			}

			public static function trim_strip_deep($value, $chars = NULL, $extra_chars = NULL)
			{
				return plugin::trim_deep(stripslashes_deep($value), $chars, $extra_chars);
			}

			/** @return \wpdb */
			public function wpdb()
			{
				return $GLOBALS['wpdb'];
			}

			public static function add_admin_columns($columns)
			{
				return array_merge($columns,
				                   array('hits'        => __('Hits'),
				                         'last_access' => __('Last Access')));
			}

			public static function show_admin_column_value($column, $post_id)
			{
				switch($column)
				{
					case 'hits':
						$_hits = get_post_meta($post_id, 'wp_redirect_hits', TRUE);

						if($_hits == '')
							update_post_meta($post_id, 'wp_redirect_hits', '0');

						echo $_hits;

						unset($_hits);
						break;

					case 'last_access':
						$_last_access = get_post_meta($post_id, 'wp_redirect_last_access', TRUE);

						if($_last_access == '')
						{
							update_post_meta($post_id, 'wp_redirect_last_access', '0');
							$_last_access = '0';
						}

						if($_last_access == '0')
							echo __('Never', 'wp-redirects');
						else
							echo date(get_option('date_format'), get_post_meta($post_id, 'wp_redirect_last_access', TRUE));

						unset($_last_access);
						break;
				}
			}

			public static function register_sortable_admin_columns($columns)
			{
				$columns['hits']        = 'hit';
				$columns['last_access'] = 'last_access';

				return $columns;
			}

			public static function admin_column_orderby($query)
			{
				/** @var \WP_Query $query */

				if(!is_admin())
					return;

				$orderby = $query->get('orderby');

				if('hit' == $orderby)
				{
					$query->set('meta_key', 'wp_redirect_hits');
					$query->set('orderby', 'meta_value_num');
				}
				elseif('last_access' == $orderby)
				{
					$query->set('meta_key', 'wp_redirect_last_access');
					$query->set('orderby', 'meta_value_num');
				}
			}

			public function activate()
			{
				$this->setup(); // Setup routines.
				$this->caps('activate');
				flush_rewrite_rules();
			}

			public function check_version()
			{
				if(version_compare($this->options['version'], $this->version, '>='))
					return; // Nothing to do in this case.

				$this->options['version'] = $this->version;
				update_option(__NAMESPACE__.'_options', $this->options);

				$notices   = (is_array($notices = get_option(__NAMESPACE__.'_notices'))) ? $notices : array();
				$notices[] = __('<strong>WP Redirects:</strong> detected a new version of itself. Recompiling w/ latest version... all done :-)', $this->text_domain);
				update_option(__NAMESPACE__.'_notices', $notices);
			}

			public function deactivate()
			{

				delete_option(__NAMESPACE__.'_options');
				delete_option(__NAMESPACE__.'_notices');
				delete_option(__NAMESPACE__.'_errors');
				$this->caps('deactivate');
				flush_rewrite_rules();
			}

			public function actions()
			{
				if(empty($_REQUEST[__NAMESPACE__])) return;

				require_once dirname(__FILE__).'/includes/actions.php';
			}

			public function enqueue_admin_styles($hook)
			{
				if('edit.php' != $hook) // only load this style on the Edit posts admin screen
					return;

				$deps = array(); // Plugin dependencies.

				wp_enqueue_style(__NAMESPACE__, $this->url('/client-s/css/admin-style.css'), $deps, $this->version, 'all');
			}

			public function all_admin_notices()
			{
				$notices = (is_array($notices = get_option(__NAMESPACE__.'_notices'))) ? $notices : array();
				if($notices) delete_option(__NAMESPACE__.'_notices'); // Process one-time only.

				$notices = array_unique($notices); // Don't show dupes.

				if(current_user_can($this->cap)) foreach($notices as $_notice)
					echo apply_filters(__METHOD__.'__notice', '<div class="updated"><p>'.$_notice.'</p></div>', get_defined_vars());
				unset($_notice); // Housekeeping.
			}

			public function all_admin_errors()
			{
				$errors = (is_array($errors = get_option(__NAMESPACE__.'_errors'))) ? $errors : array();
				if($errors) delete_option(__NAMESPACE__.'_errors'); // Process one-time only.

				$errors = array_unique($errors); // Don't show dupes.

				if(current_user_can($this->cap)) foreach($errors as $_error)
					echo apply_filters(__METHOD__.'__error', '<div class="error"><p>'.$_error.'</p></div>', get_defined_vars());
				unset($_error); // Housekeeping.
			}
		}

		/**
		 * @return plugin Class instance.
		 */
		function plugin() // Easy reference.
		{
			return $GLOBALS[__NAMESPACE__];
		}

		$GLOBALS[__NAMESPACE__] = new plugin(); // New plugin instance.
	}
	else add_action('all_admin_notices', function () // Do NOT load in this case.
	{
		echo '<div class="error"><p>'. // Running multiple versions of this plugin at same time.
		     __('You appear to be running another instance of WP-Redirects. Please disable the other instance before enabling this one.',
		        str_replace('_', '-', __NAMESPACE__)).'</p></div>';
	});
}