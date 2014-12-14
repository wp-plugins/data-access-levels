<?php

/**
 * Plugin settings page in admin console WP.
 * @see tools.php?page=dal_settings.php
 */

/**
 * Create plugin menu items.
 */ 
function dal_menu_items() {
    
    $page = add_submenu_page(
        'tools.php'
        , __( 'DAL settings', 'dal-plugin' )
        , __( 'DAL settings', 'dal-plugin' )
        , 'dal_admin'   
        , basename(__FILE__)
        , 'dal_create_settings_page'    // function - callback
    );
    
    add_action( 'admin_print_scripts-' . $page, 'dal_inc_admin_jscripts' );
    
}

add_action( 'admin_menu', 'dal_menu_items' );

/**
 * Add jscripts for settings page.
 */

function dal_inc_admin_jscripts() {
    wp_enqueue_script( 'dal-jsadmin' );
}

/**
 * Construct settings form.
 * @staticvar string $dal_post_types - post types list to activate restrictions for. 
 */ 

function dal_create_settings_page() {

    $dal_post_types = dal_get_restriction_post_types();
    
    // Get all post types, except attachment.
    $post_types = (array) dal_get_post_types(false);   
    
    // Get plugin settings
    $settings = dal_get_settings();
    $guest_access_level = ( ! empty($settings['dal_guest_access_level']) ? (int)$settings['dal_guest_access_level']: 0 );
    
    ?>

    <div class="wrap">
        <h2><?php _e( 'Data access levels. General settings.', 'dal-plugin' ) ?></h2>

        <form method="post" action="options.php">
            <?php settings_fields( 'dal-settings-group' ); ?>
            <?php 
            if ( ! empty($_REQUEST['error']) ): 
                ?>
                <p style="color: red"><?php _e( 'Form contain bad data.', 'dal-plugin' ) ?></p>
                <?php 
            endif; ?>
            <table class="form-table">
                <tr valign="top">                    
                    <th scope="row"><?php _e('Post types', 'dal-plugin'); ?></th>
                    <td>
                        <fieldset><legend class="screen-reader-text"><span></span></legend><label>
                            <select multiple="multiple" name="dal_post_types[]">
                                <?php
                                foreach ( $post_types as $type ) {
                                    ?>
                                    <option value="<?php echo esc_attr($type); ?>" <?php if (  in_array( $type, $dal_post_types ) ) { ?> selected="selected" <?php } ?>  ><?php echo $type ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e( 'Select post types to activate access level restrictions for them.', 'dal-plugin' ) ?></p>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">                    
                    <th scope="row"><?php _e('Extra columns', 'dal-plugin'); ?></th>
                    <td>
                        <fieldset><legend class="screen-reader-text"><span><?php _e( 'Select post types to activate access level restrictions for them.', 'dal-plugin' ) ?></span></legend>
                            <div>			
                                <input type="checkbox" name="dal_post_column" id="dal_post_column" value="1" <?php if ( ! empty( $settings['dal_post_column'] ) ) echo 'checked="checked"'; ?> />                
                                &nbsp;&nbsp;<label><?php _e( 'Allow column "Access levels" on manage posts page.', 'dal-plugin' ) ?></label>
                                <div id="dal_post_column_hidden">
                                    <input type="checkbox" name="dal_post_column_sort" value="1" <?php if ( ! empty( $settings['dal_post_column_sort'] ) ) echo 'checked="checked"'; ?> />                
                                    &nbsp;&nbsp;<label><?php _e( 'Sort by column.', 'dal-plugin' ) ?></label><br /><p class="description"><?php _e( 'Attention! When sorting of data in table only posts where "access level" is assigned will be included.', 'dal-plugin' ) ?></p>
                                </div>
                            </div>
                            <br />
                            <div>	                
                                <input type="checkbox" name="dal_user_column" id="dal_user_column" value="1" <?php if ( ! empty( $settings['dal_user_column'] ) ) echo 'checked="checked"'; ?> />                
                                &nbsp;&nbsp;<label><?php _e( 'Allow column "Access level" on manage users page.', 'dal-plugin' ) ?></label>                
                                <div id="dal_user_column_hidden">
                                    <input type="checkbox" name="dal_user_column_sort" value="1" <?php if ( ! empty( $settings['dal_user_column_sort'] ) ) echo 'checked="checked"'; ?> />                
                                    &nbsp;&nbsp;<label><?php _e( 'Sort by column.', 'dal-plugin' ) ?></label><br /><p class="description"><?php _e( 'Attention! When sorting of data in table only users where "access level" is assigned will be included.', 'dal-plugin' ) ?></p>
                                </div>
                            </div>
                           
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">                    
                    <th scope="row"><?php _e('Restrictions in admin panel', 'dal-plugin'); ?></th>
                    <td>
                        <fieldset><legend class="screen-reader-text"><span><?php _e( 'Select post types to activate access level restrictions for them.', 'dal-plugin' ) ?></span></legend><label for="users_can_register">
                            <input type="checkbox" name="dal_admin_restriction" id="dal_admin_restriction" value="1" <?php if ( ! empty( $settings['dal_admin_restriction'] ) ) echo 'checked="checked"'; ?> />   
                            <?php _e( 'Should we apply restrictions in admin console?', 'dal-plugin' ) ?></label>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">                    
                    <th scope="row"><?php _e('Guest access level', 'dal-plugin'); ?></th>
                    <td>
                        <label>
                            <select name="dal_guest_access_level">
                                <option value="0" <?php selected( $guest_access_level, 0 );?> >0</option>
                                <option value="1" <?php selected( $guest_access_level, 1 );?> >1</option>
                            </select>  
                            <p class="description"><?php _e( 'Minimum value of Post access level = 1. When Guest access level = 0, guests will lose access to the all restricted post types.', 'dal-plugin' ) ?></p></label>                        
                    </td>
                </tr>                

            </table>
            <input type="hidden" name="dal_action" value="dal_update" />
            <?php submit_button(); ?>

        </form>
    </div>

    <?php
}


/**
 * Form data processing.
 */ 

function dal_settings_update() {
    
    if ( ! current_user_can( 'dal_admin' ) ) {
        return;
    }

    if ( ! empty($_REQUEST['dal_action']) ) {      
   
        // Get all post types, except attachment.
        $post_types = (array) dal_get_post_types(false);
           
        // Define array for checking form data
        $result_post_types  = array();
        
        // Save form data in array
        $request_post_types = (array) $_REQUEST['dal_post_types'];

        // Check every post types in the form for existence in database. Skip it if was forged.
        foreach ( $request_post_types as $request_post_type ) {

            $dal_post_type = trim( htmlspecialchars($request_post_type, ENT_QUOTES) );
            if ( in_array( $dal_post_type, $post_types ) ) {
                $result_post_types[] = $dal_post_type;
            }

        }
        
        // If form data was empty, set default value.
        if ( empty($result_post_types) ) {
            $result_post_types = array(DAL_POST_TYPE_DEFAULT);
        }
        
        // Define plugin settings names in array.
        $setting_vars_names = array (
            'dal_post_column',
            'dal_post_column_sort',
            'dal_user_column',
            'dal_user_column_sort',
            'dal_admin_restriction',
            'dal_guest_access_level'
        );
        
        // Define array for plugin settings.
        $dal_settings = array();
        
        // Check form data values and fill $dal_settings array.
        foreach ( $setting_vars_names as $var ) {
            if ( ! empty($_REQUEST[$var]) && $_REQUEST[$var] == 1 ) {
                $dal_settings[$var] = 1;
            }
        }        
        
        // Save values for restricted post types.
        update_option( 'dal_post_types', serialize($result_post_types) );
        // Save plugin settings.
        update_option( 'dal_settings', serialize($dal_settings) );
        
        // Forward browser to next page.
        header( 'Location: '.get_bloginfo('wpurl').'/wp-admin/tools.php?page=dal_settings.php&updated=true' );
        die();
    }
}

add_action( 'init', 'dal_settings_update', 100 );
