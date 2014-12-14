<?php

/**
 * Add "Access level" field to user profile.
 * @param object $user user data.
 */
 
function dal_show_user_fields($user) { 

    if ( ! current_user_can( 'dal_edit' ) || !current_user_can( 'edit_user', $user->ID ) ) {
        return;
    }   

    // Get user access level.   
    $user_level = dal_get_user_level($user->ID);
    // Minimum user access level = guest access level.
    $min_user_level = dal_get_guest_access_level();

?>
    <h3><?php _e( 'Data access by level', 'dal-plugin' ) ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="twitter"><?php _e( 'User access level', 'dal-plugin' ) ?></label></th>
            <td>
                <select name="dal_userlevel" id="dal_userlevel">
                    <?php     
                       for ( $i = $min_user_level; $i <= DAL_MAX_ACCESS_LEVEL; $i++ ) {
                          if ( $user_level == $i ) {
                              echo '<option value="'.$i.'" selected="selected">'.$i.'</option>';
                          }
                          else {
                              echo '<option value="'.$i.'">'.$i.'</option>';
                          }
                       }
                    ?>
                </select>
                <span class="description"><?php _e( 'Please, select access level.', 'dal-plugin' ) ?>
                </span>
            </td>
        </tr>
    </table>
<?php }

add_action('show_user_profile', 'dal_show_user_fields');
add_action('edit_user_profile', 'dal_show_user_fields');


/**
 * Form data processing on update.
 * @param integer $user_id user's id.
 */

function dal_save_user_fields($user_id) {
    
    if ( ! current_user_can( 'dal_edit' ) || !current_user_can( 'edit_user', $user_id ) ) {
        return;
    }    

    $request_level = (int) $_POST['dal_userlevel'];
    
    update_user_meta($user_id, 'dal_userlevel', $request_level);

}

add_action('personal_options_update', 'dal_save_user_fields');
add_action('edit_user_profile_update', 'dal_save_user_fields');

/**
 * Add column "Access level" on user list page in administrator console.
 */

function dal_manage_users_columns($columns) {

   $column_display_name = __( 'Access level', 'dal-plugin' );   
   $columns['dal_uf']   = $column_display_name;
   
   return $columns;
   
}
 
/**
 * Return user access level by user's id when dal_uf column is processing.
 */

function dal_manage_users_custom_column($value, $column_name, $user_id) {
    $user = get_userdata( $user_id );
    if ( 'dal_uf' == $column_name )
        return get_user_meta( $user_id, 'dal_userlevel', true );
		
    return $value;
} 


/**
 * Add dal_uf column to sortable columns.
 */

function dal_manage_users_sortable_columns($columns) {
    $columns['dal_uf'] = 'dal_uf';
    return $columns;
}


/**
 * @param WP_User_Query Object $userquery User Query object before query is executed
 */

function dal_user_query($userquery) {

   global $wpdb;

   $vars = $userquery->query_vars;
   
   if ('dal_uf' == $vars['orderby'] )  {
        $userquery->query_from .= " LEFT JOIN {$wpdb->usermeta} m1 ON {$wpdb->users}.ID=m1.user_id AND (m1.meta_key='dal_userlevel')"; 
        $userquery->query_orderby = ' ORDER BY (0+m1.meta_value) '. $vars['order'];    
   }
   return $userquery;
   
}


/**
 * Add actions and filters for manage users page.
 */

// Check if display column is required.
if ( ! empty($dal_cur_settings['dal_user_column']) ) {
    
    add_filter('manage_users_columns', 'dal_manage_users_columns');
    add_action('manage_users_custom_column',  'dal_manage_users_custom_column', 10, 3);
    
    // Check if sorting is required.
    if ( ! empty($dal_cur_settings['dal_user_column_sort']) ) {
        add_filter('manage_users_sortable_columns', 'dal_manage_users_sortable_columns');
        add_action('pre_user_query', 'dal_user_query');
    }
    
}