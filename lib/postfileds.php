<?php

/**
 * Add custom field "Access level" to custom types of post
 * dal_get_restriction_post_types() function is used here to get restricted post types
 */

add_action('add_meta_boxes', 'dal_post_options_box');

function dal_post_options_box() {

    $dal_post_types = dal_get_restriction_post_types();

    if ( function_exists('add_meta_box') ) { 
        foreach ( $dal_post_types as $post_type ) {
            if ( post_type_exists( $post_type ) ) {
                add_meta_box('dal_fields', __( 'Post access level', 'dal-plugin' ), 'dal_add_postfield', $post_type, 'normal', 'high');
            }
        }
    }

}

function dal_add_postfield() {
    
    // Add "nonce" form hidden filed for form request verification
    wp_nonce_field( plugin_basename(__FILE__), 'dal_noncename' );  

    // User access level.
    $user_id        = get_current_user_id();
    $user_level     = dal_get_user_level( $user_id );    
    
    // Create "Access level" form elemets.
    echo '<label for="myplugin_new_field">' . __( 'Please, select access level:', 'dal-plugin' ) . '</label> ';  
    echo '<select name="dal_postlevel" id="dal_postlevel">';
        // The highest possible access level for input does not exceed the user access level.
        for ( $i = DAL_MIN_ACCESS_LEVEL; $i <= $user_level; $i++ ) {
            echo $i;
            if ( esc_attr(dal_get_post_level()) == $i ) {
                echo '<option value="'.$i.'" selected="selected">'.$i.'</option>';
            }
            else {
                echo '<option value="'.$i.'">'.$i.'</option>';
            }
        }
    echo '</select>';
}

/**
 * 
 * Check "Access level" form data and save.
 * 
 */

add_action('save_post', 'dal_custom_add_save');

function dal_custom_add_save($post_id) { 
    
    if ( empty( $_POST['dal_noncename']) || ! wp_verify_nonce( $_POST['dal_noncename'], plugin_basename(__FILE__) )) {  
        return $post_id;  
    }  
    
    if ( ! defined('DOING_AUTOSAVE') && ! DOING_AUTOSAVE ) {
        return $post_id;
    }
    else
    {
        if ( 'page' == $_POST['post_type'] ) {  
            if ( ! current_user_can( 'edit_page', $post_id ) )  
                return $post_id;  
        } 
        else {  
            if ( ! current_user_can( 'edit_post', $post_id ) )  
                return $post_id;  
        }      
        
        // User access level.
        $user_id        = get_current_user_id();
        $user_level     = dal_get_user_level( $user_id );
        
        // Access level on request.        
        $request_level  = (int) htmlspecialchars($_POST['dal_postlevel'], ENT_QUOTES);
        
        // Additional verification. If the user access level below the level of
        // resulting query, take the maximum possible for the user.
        if ( empty($request_level) ) {
            $request_level = DAL_MIN_ACCESS_LEVEL;
        }
        elseif ( $request_level > $user_level ) {
            $request_level = $user_level;
        }

        update_post_meta($post_id, 'dal_postlevel', $request_level);
    }

}


// Add new column "Access level" to manage posts page.
function dal_add_new_post_column($defaults) {
    $defaults['dal_pf'] = __( 'Access level', 'dal-plugin' );
    return $defaults;
}

// Get post access level.
function dal_get_item_post_level($column, $post_id) { 
    if ( $column == 'dal_pf' ) {
         print get_post_meta($post_id, 'dal_postlevel', TRUE);
    }
} 

// Add column "dal_pf" to allowed sort columns.
function dal_allow_order_by_post_level($columns) {
    $columns['dal_pf'] = 'dal_pf';
    return $columns;
}

// Tune sorting on "Access level" column.
function dal_views_column_orderby($vars) {
    if (isset( $vars['orderby'] ) && $vars['orderby'] == 'dal_pf') {
        $vars = array_merge($vars, array(
            'meta_key' => 'dal_postlevel',
            'orderby' => 'meta_value_num'
        ) );
    }
    return $vars;
}

function dal_add_manage_posts_custom_column() {
    
    global $dal_cur_settings;
    
    $dal_post_types = dal_get_restriction_post_types();
    foreach ( $dal_post_types as $post_type ) {
        add_action('manage_'.$post_type.'_posts_custom_column', 'dal_get_item_post_level', 10, 2);
        add_filter('manage_'.$post_type.'_posts_columns', 'dal_add_new_post_column');
        // Check if sort is required
        if ( ! empty($dal_cur_settings['dal_post_column_sort']) ) {
            add_filter('manage_edit-'.$post_type.'_sortable_columns', 'dal_allow_order_by_post_level');
        }
    }
    
}

/**
 * Add column "Access level" in manage posts.
 * Function "dal_add_manage_posts_custom_column" attached to INIT action
 * for correct operation get_post_types() function.
 */

// Check if colunm is required.
if ( ! empty($dal_cur_settings['dal_post_column']) ) {
    
    add_action( 'init', 'dal_add_manage_posts_custom_column', 100 );
    
    // Check if sort is required.
    if ( ! empty($dal_cur_settings['dal_post_column_sort']) ) {
        add_filter('request', 'dal_views_column_orderby');
    }
    
}
