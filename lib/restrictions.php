<?php

/**
 * 
 * Construct query using post restriction list.
 * @global object $wpdb
 * @param string $wpdp_post_table_alias - posts table alias in database queries. 
 * @return array
 * 
 */
function dal_get_query_restrictions( $wpdp_post_table_alias = NULL ) {

    global $wpdb;
    if ( empty($wpdp_post_table_alias) ) {
        $wpdp_post_table_alias = $wpdb->posts;
    }

    // Get current user access level.
    $user_id     = get_current_user_id();  
    $user_level  = dal_get_user_level($user_id);        

    // Get types to limit result in database query.
    $post_types  = dal_get_restriction_post_types('list');
    $post_types  = "'" . $post_types . "'";

    $clauses = array();

    // Construct conditions for query to get only allowed posts where user access
    // level greater or equal than data access level
    $clauses['join']     = " LEFT JOIN {$wpdb->postmeta} dal1 ON {$wpdp_post_table_alias}.ID=dal1.post_id AND {$wpdp_post_table_alias}.post_type IN ( $post_types ) ";  

    $clauses['where']    = " (( dal1.meta_key='dal_postlevel' AND dal1.meta_value<=$user_level AND {$wpdp_post_table_alias}.post_type IN ( $post_types ) )  OR {$wpdp_post_table_alias}.post_type NOT IN ( $post_types ) ) ";  

    return $clauses;
   
}

/**
 * Edit database query to apply access level restrictions .
 */

function dal_intercept_query_clauses( $clauses )
{  

    $dal_clauses = dal_get_query_restrictions();   

    $clauses['join'] .= $dal_clauses['join'];
    if( ! empty($clauses['where']) && ! empty($dal_clauses['where']) ) {
        $clauses['where'] .= " AND ";
    }  
    $clauses['where'] .= $dal_clauses['where'];

    return $clauses;
   
}

/**
 * Restrict access to post edit page if access level lower than required.
 * @global string $pagenow
 */
function dal_restrict_edit_page() {
    
    global $pagenow;
    
    if ( 'post.php' == $pagenow ) {
        if ( 'edit' == $_REQUEST['action'] ) {
            
            $request_post_id = (int) $_REQUEST['post'];
            
            // Get current post type.
            $post_type = get_post_type( $request_post_id );
            
            /**
             * Check if restrictions to be applied exist for this type. 
             * This check is required when restiction to post type was canceled 
             * but access level fields stil exist in posts metadata (table 
             * {wpdb->prefix}postmeta}).
             */
            $restriction_post_types = dal_get_restriction_post_types();
            if ( ! in_array($post_type, $restriction_post_types) ) {
                return;
            }
    
            // Get user access level.
            $user_id    = get_current_user_id();  
            $user_level = dal_get_user_level($user_id);          
            
            // Get post access level.
            $post_level = dal_get_post_level( $request_post_id );
            
            // Compare and restrict.
            if( $user_level < $post_level ) {
                wp_die( __('You are not allowed to access this part of the site') );
            }
            
        }
    }
    
}

/**
 * Recalculation of number of records taking into user access levels..
 * @global object $wpdb
 * @param object $counts An object containing the current post_type's post 
 * counts by status.
 * @param string $type The post type.
 * @param string $perm The permission to determine if the posts are 'readable' 
 * by the current user.
 * @return object An object containing the current post_type's post counts by status.
 */
