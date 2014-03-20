<?php

/**
 * Public post types list
 * @param bool $builtin post types category (false - all types, true - custom post types only)
 * @return array types list
 */
function dal_get_post_types( $builtin = false ) {

#   $args       =  array( 'public' => true, '_builtin' => $builtin  ); 
    $args = array( 'public' => true  ); 

    if ( $builtin === true ) {
        $args = array_merge($args, array('_builtin' => false));
    }

    $output              =  'names'; 
    $operator            =  'and'; 
    $result_post_types   =  (array) get_post_types($args, $output, $operator);

    // Exclude attachments from types list.
    unset($result_post_types['attachment']);

    return $result_post_types;    
} 

/**
 * Get post types list to apply restrictions by levels.
 * @param string $type_result format of the return value.
 * @return mixed post types list.
 */
function dal_get_restriction_post_types( $type_result = 'array' ) {

    $dal_post_types_serialize   =  get_option( 'dal_post_types' );
    $dal_post_types             =  (array) unserialize($dal_post_types_serialize);

    if ( empty($dal_post_types) ) {
        $dal_post_types = array(DAL_POST_TYPE_DEFAULT);
    }

    // Result in string format.
    if ( $type_result == 'list' ) {
        $dal_post_types = implode("', '", $dal_post_types);
    }
    
    return $dal_post_types;
    
} 

/**
 * Get plugin settings.
 * @return array plugin settings values.
 */
function dal_get_settings() {

    $dal_settings_serialize   =  get_option( 'dal_settings' );
    $dal_settings             =  (array) unserialize($dal_settings_serialize);
    
    return (array) $dal_settings;
    
} 


/**
 * Get post access level by post ID if exist, otherwise level of current user.
 */
function dal_get_post_level( $post_id = 0 ) {
    
    if ( empty($post_id) ) {
        global $post;
        $post_id = $post->ID;
    }

    $post_level = get_post_meta($post_id, 'dal_postlevel', true);
    if ( ! empty($post_level) ) {
         return $post_level;
    }

    $user_id = get_current_user_id();   

    return dal_get_user_level($user_id);
    
}


/**
 * Get user access level by user ID.
 * @param integer $user_id user's id.
 * @return mixed user access level.
 */
function dal_get_user_level( $user_id = 0 ) {
   
    $user_level = get_user_meta( $user_id, 'dal_userlevel', TRUE );
    if ( ! empty($user_level) ) {
        return $user_level;
    }

    return DAL_MIN_USER_LEVEL;

}
