=== WP Redirects ===

Stable tag: 130206
Requires at least: 3.2
Tested up to: 3.7.1
Text Domain: wp-redirects

License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Contributors: WebSharks
Donate link: http://www.websharks-inc.com/r/wp-theme-plugin-donation/
Tags: redirect, redirects, 301 redirects, links, relocate, SEO, post type, post types, utilities, posts, pages

Create Redirects! This plugin adds a new Post Type.

== Description ==

Redirect from anywhere — to anywhere. A very lightweight plugin!

This plugin is VERY simple; NO configuration options necessary.

This plugin adds a new Post Type; making it SUPER easy to create redirections on your site. From anywhere — to anywhere! It even creates redirection links for you (i.e. Redirect Permalinks — using these is optional however).

After installing this plugin, create a new Redirect (find menu item on the left in your Dashboard). Redirects can be simple or complex. You can even use regular expression patterns! It is also possible to control the HTTP status code that is sent to a browser during redirection.

== Frequently Asked Questions ==

= Who can manage Redirects in the Dashboard? =

By default, only WordPress® Administrators can manage (i.e. create/edit/delete/manage) Redirects. Editors and Authors can create/edit/delete their own Redirects, but permissions are limited for Editors/Authors. If you would like to give other WordPress Roles the Capabilities required, please use a plugin like [Enhanced Capability Manager](http://wordpress.org/extend/plugins/capability-manager-enhanced/).

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

NOTE: There are also some WordPress filters integrated into the code for this plugin, which can make permissions easier to deal with in many cases. You can have a look at the source code and determine how to proceed on your own; if you choose this route.

== Installation ==

= WP Redirects is Very Easy to Install =

1. Upload the `/wp-redirects` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress®.
3. Create Redirects in WordPress® (see: **Dashboard -› Redirects**).

== Changelog ==

= v130206 =

 * Initial release.