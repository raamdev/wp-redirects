=== WP Redirects ===

Contributors: WebSharks
Donate link: http://www.s2member.com/donate/
Tags: redirect, redirects, 301 redirects, links, relocate, SEO, post type, post types, utilities, posts, pages

License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Stable tag: 130206
Requires at least: 3.2
Tested up to: 3.6-alpha

Create Redirects! This plugin adds a new Post Type. Redirect from anywhere — to anywhere. A very lightweight plugin!

== Description ==

This plugin is VERY simple. There are NO configuration options necessary.

This plugin adds a new Post Type. This plugin makes it SUPER easy to create redirections on your site. From anywhere — to anywhere! It even creates redirection links for you (i.e. Redirect Permalinks — using these is optional however). This is a very lightweight plugin.

After installing this plugin, create a new Redirect (find menu item on the left in your Dashboard). Redirects can be simple or complex. You can even use regular expression patterns! It is also possible to control the HTTP status code that is sent to a browser during redirection.

== Frequently Asked Questions ==

#### Who can manage Redirects in the Dashboard?

By default, only WordPress® Administrators can manage (i.e. create/edit) Redirects. If you would like to give others the Capabilities required, please use a plugin like [Enhanced Capability Manager](http://wordpress.org/extend/plugins/capability-manager-enhanced/).

Add the following Capabilities to the additional Roles that should be allowed to manage Redirects.

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

== Installation ==

= WP Redirects is very easy to install (instructions) =
1. Upload the `/wp-redirects` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` menu in WordPress®.
3. Create Redirects in WordPress® (see: Dashboard -› Redirects).

== Changelog ==

= v130206 =
 * Initial release.