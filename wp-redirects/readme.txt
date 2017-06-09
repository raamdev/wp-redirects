=== WP Redirects ===

Stable tag: 141009
Requires at least: 3.2
Tested up to: 4.9-alpha
Text Domain: wp-redirects

License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Contributors: raamdev, WebSharks, JasWSInc
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

== License ==

Copyright: © 2013 [WebSharks, Inc.](http://www.websharks-inc.com/bizdev/) (coded in the USA)

Released under the terms of the [GNU General Public License](http://www.gnu.org/licenses/gpl-2.0.html).

= Credits / Additional Acknowledgments =

* Software designed for WordPress®.
	- GPL License <http://codex.wordpress.org/GPL>
	- WordPress® <http://wordpress.org>
* Some JavaScript extensions require jQuery.
	- GPL-Compatible License <http://jquery.org/license>
	- jQuery <http://jquery.com/>
* CSS framework and some JavaScript functionality provided by Bootstrap.
	- GPL-Compatible License <http://getbootstrap.com/getting-started/#license-faqs>
	- Bootstrap <http://getbootstrap.com/>
* Icons provided by Font Awesome.
	- GPL-Compatible License <http://fortawesome.github.io/Font-Awesome/license/>
	- Font Awesome <http://fortawesome.github.io/Font-Awesome/>

== Changelog ==

= v141009 =

- **New Feature**: Hit counter. WP Redirects will now keep track of how many times a redirect has been used. There is a new, sortable Hits column in the main Redirects list that allows you to sort your redirects by the number of hits. See [#4](https://github.com/websharks/wp-redirects/issues/4).
- **New Feature**: Last Access Time. WP Redirects will now keep track of the date and time a redirect was last used. There is a new, sortable Last Access column in the main Redirects list that allows you to sort your redirect by the last access time. See [#4](https://github.com/websharks/wp-redirects/issues/4).
- **Enhancement**: Overhauled the New/Edit Redirect screen. Sections are now modular meta boxes that can be hidden/shown using the Screen Options panel. Lots of inline documentation was also added to help clarify each option. See [#13](https://github.com/websharks/wp-redirects/issues/13).
- **Enhancement**: Refactored codebase to improve readability and organization of code. See [#9](https://github.com/websharks/wp-redirects/issues/9).
- **New Maintainer**: Added raamdev to the contributors list. [Raam Dev](https://profiles.wordpress.org/raamdev/) will now be maintaining the WP Redirects plugin.

= v131121 =

* General code cleanup and optimizatioon.
* Adding support for categories and tags against Redirects.

= v130206 =

 * Initial release.
