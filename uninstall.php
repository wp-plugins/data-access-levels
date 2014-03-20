<?php

/* 
 * Delete plugin options.
 */

if( ! defined('WP_UNINSTALL_PLUGIN') )
    exit();

delete_option('dal_post_types');
delete_option('dal_settings');
