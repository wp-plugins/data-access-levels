=== Data access levels ===
Contributors: lienann 
Tags: access, level, restriction, permissions
Requires at least: 3.0
Tested up to: 3.7
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Restrict access to posts by user access level.

== Description ==

This plugin allow to set access level value for users and materials and limit 
access to materials of a specific type (posts, pages, custom post types) when 
user access level lower then material access level.

Also this plugin creates a new capabilities:
1. dal_admin - capability to change plugin settings
2. dal_edit - capability to change user access level

Attention to developers! 
Filters used in this plugin does not work when using get_posts() with
the parameter 'suppress_filters' => true.

I apologize for possible mistakes in plugin translation.
I will be glad to accept the help with the correct translation of a plugin into
English and to correction of my mistakes.
Please contact me via e-mail: lienann@yandex.ru

== Installation ==

1. Upload the `dal` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Tools -> DAL settings

== Changelog ==

= 1.0 =
* Initial release

== Screenshots ==

1. Plugin settings page
2. Access level column from the User panel (users.php)
3. "Access level" field on edit page