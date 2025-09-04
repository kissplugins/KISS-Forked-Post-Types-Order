<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class CPTO 
        {
            var $current_post_type = null;
            
            var $functions;
            
            /**
            * Constructor
            * 
            */
            function __construct() 
                {

                    $this->functions    =   new CptoFunctions();
                   
                    $is_configured = get_option('CPT_configured');
                    if ($is_configured == '')
                        add_action( 'admin_notices', array ( $this, 'admin_configure_notices'));
                        
                    
                    add_filter('init',          array ( $this, 'on_init'));
                    add_filter('init',          array ( $this, 'compatibility'));
                    
                    
                    add_filter('pre_get_posts', array ( $this, 'pre_get_posts'));
                    add_filter('posts_orderby', array ( $this, 'posts_orderby'), 99, 2);                        
                }
                
            
            /**
            * Initialisation function
            *     
            */
            function init()
                {
                    
                    add_action( 'admin_init',                               array ( $this, 'admin_init'), 10 );
                    add_action( 'admin_menu',                               array ( $this, 'add_menu') );
                    
                    add_action( 'admin_menu',                               array ( $this, 'plugin_options_menu'));
                    
                    //load archive drag&drop sorting dependencies
                    add_action( 'admin_enqueue_scripts',                    array ( $this, 'archiveDragDrop'), 10 );
                    
                    add_action( 'wp_ajax_update-custom-type-order',         array ( $this, 'saveAjaxOrder') );
                    add_action( 'wp_ajax_update-custom-type-order-archive', array ( $this, 'saveArchiveAjaxOrder') );
                    add_action( 'wp_ajax_pto_filter_posts_by_category',     array ( $this, 'filterPostsByCategory') );
                
                }

            
            /**
            * On WordPress Init hook
            * This is being used to set the navigational links
            * 
            */
            function on_init()
                {
                    if( is_admin() )
                        return;
                    
                    
                    //check the navigation_sort_apply option
                    $options          =     $this->functions->get_options();
                    
                    $navigation_sort_apply   =  ( strval ( $options['navigation_sort_apply'] ) ===  "1")    ?   TRUE    :   FALSE;
                    
                    //Deprecated, rely on pto/navigation_sort_apply
                    $navigation_sort_apply   =  apply_filters('cpto/navigation_sort_apply', $navigation_sort_apply);
                    
                    $navigation_sort_apply   =  apply_filters('pto/navigation_sort_apply', $navigation_sort_apply);
                    
                    if( !   $navigation_sort_apply)
                        return;
                    
                    add_filter('get_previous_post_where',   array ( $this->functions, 'cpto_get_previous_post_where'),    99, 3);
                    add_filter('get_previous_post_sort',    array ( $this->functions, 'cpto_get_previous_post_sort')          );
                    add_filter('get_next_post_where',       array ( $this->functions, 'cpto_get_next_post_where'),        99, 3);
                    add_filter('get_next_post_sort',        array ( $this->functions, 'cpto_get_next_post_sort')              );
                
                }    
            
            
            /**
            * Compatibility with different 3rd codes
            * 
            */
            function compatibility()
                {
                    include_once( CPTPATH . '/include/class.compatibility.php');                    
                }
                
                
            /**
            * Pre get posts filter
            * 
            * @param mixed $query
            */
            function pre_get_posts($query)
                {
                        
                    //no need if it's admin interface
                    if (is_admin())
                        return $query;
                    
                    //check for ignore_custom_sort
                    if (isset($query->query_vars['ignore_custom_sort']) && $query->query_vars['ignore_custom_sort'] === TRUE)
                        return $query; 
                    
                    //ignore if  "nav_menu_item"
                    if(isset($query->query_vars)    &&  isset($query->query_vars['post_type'])   && $query->query_vars['post_type'] ==  "nav_menu_item")
                        return $query;    
                        
                    $options          =     $this->functions->get_options();
                    
                    //if auto sort    
                    if ( strval ( $options['autosort'] ) === "1")
                        {                                    
                            //remove the supresed filters;
                            if (isset($query->query['suppress_filters']))
                                $query->query['suppress_filters'] = FALSE;    
                            
                 
                            if (isset($query->query_vars['suppress_filters']))
                                $query->query_vars['suppress_filters'] = FALSE;
                 
                        }
                        
                    return $query;
                }
            
            
            
            /**
            * Posts OrderBy filter
            * 
            * @param mixed $orderBy
            * @param mixed $query
            */
            function posts_orderby($orderBy, $query) 
                {
                    global $wpdb;
                    
                    $options          =     $this->functions->get_options();
                    
                    //check for ignore_custom_sort
                    if (isset($query->query_vars['ignore_custom_sort']) && $query->query_vars['ignore_custom_sort'] === TRUE)
                        return $orderBy;  
                    
                    //ignore the bbpress
                    if (isset($query->query_vars['post_type']) && ((is_array($query->query_vars['post_type']) && in_array("reply", $query->query_vars['post_type'])) || ($query->query_vars['post_type'] == "reply")))
                        return $orderBy;
                    if (isset($query->query_vars['post_type']) && ((is_array($query->query_vars['post_type']) && in_array("topic", $query->query_vars['post_type'])) || ($query->query_vars['post_type'] == "topic")))
                        return $orderBy;
                        
                    //check for orderby GET paramether in which case return default data
                    if (isset($_GET['orderby']) && $_GET['orderby'] !==  'menu_order')
                        return $orderBy;
                        
                    //Avada orderby
                    if (isset($_GET['product_orderby']) && $_GET['product_orderby'] !==  'default')
                        return $orderBy;
                    
                    //check to ignore
                    /**
                    * Deprecated filter
                    * do not rely on this anymore
                    */
                    if (  apply_filters('pto/posts_orderby', $orderBy, $query )  === FALSE )
                        return $orderBy;
                        
                    $ignore =   apply_filters('pto/posts_orderby/ignore', FALSE, $orderBy, $query);
                    if( boolval( $ignore )  === TRUE )
                        return $orderBy;
                    
                    //ignore search
                    if( $query->is_search()  &&  isset( $query->query['s'] )   &&  ! empty ( $query->query['s'] ) )
                        return( $orderBy );
                    
                    if ( ( is_admin() &&  !wp_doing_ajax() )    ||  ( wp_doing_ajax() && isset($_REQUEST['action']) && $_REQUEST['action'] === 'query-attachments') )
                            {
                                
                                if ( strval ( $options['adminsort'] ) === "1" || ( wp_doing_ajax() && isset($_REQUEST['action']) && $_REQUEST['action'] === 'query-attachments') )
                                    {
                                        
                                        global $post;
                                        
                                        $order  =   apply_filters('pto/posts_order', '', $query);
                                        
                                        //temporary ignore ACF group and admin ajax calls, should be fixed within ACF plugin sometime later
                                        if (is_object($post) && $post->post_type    ===  "acf-field-group"
                                                ||  (defined('DOING_AJAX') && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'acf/') === 0))
                                            return $orderBy;
                                            
                                        if(isset($_POST['query'])   &&  isset($_POST['query']['post__in'])  &&  is_array($_POST['query']['post__in'])   &&  count($_POST['query']['post__in'])  >   0)
                                            return $orderBy;   
                                        
                                        $orderBy = "{$wpdb->posts}.menu_order {$order}, {$wpdb->posts}.post_date DESC";
                                    }
                            }
                        else
                            {   
                                $order  =   '';
                                if ( strval ( $options['use_query_ASC_DESC'] ) === "1" )
                                    $order  =   isset($query->query_vars['order'])  ?   " " . $query->query_vars['order'] : '';
                                
                                $order  =   apply_filters('pto/posts_order', $order, $query);
                                
                                if ( strval ( $options['autosort'] ) === "1")
                                    {
                                        if(trim($orderBy) == '')
                                            $orderBy = "{$wpdb->posts}.menu_order " . $order;
                                        else
                                            $orderBy = "{$wpdb->posts}.menu_order". $order .", " . $orderBy;
                                    }
                            }

                    return($orderBy);
                }
            
            
            
            /**
            * Show the Not Configured notice
            *     
            */
            function admin_configure_notices()
                {
                    if (isset($_POST['form_submit']))
                        return;
                        
                    ?>
                        <div class="error fade">
                            <p><strong><?php esc_html_e('Post Types Order must be configured. Please go to', 'post-types-order'); ?> <a href="<?php echo esc_attr( get_admin_url() ); ?>options-general.php?page=cpto-options"><?php esc_html_e('Settings Page', 'post-types-order'); ?></a> <?php esc_html_e('make the configuration and save', 'post-types-order'); ?></strong></p>
                        </div>
                    <?php
                }
            
            
            /**
            * Plugin options menu
            * 
            */
            function plugin_options_menu()
                {
                    
                    include (CPTPATH . '/include/class.options.php');
                    
                    $options_interface  =    new CptoOptionsInterface();
                    $options_interface->check_options_update();
                    
                    $hookID   =     add_options_page('Post Types Order', '<img class="menu_pto" src="'. CPTURL .'/images/menu-icon.png" alt="" /> Post Types Order', 'manage_options', 'cpto-options', array($options_interface, 'plugin_options_interface'));
                    add_action('admin_print_styles-' . $hookID ,    array($this, 'admin_options_print_styles'));
                }    
            
            
            /**
            * Admin options styles
            * 
            */
            function admin_options_print_styles()
                {
                    wp_register_style('pto-options', CPTURL . '/css/cpt-options.css', array(), PTO_VERSION );
                    wp_enqueue_style( 'pto-options'); 
                }
                
            
            /**
            * Load archive drag&drop sorting dependencies
            * 
            * Since version 1.8.8
            */
            function archiveDragDrop()
                {
                    $options          =     $this->functions->get_options();
                    
                                        
                    //if adminsort turned off no need to continue
                    if( strval ( $options['adminsort'] )           !==      '1')
                        return;
                    
                    $screen = get_current_screen();
                        
                    //check if the right interface
                    if( !isset( $screen->post_type )   ||  empty($screen->post_type))
                        return;
                    
                    if( isset( $screen->taxonomy ) && !empty($screen->taxonomy) )
                        return;
                    
                    if ( empty ( $options['allow_reorder_default_interfaces'][$screen->post_type] )     ||  ( isset ( $options['allow_reorder_default_interfaces'][$screen->post_type] )  &&  $options['allow_reorder_default_interfaces'][$screen->post_type]   !==      'yes' ) )
                        return;
                        
                    if ( wp_is_mobile() || ( function_exists( 'jetpack_is_mobile' ) && jetpack_is_mobile() ) )
                        return;
                                                                
                    //if is taxonomy term filter return
                    if(is_category()    ||  is_tax())
                        return;
                    
                    //return if use orderby columns
                    if (isset($_GET['orderby']) && $_GET['orderby'] !==  'menu_order')
                        return false;
                        
                    //return if post status filtering
                    if ( isset( $_GET['post_status'] )  &&  $_GET['post_status']    !== 'all' )
                        return false;
                        
                    //return if post author filtering
                    if (isset($_GET['author']))
                        return false;
                    
                    //load required dependencies
                    wp_enqueue_style('cpt-archive-dd', CPTURL . '/css/cpt-archive-dd.css');
                    
                    wp_enqueue_script('jquery');
                    wp_enqueue_script('jquery-ui-sortable');
                    wp_register_script('cpto', CPTURL . '/js/cpt.js', array('jquery')); 
                    
                    global $userdata;
                    
                    // Localize the script with new data
                    $CPTO_variables = array(
                                                'post_type'             =>  $screen->post_type,
                                                'archive_sort_nonce'    =>  wp_create_nonce( 'CPTO_archive_sort_nonce_' . $userdata->ID) 
                                            );
                    wp_localize_script( 'cpto', 'CPTO', $CPTO_variables );

                    // Enqueued script with localized data.
                    wp_enqueue_script( 'cpto' );   
                    
                }    
            

            /**
            * Admin init with enhanced security validation
            */
            function admin_init()
                {
                    if (isset($_GET['page']) && is_string($_GET['page'])) {
                        $page = sanitize_text_field(wp_unslash($_GET['page']));

                        // Security: Validate page parameter format
                        if (substr($page, 0, 17) === 'order-post-types-') {
                            $post_type_name = str_replace('order-post-types-', '', $page);

                            // Security: Validate post type name format
                            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $post_type_name)) {
                                wp_die(__('Invalid post type format.', 'post-types-order'));
                                return;
                            }

                            // Security: Check if post type exists
                            $this->current_post_type = get_post_type_object($post_type_name);
                            if ($this->current_post_type === null) {
                                wp_die(__('Invalid post type.', 'post-types-order'));
                                return;
                            }

                            // Security: Check user capabilities for this post type
                            if (!current_user_can($this->current_post_type->cap->edit_posts)) {
                                wp_die(__('Insufficient permissions for this post type.', 'post-types-order'));
                                return;
                            }
                        }
                    }
                }
            
            
            /**
            * Save the order set through separate interface
            * Enhanced with comprehensive security validation
            */
            function saveAjaxOrder()
                {
                    // Security: Check if user is logged in and has proper capabilities
                    if (!is_user_logged_in()) {
                        wp_send_json_error(array('message' => __('Authentication required.', 'post-types-order')));
                        return;
                    }

                    // Security: Check user capabilities
                    if (!current_user_can('edit_posts')) {
                        wp_send_json_error(array('message' => __('Insufficient permissions.', 'post-types-order')));
                        return;
                    }

                    set_time_limit(600);

                    global $wpdb;

                    // Security: Validate and sanitize nonce
                    $nonce = isset($_POST['interface_sort_nonce']) ? sanitize_text_field(wp_unslash($_POST['interface_sort_nonce'])) : '';
                    if (empty($nonce) || !wp_verify_nonce($nonce, 'interface_sort_nonce')) {
                        wp_send_json_error(array('message' => __('Security verification failed.', 'post-types-order')));
                        return;
                    }

                    // Security: Validate and sanitize order data
                    if (!isset($_POST['order']) || empty($_POST['order'])) {
                        wp_send_json_error(array('message' => __('No order data provided.', 'post-types-order')));
                        return;
                    }

                    $order_raw = wp_unslash($_POST['order']);
                    if (!is_string($order_raw)) {
                        wp_send_json_error(array('message' => __('Invalid order data format.', 'post-types-order')));
                        return;
                    }

                    parse_str(sanitize_text_field($order_raw), $data);

                    // Security: Validate parsed data
                    if (!is_array($data) || empty($data)) {
                        wp_send_json_error(array('message' => __('Invalid order data.', 'post-types-order')));
                        return;
                    }

                    // Security: Validate and sanitize category filter
                    $category_filter = '';
                    if (isset($_POST['category_filter'])) {
                        $category_filter = sanitize_text_field(wp_unslash($_POST['category_filter']));
                        // Validate category filter format (taxonomy:term_id)
                        if (!empty($category_filter) && !preg_match('/^[a-zA-Z0-9_-]+:[0-9]+$/', $category_filter)) {
                            wp_send_json_error(array('message' => __('Invalid category filter format.', 'post-types-order')));
                            return;
                        }
                    }

                    // Process validated data
                    if (!empty($category_filter)) {
                        $this->saveFilteredAjaxOrder($data, $category_filter);
                    } else {
                        // Standard order saving (no filter applied)
                        $this->processOrderData($data);
                    }

                    //trigger action completed
                    do_action('PTO/order_update_complete');

                    CptoFunctions::site_cache_clear();

                    wp_send_json_success(array('message' => __('Order updated successfully.', 'post-types-order')));
                }

            /**
            * Process order data with enhanced validation
            *
            * @param array $data The order data to process
            */
            private function processOrderData($data)
                {
                    global $wpdb;

                    foreach($data as $key => $values) {
                        // Security: Validate key format
                        if (!is_string($key) || (!$key === 'item' && !preg_match('/^item_[0-9]+$/', $key))) {
                            continue; // Skip invalid keys
                        }

                        if (!is_array($values)) {
                            continue; // Skip invalid values
                        }

                        if ($key === 'item') {
                            foreach($values as $position => $id) {
                                // Security: Validate position and ID
                                $position = intval($position);
                                $id = intval($id);

                                if ($position < 0 || $id <= 0) {
                                    continue; // Skip invalid data
                                }

                                // Security: Verify post exists and user can edit it
                                $post = get_post($id);
                                if (!$post || !current_user_can('edit_post', $id)) {
                                    continue; // Skip posts user can't edit
                                }

                                $data_update = array('menu_order' => $position);

                                //Deprecated, rely on pto/save-ajax-order
                                $data_update = apply_filters('post-types-order_save-ajax-order', $data_update, $key, $id);
                                $data_update = apply_filters('pto/save-ajax-order', $data_update, $key, $id);

                                $wpdb->update($wpdb->posts, $data_update, array('ID' => $id));
                            }
                        } else {
                            // Handle hierarchical posts
                            $parent_id = intval(str_replace('item_', '', $key));

                            // Security: Validate parent post
                            if ($parent_id <= 0) {
                                continue;
                            }

                            $parent_post = get_post($parent_id);
                            if (!$parent_post || !current_user_can('edit_post', $parent_id)) {
                                continue;
                            }

                            foreach($values as $position => $id) {
                                $position = intval($position);
                                $id = intval($id);

                                if ($position < 0 || $id <= 0) {
                                    continue;
                                }

                                // Security: Verify child post exists and user can edit it
                                $post = get_post($id);
                                if (!$post || !current_user_can('edit_post', $id)) {
                                    continue;
                                }

                                $data_update = array('menu_order' => $position, 'post_parent' => $parent_id);

                                //Deprecated, rely on pto/save-ajax-order
                                $data_update = apply_filters('post-types-order_save-ajax-order', $data_update, $key, $id);
                                $data_update = apply_filters('pto/save-ajax-order', $data_update, $key, $id);

                                $wpdb->update($wpdb->posts, $data_update, array('ID' => $id));
                            }
                        }
                    }

                    //trigger action completed
                    do_action('PTO/order_update_complete');

                    CptoFunctions::site_cache_clear();
                }


            /**
            * Save order when category filter is applied
            *
            * @param array $data
            * @param string $category_filter
            */
            function saveFilteredAjaxOrder($data, $category_filter)
                {
                    global $wpdb;

                    // Parse category filter
                    $filter_parts = explode(':', $category_filter);
                    if (count($filter_parts) !== 2) {
                        return; // Invalid filter format
                    }

                    $taxonomy = $filter_parts[0];
                    $term_id = intval($filter_parts[1]);

                    // Get current post type from the interface
                    $current_post_type = $this->current_post_type ? $this->current_post_type->name : 'post';

                    // Get all posts in this post type ordered by current menu_order
                    $all_posts = get_posts(array(
                        'post_type' => $current_post_type,
                        'posts_per_page' => -1,
                        'post_status' => 'any',
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'fields' => 'ids'
                    ));

                    // Get posts that were in the filtered view (the ones being reordered)
                    $filtered_post_ids = array();
                    foreach($data as $key => $values) {
                        if ($key === 'item') {
                            foreach($values as $position => $id) {
                                $filtered_post_ids[] = (int)$id;
                            }
                        }
                    }

                    // Create a new order array that preserves non-filtered posts
                    $new_order = array();
                    $filtered_posts_new_order = array();

                    // Extract the new order for filtered posts
                    foreach($data as $key => $values) {
                        if ($key === 'item') {
                            foreach($values as $position => $id) {
                                $filtered_posts_new_order[] = (int)$id;
                            }
                        }
                    }

                    // Build the final order: preserve positions of non-filtered posts,
                    // insert filtered posts in their new order
                    $filtered_index = 0;
                    foreach($all_posts as $post_id) {
                        if (in_array($post_id, $filtered_post_ids)) {
                            // This post was in the filter, use its new position
                            if ($filtered_index < count($filtered_posts_new_order)) {
                                $new_order[] = $filtered_posts_new_order[$filtered_index];
                                $filtered_index++;
                            }
                        } else {
                            // This post was not in the filter, keep its relative position
                            $new_order[] = $post_id;
                        }
                    }

                    // Update menu_order for all posts
                    foreach($new_order as $position => $post_id) {
                        $data_update = array('menu_order' => $position);

                        //Deprecated, rely on pto/save-ajax-order
                        $data_update = apply_filters('post-types-order_save-ajax-order', $data_update, 'item', $post_id);

                        $data_update = apply_filters('pto/save-ajax-order', $data_update, 'item', $post_id);

                        $wpdb->update($wpdb->posts, $data_update, array('ID' => $post_id));
                    }
                }


            /**
            * Save the order set through the Archive
            * Enhanced with comprehensive security validation
            */
            function saveArchiveAjaxOrder()
                {
                    // Security: Check if user is logged in and has proper capabilities
                    if (!is_user_logged_in()) {
                        wp_send_json_error(array('message' => __('Authentication required.', 'post-types-order')));
                        return;
                    }

                    // Security: Check user capabilities
                    if (!current_user_can('edit_posts')) {
                        wp_send_json_error(array('message' => __('Insufficient permissions.', 'post-types-order')));
                        return;
                    }

                    set_time_limit(600);

                    global $wpdb, $userdata;

                    // Security: Validate and sanitize post type
                    if (!isset($_POST['post_type']) || empty($_POST['post_type'])) {
                        wp_send_json_error(array('message' => __('Post type is required.', 'post-types-order')));
                        return;
                    }

                    $post_type = preg_replace('/[^a-zA-Z0-9_\-]/', '', sanitize_text_field(wp_unslash($_POST['post_type'])));
                    if (empty($post_type) || !post_type_exists($post_type)) {
                        wp_send_json_error(array('message' => __('Invalid post type.', 'post-types-order')));
                        return;
                    }

                    // Security: Validate and sanitize paged parameter
                    $paged = 1;
                    if (isset($_POST['paged'])) {
                        $paged = filter_var(sanitize_text_field(wp_unslash($_POST['paged'])), FILTER_SANITIZE_NUMBER_INT);
                        $paged = max(1, intval($paged)); // Ensure minimum value of 1
                    }

                    // Security: Validate and sanitize nonce
                    $nonce = isset($_POST['archive_sort_nonce']) ? sanitize_text_field(wp_unslash($_POST['archive_sort_nonce'])) : '';
                    if (empty($nonce) || !wp_verify_nonce($nonce, 'CPTO_archive_sort_nonce_' . $userdata->ID)) {
                        wp_send_json_error(array('message' => __('Security verification failed.', 'post-types-order')));
                        return;
                    }

                    // Security: Validate and sanitize order data
                    if (!isset($_POST['order']) || empty($_POST['order'])) {
                        wp_send_json_error(array('message' => __('No order data provided.', 'post-types-order')));
                        return;
                    }

                    $order_raw = wp_unslash($_POST['order']);
                    if (!is_string($order_raw)) {
                        wp_send_json_error(array('message' => __('Invalid order data format.', 'post-types-order')));
                        return;
                    }

                    parse_str(sanitize_text_field($order_raw), $data);
                    
                    // Security: Validate parsed data
                    if (!is_array($data) || empty($data)) {
                        wp_send_json_error(array('message' => __('Invalid order data.', 'post-types-order')));
                        return;
                    }
                    
                    //retrieve a list of all objects
                    $mysql_query    =   $wpdb->prepare("SELECT ID FROM ". $wpdb->posts ." 
                                                            WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future', 'inherit')
                                                            ORDER BY menu_order, post_date DESC", $post_type);
                    $results        =   $wpdb->get_results($mysql_query);
                    
                    if (!is_array($results)    ||  count($results)    <   1)
                        die();
                    
                    //create the list of ID's
                    $objects_ids    =   array();
                    foreach($results    as  $result)
                        {
                            $objects_ids[]  =   (int)$result->ID;   
                        }
                    
                    global $userdata;
                    if ( $post_type === 'attachment' )
                        $objects_per_page   =   get_user_meta( $userdata->ID , 'upload_per_page', TRUE );
                        else
                        $objects_per_page   =   get_user_meta( $userdata->ID ,'edit_' .  $post_type  .'_per_page', TRUE );
                    $objects_per_page   =   apply_filters( "edit_{$post_type}_per_page", $objects_per_page );
                    if(empty($objects_per_page))
                        $objects_per_page   =   20;
                    
                    $edit_start_at      =   $paged  *   $objects_per_page   -   $objects_per_page;
                    $index              =   0;
                    for($i  =   $edit_start_at; $i  <   ($edit_start_at +   $objects_per_page); $i++)
                        {
                            if(!isset($objects_ids[$i]))
                                break;
                                
                            $objects_ids[$i]    =   (int)$data['post'][$index];
                            $index++;
                        }
                    
                    //update the menu_order within database
                    foreach( $objects_ids as $menu_order   =>  $id ) 
                        {
                            $data = array(
                                            'menu_order' => $menu_order
                                            );
                            
                            //Deprecated, rely on pto/save-ajax-order
                            $data = apply_filters('post-types-order_save-ajax-order', $data, $menu_order, $id);
                            
                            $data = apply_filters('pto/save-ajax-order', $data, $menu_order, $id);
                            
                            $wpdb->update( $wpdb->posts, $data, array('ID' => $id) );
                            
                            clean_post_cache( $id );
                        }
                        
                    //trigger action completed
                    do_action('PTO/order_update_complete');
                    
                    CptoFunctions::site_cache_clear();                
                }


            /**
            * Filter posts by category via AJAX
            * Enhanced with comprehensive security validation
            */
            function filterPostsByCategory()
                {
                    // Security: Check if user is logged in and has proper capabilities
                    if (!is_user_logged_in()) {
                        wp_send_json_error(array('message' => __('Authentication required.', 'post-types-order')));
                        return;
                    }

                    // Security: Check user capabilities
                    if (!current_user_can('edit_posts')) {
                        wp_send_json_error(array('message' => __('Insufficient permissions.', 'post-types-order')));
                        return;
                    }

                    // Security: Validate and sanitize nonce
                    $nonce = isset($_POST['filter_nonce']) ? sanitize_text_field(wp_unslash($_POST['filter_nonce'])) : '';
                    if (empty($nonce) || !wp_verify_nonce($nonce, 'interface_sort_nonce')) {
                        wp_send_json_error(array('message' => __('Security verification failed.', 'post-types-order')));
                        return;
                    }

                    // Security: Validate and sanitize post type
                    if (!isset($_POST['post_type']) || empty($_POST['post_type'])) {
                        wp_send_json_error(array('message' => __('Post type is required.', 'post-types-order')));
                        return;
                    }

                    $post_type = sanitize_text_field(wp_unslash($_POST['post_type']));
                    if (!post_type_exists($post_type)) {
                        wp_send_json_error(array('message' => __('Invalid post type.', 'post-types-order')));
                        return;
                    }

                    // Security: Check if user can edit this post type
                    $post_type_object = get_post_type_object($post_type);
                    if (!$post_type_object || !current_user_can($post_type_object->cap->edit_posts)) {
                        wp_send_json_error(array('message' => __('Insufficient permissions for this post type.', 'post-types-order')));
                        return;
                    }

                    // Security: Validate and sanitize category filter
                    $category_filter = '';
                    if (isset($_POST['category_filter'])) {
                        $category_filter = sanitize_text_field(wp_unslash($_POST['category_filter']));
                        // Validate category filter format (taxonomy:term_id)
                        if (!empty($category_filter) && !preg_match('/^[a-zA-Z0-9_-]+:[0-9]+$/', $category_filter)) {
                            wp_send_json_error(array('message' => __('Invalid category filter format.', 'post-types-order')));
                            return;
                        }

                        // Additional validation: check if taxonomy exists and term exists
                        if (!empty($category_filter)) {
                            $filter_parts = explode(':', $category_filter);
                            $taxonomy = $filter_parts[0];
                            $term_id = intval($filter_parts[1]);

                            if (!taxonomy_exists($taxonomy)) {
                                wp_send_json_error(array('message' => __('Invalid taxonomy.', 'post-types-order')));
                                return;
                            }

                            if (!term_exists($term_id, $taxonomy)) {
                                wp_send_json_error(array('message' => __('Invalid term.', 'post-types-order')));
                                return;
                            }
                        }
                    }

                    // Get pagination parameters
                    $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
                    $posts_per_page = 50; // Match interface pagination

                    // Build query args with pagination
                    $args = array(
                        'post_type' => $post_type,
                        'posts_per_page' => $posts_per_page,
                        'paged' => $paged,
                        'post_status' => 'any',
                        'orderby' => array(
                            'menu_order' => 'ASC',
                            'post_date' => 'DESC'
                        )
                    );

                    // Add taxonomy filter if specified
                    if (!empty($category_filter)) {
                        $filter_parts = explode(':', $category_filter);
                        if (count($filter_parts) === 2) {
                            $taxonomy = $filter_parts[0];
                            $term_id = intval($filter_parts[1]);

                            $args['tax_query'] = array(
                                array(
                                    'taxonomy' => $taxonomy,
                                    'field' => 'term_id',
                                    'terms' => $term_id,
                                )
                            );
                        }
                    }

                    // Allow customisation of the query
                    $args = apply_filters('pto/interface/query/args', $args);

                    $the_query = new WP_Query($args);
                    $posts = $the_query->posts;

                    // Generate HTML using the walker
                    $output = '';
                    if (!empty($posts)) {
                        include_once(CPTPATH . '/include/class.walkers.php');
                        $walker = new Post_Types_Order_Walker;
                        $walker_args = array($posts, -1, array());
                        $output = call_user_func_array(array(&$walker, 'walk'), $walker_args);
                    }

                    $message = empty($category_filter)
                        ? __('Showing all posts.', 'post-types-order')
                        : sprintf(__('Showing posts filtered by category. Found %d posts.', 'post-types-order'), $the_query->found_posts);

                    wp_send_json_success(array(
                        'html' => $output,
                        'message' => $message,
                        'count' => count($posts),
                        'pagination' => array(
                            'current_page' => $paged,
                            'total_pages' => $the_query->max_num_pages,
                            'total_posts' => $the_query->found_posts,
                            'posts_per_page' => $posts_per_page
                        )
                    ));
                }


            /**
            * Add the dashboard menus
            *
            */
            function add_menu() 
                {
                    
                    include_once ( CPTPATH . '/include/class.interface.php' );
                    include_once ( CPTPATH . '/include/class.walkers.php' );
                    
                    global $userdata;
                    //put a menu for all custom_type
                    $post_types = get_post_types();
                    
                    $options          =     $this->functions->get_options();
                    //get the required user capability
                    $capability = '';
                    if(isset($options['capability']) && !empty($options['capability']))
                        {
                            $capability = $options['capability'];
                        }
                    else if (is_numeric($options['level']))
                        {
                            $capability = $this->functions->userdata_get_user_level();
                        }
                        else
                            {
                                $capability = 'manage_options';  
                            }
                    
                    $PTO_Interface =    new PTO_Interface();
                    
                    foreach( $post_types as $post_type_name ) 
                        {
                            if ($post_type_name === 'page')
                                continue;
                                
                            //ignore bbpress
                            if ($post_type_name === 'reply' || $post_type_name === 'topic')
                                continue;
                            
                            if(is_post_type_hierarchical($post_type_name))
                                continue;
                                
                            $post_type_data = get_post_type_object( $post_type_name );
                            if($post_type_data->show_ui === FALSE)
                                continue;
                                
                            if(isset($options['show_reorder_interfaces'][$post_type_name]) && $options['show_reorder_interfaces'][$post_type_name] !== 'show')
                                continue;
                                
                            $required_capability = apply_filters('pto/edit_capability', $capability, $post_type_name);
                            
                            if ( $post_type_name == 'post' )
                                $hookID   = add_submenu_page('edit.php', __('KISS Re-Order', 'post-types-order'), __('KISS Re-Order', 'post-types-order'), $required_capability, 'order-post-types-'.$post_type_name, array( $PTO_Interface, 'sort_page') );
                            elseif ($post_type_name == 'attachment')
                                $hookID   = add_submenu_page('upload.php', __('KISS Re-Order', 'post-types-order'), __('KISS Re-Order', 'post-types-order'), $required_capability, 'order-post-types-'.$post_type_name, array( $PTO_Interface, 'sort_page') );
                            else
                                {
                                    $hookID   = add_submenu_page('edit.php?post_type='.$post_type_name, __('KISS Re-Order', 'post-types-order'), __('KISS Re-Order', 'post-types-order'), $required_capability, 'order-post-types-'.$post_type_name, array( $PTO_Interface, 'sort_page') );
                                }
                            
                            add_action('admin_print_styles-' . $hookID ,    array($this, 'admin_reorder_styles'));
                        }
                }
                
            
            /**
            * Admin reorder print styles
            * 
            */
            function admin_reorder_styles() 
                {
                    
                    if ( $this->current_post_type != null ) 
                        {
                            wp_enqueue_script('jQuery');
                            wp_enqueue_script('jquery-ui-sortable');
                        }
                        
                    wp_register_style('CPTStyleSheets', CPTURL . '/css/cpt.css', array(), PTO_VERSION );
                    wp_enqueue_style( 'CPTStyleSheets');
                }
            
            
        }