function dal_wp_count_posts( $counts, $type, $perm ) {

    global $wpdb;

    $user       = wp_get_current_user();

    $cache_key  = 'posts-' . $type;
    
    $dal_clauses    = dal_get_query_restrictions();   
    
    $stats          = array();
    $where          = '';
    $join           = '';
    
    $join   .= $dal_clauses['join'];

    if( ! empty( $where ) && ! empty($dal_clauses['where']) ) {
        $where .= " AND ";
    }  
    $where  .= $dal_clauses['where'] . ' AND ';  
    

    $query  = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} {$join} WHERE {$where} post_type = %s";
    if ( 'readable' == $perm && is_user_logged_in() ) {
        $post_type_object = get_post_type_object($type);
        if ( !current_user_can( $post_type_object->cap->read_private_posts ) ) {
            $cache_key .= '_' . $perm . '_' . $user->ID;
            $query .= " AND (post_status != 'private' OR ( post_author = '$user->ID' AND post_status = 'private' ))";
        }
    }
    $query .= ' GROUP BY post_status';


    $results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
    $counts = array_fill_keys( get_post_stati(), 0 );

    foreach ( $results as $row )
        $counts[ $row['post_status'] ] = $row['num_posts'];

    $counts = (object) $counts;
    wp_cache_set( $cache_key, $counts, 'counts' );


    return $counts;
    
}

/**
 * Correction sticky posts array.
 * @global object $wpdb
 * @param array $sticky_posts sticky posts array
 * @return array fitered sticky posts array 
 */
function dal_option_sticky_posts($sticky_posts) {
    
    global $wpdb;

    $dal_clauses    = dal_get_query_restrictions();   
    
    $where  = '';
    $join   = '';
    
    $join   .= $dal_clauses['join'];

    if( ! empty($dal_clauses['where']) ) {
        $where .= " AND ";
    }  
    $where  .= $dal_clauses['where'];  
    

    $query  = "SELECT ID FROM $wpdb->posts {$join} WHERE ID IN ('" . implode( "','", $sticky_posts ) . "') {$where}";

    $results = (array) $wpdb->get_col( $query );

    return $results;
}

/**
 * Query correction for exclude navigation "access denied" while using
 * "NEXT/PREVIOUS" links.
 */
 
function dal_get_next_post_where($where) {
   
    $dal_clauses = dal_get_query_restrictions('p'); 
    if( ! empty($where) && ! empty($dal_clauses['where']) ) {
        $where .= " AND ";
    }  
    $where .= $dal_clauses['where'];   

    return $where;
}

function dal_get_next_post_join($join) {

    $dal_clauses = dal_get_query_restrictions('p'); 

    $join .= $dal_clauses['join'];  

    return $join;
   
}

/**
 * Query correction for exclude comments of restricted posts.
 */
function dal_comments_clauses($clauses) {
    
    global $wpdb;
    
    $dal_clauses = dal_get_query_restrictions('dalp');   
    $clauses['join'] .= " JOIN {$wpdb->prefix}posts dalp ON dalp.ID = {$wpdb->prefix}comments.comment_post_ID ";
    $clauses['join'] .= $dal_clauses['join'];

    if( ! empty($clauses['where']) && ! empty($dal_clauses['where']) ) {
         $clauses['where'] .= " AND ";
    }  
    $clauses['where'] .= $dal_clauses['where'];

    return $clauses;

}

/**
 * Recalculation of number of comments taking into user access levels.
 * @global object $wpdb
 * @param integer $post_id post's ID
 * @return object comments count
 */
function dal_count_comments($post_id) {
    
    global $wpdb;

    $dal_clauses    = dal_get_query_restrictions('dalp');   
    
    $stats          = array();
    $where          = '';
    $join           = '';
    
    if ( $post_id > 0 )
        $where = $wpdb->prepare( " comment_post_ID = %d ", $post_id );
    
    $join .= " JOIN {$wpdb->prefix}posts dalp ON dalp.ID = {$wpdb->prefix}comments.comment_post_ID ";
    $join .= $dal_clauses['join'];

    if( ! empty( $where ) && ! empty($dal_clauses['where']) ) {
        $where .= " AND ";
    }  
    $where .= $dal_clauses['where'];    

    $count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} {$join} WHERE {$where} GROUP BY comment_approved", ARRAY_A );

    $total = 0;
    $approved = array('0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed');
    foreach ( (array) $count as $row ) {
        // Don't count post-trashed toward totals
        if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] )
            $total += $row['num_comments'];
        if ( isset( $approved[$row['comment_approved']] ) )
            $stats[$approved[$row['comment_approved']]] = $row['num_comments'];
    }

    $stats['total_comments'] = $total;
    foreach ( $approved as $key ) {
        if ( empty($stats[$key]) )
            $stats[$key] = 0;
    }

    $stats = (object) $stats;
    
    return $stats;
        
}

