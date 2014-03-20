<?php
/*
Plugin Name: Data access levels
Plugin URI: https://github.com/lienann/DAL
Description: Plugin introduce new materials and users property named ACCESS LEVEL and allow limit access to materials when user access level lower then material access level.
Version: 1.0
Author: lienann
*/

/*  Copyright 2014  liena  (email: lienann@yandex.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 * 
 * Constants.
 * 
 */

// The post type that will be subject to restrictions by default. 
define('DAL_POST_TYPE_DEFAULT', 'post');

// Lowest data access level. 
define('DAL_MIN_ACCESS_LEVEL', 1);

// The highest data access level.
define('DAL_MAX_ACCESS_LEVEL', 100);

// Lowest user access level. 
define('DAL_MIN_USER_LEVEL', 0);


/**
 *
 * Plugin activation functions.
 *
 */

/**
 * data_access_levels_install() - define default plugin settings values.
 */
 
register_activation_hook(__FILE__,'data_access_levels_activation');

function data_access_levels_activation() {
   
    // Specify the initial settings plugin.
    
    // The post type that will be subject to restrictions by default.
    $post_type_default = array(DAL_POST_TYPE_DEFAULT);
    update_option( 'dal_post_types', serialize($post_type_default) );
    
    // Add status display "Access level" column in data and user controls.
    $dal_settings = array();
    $dal_settings['dal_post_column'] = 1;
    $dal_settings['dal_user_column'] = 1;
    update_option( 'dal_settings', serialize($dal_settings) );

    
    // Set maximum access level for administrator role.
    $admins = get_users('role=administrator');    
    foreach ($admins as $admin) {
        update_user_meta($admin->ID, 'dal_userlevel', DAL_MAX_ACCESS_LEVEL);        
    }    
    
}

/**
 * Load translated strings.
 */
function dal_load_textdomain() {
    load_plugin_textdomain( 'dal-plugin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'dal_load_textdomain' );

/**
 * 
 * Add capabilities.
 * dal_admin - capability to change plugin settings by admin.
 * dal_edit - capability to change user access level by admin.
 * 
 */

function dal_add_caps() {
    
    $role = get_role( 'administrator' );

    $role->add_cap( 'dal_admin' ); 
    $role->add_cap( 'dal_edit' );     
    
}
add_action( 'admin_init', 'dal_add_caps');


/**
 * 
 * Add js-scripts.
 * 
 */

function dal_inc_jscripts() {
    wp_register_script( 'dal-jsadmin', plugins_url('js/admin.js', __FILE__) );   
}
add_action( 'admin_init', 'dal_inc_jscripts');

/**
 *
 * Include plugin's functions.
 *
 */

/* Define base plugin functions. */
require_once( dirname(__FILE__).'/lib/functions.php' );

/* Get plugin settings. */
$dal_cur_settings = dal_get_settings();

/* Plugin's options page in WP administrator console. */
require_once( dirname(__FILE__).'/lib/dal_settings.php' );

/* Add "Access level" fields to user settins page. */
require_once( dirname(__FILE__).'/lib/userfileds.php' );

/* Add "Access level" fields to post settins page. */
require_once( dirname(__FILE__).'/lib/postfileds.php' );

/* Define objective functions plugin. */
require_once( dirname(__FILE__).'/lib/restrictions.php' );