/**
 * Query correction to exclude rectricted taxonomy terms.
 */

function dal_terms_clauses($clauses, $taxonomies, $args) {

    global $wpdb;
    
    // If there is taxonomy terms to hide, correct $clauses query.
    
    if ( $args['hide_empty'] == 1 ) {
        
        $dal_clauses = dal_get_query_restrictions('dalp');   

        $clauses['fields']  .= ', count(t.term_id) as count';

        $clauses['join']    .= " LEFT JOIN {$wpdb->prefix}term_relationships daltr ON daltr.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN {$wpdb->prefix}posts dalp ON dalp.ID = daltr.object_id ";
        $clauses['join']    .= $dal_clauses['join'];

        if( ! empty($clauses['where']) && ! empty($dal_clauses['where']) ) {
             $clauses['where'] .= " AND ";
        }  
        $clauses['where']   .= $dal_clauses['where'];

        $clauses['orderby']  = 'GROUP BY t.term_id ' . $clauses['orderby'];
        
    }
        
    return $clauses;

}

/**
 * Query correction to exclude rectricted archive records (where hook).
 */
 
function dal_getarchives_where($where) {
   
    $dal_clauses = dal_get_query_restrictions(); 
    
    if( ! empty($where) && ! empty($dal_clauses['where']) ) {
        $where .= " AND ";
    }  
    $where .= $dal_clauses['where'];   

    return $where;
}

/**
 * Query correction to exclude rectricted archive records (join hook).
 */
function dal_getarchives_join($join) {

    $dal_clauses = dal_get_query_restrictions(); 

    $join .= $dal_clauses['join'];  

    return $join;
   
}


/** 
 * Activate hook functions.
 */

// Check if required functions available, if not include it.
if( ! function_exists('wp_get_current_user') )
    require_once(ABSPATH . "wp-includes/pluggable.php"); 

if ( ! current_user_can( 'dal_admin' ) && ( ! is_admin() || ( is_admin() && ! empty ($dal_cur_settings['dal_admin_restriction']) ) ) ) {

    // add filter to exclude restricted posts from list
    add_filter( 'posts_clauses', 'dal_intercept_query_clauses', 100 );	
    // add filter to restrict access to edit page
    add_action( 'admin_init', 'dal_restrict_edit_page', 1 );
    // add filter to recalculate posts
    add_filter( 'wp_count_posts', 'dal_wp_count_posts', 100, 3 );
    // correction sticky posts array
    add_filter( 'option_sticky_posts', 'dal_option_sticky_posts' );    
    
    // add filter to correct next/previous links
    add_filter( 'get_previous_post_where', 'dal_get_next_post_where', 10, 1 );
    add_filter( 'get_next_post_where', 'dal_get_next_post_where', 10, 1 );
    add_filter( 'get_previous_post_join', 'dal_get_next_post_join', 10, 1 );
    add_filter( 'get_next_post_join', 'dal_get_next_post_join', 10, 1 );

    // add filter to exclude comments of restricted posts
    add_filter( 'comments_clauses', 'dal_comments_clauses' );
    // add filter to recalculate comments
    add_filter( 'wp_count_comments', 'dal_count_comments', 100, 1 );
    
    // add filter to correct taxonomy terms list
    add_filter( 'terms_clauses', 'dal_terms_clauses', 100, 3 );

    // add filter to correct archive posts list
    add_filter( 'getarchives_where', 'dal_getarchives_where', 10, 1 );
    add_filter( 'getarchives_join', 'dal_getarchives_join', 10, 1 );

    
}
