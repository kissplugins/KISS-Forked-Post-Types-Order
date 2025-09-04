<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    
    class PTO_Interface 
        {
            
            var $functions;
            var $CPTO;
            
            /**
            * Constructor
            * 
            */
            function __construct() 
                {

                    $this->functions    =   new CptoFunctions();
                    
                    global $CPTO;
                    $this->CPTO         =   $CPTO;
                    
                }
                
                
            /**
            * Sort interfaces
            * 
            */
            function sort_page() 
                {
                    
                    $options          =     $this->functions->get_options();
                    
                    ?>
                    <div id="cpto" class="wrap">
                        <div class="icon32" id="icon-edit"><br></div>
                        <h2><?php echo esc_html( $this->CPTO->current_post_type->labels->singular_name . ' -  '. esc_html__('KISS Re-Order', 'post-types-order') ); ?></h2>

                        <div id="ajax-response"></div>
                        
                        <noscript>
                            <div class="error message">
                                <p><?php esc_html_e('This plugin can\'t work without javascript, because it\'s use drag and drop and AJAX.', 'post-types-order'); ?></p>
                            </div>
                        </noscript>
                        
                        <p>&nbsp;</p>

                        <!-- Category Filter Section with Collapsible Debug -->
                        <?php
                        // DEBUG: Collapsible debugging output
                        echo '<div style="background: #fff3cd; border: 1px solid #ffeaa7; margin: 10px 0; border-radius: 4px;">';
                        echo '<div style="padding: 8px 10px; cursor: pointer; user-select: none;" onclick="toggleDebugPanel()">';
                        echo '<span id="debug-caret" style="display: inline-block; transition: transform 0.2s;">▶</span> ';
                        echo '<strong style="color: #856404;">🐛 Debug Information (Click to expand)</strong>';
                        echo '</div>';
                        echo '<div id="debug-content" style="display: none; padding: 0 10px 10px 10px; border-top: 1px solid #ffeaa7;">';
                        echo '<h4 style="margin: 10px 0; color: #856404;">Category Filter Debug Information</h4>';

                        // Add the toggle script
                        echo '<script>
                        function toggleDebugPanel() {
                            var content = document.getElementById("debug-content");
                            var caret = document.getElementById("debug-caret");
                            if (content.style.display === "none") {
                                content.style.display = "block";
                                caret.style.transform = "rotate(90deg)";
                            } else {
                                content.style.display = "none";
                                caret.style.transform = "rotate(0deg)";
                            }
                        }
                        </script>';

                        // Get current post type info
                        $current_post_type = $this->CPTO->current_post_type;
                        echo '<p><strong>Current Post Type:</strong> ' . esc_html($current_post_type->name) . ' (' . esc_html($current_post_type->label) . ')</p>';

                        // Get taxonomies for current post type
                        $taxonomies = get_object_taxonomies($current_post_type->name, 'objects');
                        echo '<p><strong>Total Taxonomies Found:</strong> ' . count($taxonomies) . '</p>';

                        if (!empty($taxonomies)) {
                            echo '<ul>';
                            foreach ($taxonomies as $taxonomy) {
                                echo '<li><strong>' . esc_html($taxonomy->name) . '</strong> (' . esc_html($taxonomy->label) . ') - Hierarchical: ' . ($taxonomy->hierarchical ? 'YES' : 'NO');

                                if ($taxonomy->hierarchical) {
                                    $terms = get_terms(array(
                                        'taxonomy' => $taxonomy->name,
                                        'hide_empty' => false,
                                    ));

                                    if (is_wp_error($terms)) {
                                        echo ' - ERROR: ' . $terms->get_error_message();
                                    } else {
                                        echo ' - Terms: ' . count($terms);
                                        if (!empty($terms)) {
                                            $term_names = array();
                                            foreach ($terms as $term) {
                                                // Get accurate count for this specific post type
                                                $post_count_query = new WP_Query(array(
                                                    'post_type' => $current_post_type->name,
                                                    'post_status' => 'any',
                                                    'posts_per_page' => -1,
                                                    'fields' => 'ids',
                                                    'tax_query' => array(
                                                        array(
                                                            'taxonomy' => $taxonomy->name,
                                                            'field' => 'term_id',
                                                            'terms' => $term->term_id,
                                                        ),
                                                    ),
                                                ));
                                                $post_count = $post_count_query->found_posts;
                                                wp_reset_postdata();

                                                $term_names[] = $term->name . '(' . $post_count . ')';
                                            }
                                            echo ' [' . implode(', ', array_slice($term_names, 0, 5)) . (count($term_names) > 5 ? '...' : '') . ']';
                                        }
                                    }
                                }
                                echo '</li>';
                            }
                            echo '</ul>';
                        }

                        $has_hierarchical_taxonomies = false;
                        $taxonomy_options = '';
                        $debug_info = array();

                        foreach ($taxonomies as $taxonomy) {
                            // Skip non-hierarchical taxonomies (tags) and focus on categories
                            if (!$taxonomy->hierarchical) {
                                $debug_info[] = "Skipped {$taxonomy->name} (not hierarchical)";
                                continue;
                            }

                            $terms = get_terms(array(
                                'taxonomy' => $taxonomy->name,
                                'hide_empty' => false,
                            ));

                            if (is_wp_error($terms)) {
                                $debug_info[] = "Error getting terms for {$taxonomy->name}: " . $terms->get_error_message();
                                continue;
                            }

                            if (!empty($terms)) {
                                $has_hierarchical_taxonomies = true;
                                $taxonomy_options .= '<optgroup label="' . esc_attr($taxonomy->label) . '">';
                                foreach ($terms as $term) {
                                    // Get accurate count for this specific post type
                                    $post_count_query = new WP_Query(array(
                                        'post_type' => $current_post_type->name,
                                        'post_status' => 'any',
                                        'posts_per_page' => -1,
                                        'fields' => 'ids',
                                        'tax_query' => array(
                                            array(
                                                'taxonomy' => $taxonomy->name,
                                                'field' => 'term_id',
                                                'terms' => $term->term_id,
                                            ),
                                        ),
                                    ));
                                    $post_count = $post_count_query->found_posts;
                                    wp_reset_postdata();

                                    $taxonomy_options .= '<option value="' . esc_attr($taxonomy->name . ':' . $term->term_id) . '">' . esc_html($term->name) . ' (' . $post_count . ')</option>';
                                }
                                $taxonomy_options .= '</optgroup>';
                                $debug_info[] = "Added {$taxonomy->name} with " . count($terms) . " terms";
                            } else {
                                $debug_info[] = "No terms found for {$taxonomy->name}";
                            }
                        }

                        echo '<p><strong>Processing Results:</strong></p>';
                        echo '<ul>';
                        foreach ($debug_info as $info) {
                            echo '<li>' . esc_html($info) . '</li>';
                        }
                        echo '</ul>';

                        echo '<p><strong>Will Show Category Filter:</strong> ' . ($has_hierarchical_taxonomies ? '<span style="color: green;">YES</span>' : '<span style="color: red;">NO</span>') . '</p>';
                        echo '</div>'; // Close debug-content div
                        echo '</div>'; // Close main debug container div

                        // Only show the filter if there are hierarchical taxonomies with terms
                        if ($has_hierarchical_taxonomies) :
                        ?>
                        <div id="category-filter-section" style="margin-bottom: 20px; border: 2px solid #00a32a; background: #f0f6fc; padding: 15px; border-radius: 4px;">
                            <div style="background: #00a32a; color: white; padding: 5px 10px; margin: -15px -15px 10px -15px; border-radius: 2px 2px 0 0;">
                                ✅ Category Filter Active
                            </div>
                            <div class="alignleft actions">
                                <label for="category-filter" style="margin-right: 10px; font-weight: bold;"><?php esc_html_e('Filter by Category:', 'post-types-order'); ?></label>
                                <select id="category-filter" name="category-filter" style="margin-right: 10px;">
                                    <option value=""><?php esc_html_e('All Categories', 'post-types-order'); ?></option>
                                    <?php echo $taxonomy_options; ?>
                                </select>
                                <button type="button" id="apply-category-filter" class="button"><?php esc_html_e('Filter', 'post-types-order'); ?></button>
                                <button type="button" id="clear-category-filter" class="button" style="display: none; margin-left: 5px;"><?php esc_html_e('Clear Filter', 'post-types-order'); ?></button>
                            </div>
                            <div class="clear"></div>
                        </div>
                        <?php else : ?>
                        <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 4px; color: #721c24;">
                            <strong>❌ Category Filter Hidden</strong><br>
                            No hierarchical taxonomies with terms found for post type: <strong><?php echo esc_html($current_post_type->name); ?></strong><br>
                            <small>The category filter only appears when there are categories (hierarchical taxonomies) with terms available.</small>
                        </div>
                        <?php endif; ?>

                        <div id="order-objects">
           
                            <div id="nav-menu-header">
                                <div class="major-publishing-actions">

                                        
                                        <div class="alignright actions">
                                            <p class="actions">
              
                                                <span class="img_spacer">&nbsp;
                                                    <img alt="" src="<?php echo CPTURL ?>/images/wpspin_light.gif" class="waiting pto_ajax_loading" style="display: none;">
                                                </span>
                                                <a href="javascript:;" class="save-order button-primary"><?php _e('Update', 'atto') ?></a>
                                            </p>
                                        </div>
                                        
                                        <div class="clear"></div>

                                </div><!-- END .major-publishing-actions -->
                            </div><!-- END #nav-menu-header -->
           
            
                            <div id="post-body"> 
                                <ul id="sortable" class="sortable ui-sortable">
                                
                                    <?php $this->list_pages('hide_empty=0&title_li=&post_type=' . $this->CPTO->current_post_type->name ); ?>
                                    
                                </ul>
                            </div>
                            
                            <div id="nav-menu-footer">
                                <div class="major-publishing-actions">
                                        <div class="alignright actions">
                                            <img alt="" src="<?php echo CPTURL ?>/images/wpspin_light.gif" class="waiting pto_ajax_loading" style="display: none;">
                                            <a href="javascript:;" class="save-order button-primary"><?php _e('Update', 'atto') ?></a>
                                        </div>
                                        
                                        <div class="clear"></div>

                                </div><!-- END .major-publishing-actions -->
                            </div><!-- END #nav-menu-header -->
             
                        </div>
                                       
                        <?php wp_nonce_field( 'interface_sort_nonce', 'interface_sort_nonce' ); ?>
                        
                        <script type="text/javascript">
                            // Debug logging for category filter
                            console.log('🐛 Post Types Order - Category Filter Debug');
                            console.log('Current URL:', window.location.href);
                            console.log('Post Type:', '<?php echo esc_js($this->CPTO->current_post_type->name); ?>');

                            jQuery(document).ready(function() {
                                console.log('🐛 jQuery ready - checking for category filter elements');
                                console.log('Category filter section exists:', jQuery('#category-filter-section').length > 0);
                                console.log('Category filter dropdown exists:', jQuery('#category-filter').length > 0);
                                console.log('Apply filter button exists:', jQuery('#apply-category-filter').length > 0);

                                if (jQuery('#category-filter').length > 0) {
                                    console.log('🐛 Category filter dropdown found!');
                                    console.log('Options count:', jQuery('#category-filter option').length);
                                    jQuery('#category-filter option').each(function(index) {
                                        console.log('Option ' + index + ':', jQuery(this).val(), jQuery(this).text());
                                    });
                                } else {
                                    console.log('❌ Category filter dropdown NOT found');
                                }

                                // Initialize sortable
                                function initSortable() {
                                    jQuery("#sortable").sortable({
                                        'tolerance':'intersect',
                                        'cursor':'pointer',
                                        'items':'li',
                                        'placeholder':'placeholder',
                                        'nested': 'ul'
                                    });
                                    jQuery("#sortable").disableSelection();
                                }

                                // Initial sortable setup
                                initSortable();

                                // Save order functionality
                                jQuery(".save-order").bind( "click", function() {
                                    jQuery(this).parent().find('img').show();
                                    jQuery("html, body").animate({ scrollTop: 0 }, "fast");

                                    var orderData = jQuery("#sortable").sortable("serialize");
                                    var categoryFilter = jQuery("#category-filter").val();

                                    jQuery.post( ajaxurl, {
                                        action:'update-custom-type-order',
                                        order: orderData,
                                        category_filter: categoryFilter,
                                        'interface_sort_nonce' : jQuery('#interface_sort_nonce').val()
                                    }, function() {
                                        jQuery("#ajax-response").html('<div class="message updated fade"><p><?php esc_html_e('Items Order Updated', 'post-types-order') ?></p></div>');
                                        jQuery("#ajax-response div").delay(3000).hide("slow");
                                        jQuery('img.pto_ajax_loading').hide();
                                    });
                                });

                                // Category filter functionality
                                jQuery("#apply-category-filter").click(function() {
                                    var categoryFilter = jQuery("#category-filter").val();
                                    var postType = '<?php echo esc_js($this->CPTO->current_post_type->name); ?>';

                                    jQuery('#ajax-response').html('<div class="notice notice-info"><p><?php esc_html_e('Loading filtered posts...', 'post-types-order'); ?></p></div>');

                                    jQuery.post(ajaxurl, {
                                        action: 'pto_filter_posts_by_category',
                                        post_type: postType,
                                        category_filter: categoryFilter,
                                        'filter_nonce': jQuery('#interface_sort_nonce').val()
                                    }, function(response) {
                                        if (response.success) {
                                            jQuery("#sortable").html(response.data.html);
                                            initSortable(); // Reinitialize sortable for new content

                                            if (categoryFilter) {
                                                jQuery("#clear-category-filter").show();
                                                jQuery("#apply-category-filter").text('<?php esc_html_e('Refresh Filter', 'post-types-order'); ?>');
                                            }

                                            jQuery('#ajax-response').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                                            jQuery("#ajax-response div").delay(3000).hide("slow");
                                        } else {
                                            jQuery('#ajax-response').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                                        }
                                    }).fail(function() {
                                        jQuery('#ajax-response').html('<div class="notice notice-error"><p><?php esc_html_e('Error loading filtered posts.', 'post-types-order'); ?></p></div>');
                                    });
                                });

                                // Clear filter functionality
                                jQuery("#clear-category-filter").click(function() {
                                    jQuery("#category-filter").val('');
                                    jQuery("#apply-category-filter").click();
                                    jQuery(this).hide();
                                    jQuery("#apply-category-filter").text('<?php esc_html_e('Filter', 'post-types-order'); ?>');
                                });
                            });
                        </script>
                        
                        
                        
                        
                    </div>
                    <?php
                }

                
            /**
            * List pages
            * 
            * @param mixed $args
            */
            function list_pages($args = '') 
                {
                    $defaults = array(
                        'depth'             => -1, 
                        'date_format'       => get_option('date_format'),
                        'child_of'          => 0, 
                        'sort_column'       => 'menu_order',
                        'post_status'       =>  'any' 
                    );

                    $r = wp_parse_args( $args, $defaults );
                    extract( $r, EXTR_SKIP );

                    $output = '';

                    $r['exclude'] = implode( ',', apply_filters('wp_list_pages_excludes', array()) );
                    
                    // Query pages.
                    $r['hierarchical'] = 0;
                    $args = array(
                                'sort_column'       =>  'menu_order',
                                'post_type'         =>  $post_type,
                                'posts_per_page'    => -1,
                                'post_status'       =>  'any',
                                'orderby'            => array(
                                                            'menu_order'    => 'ASC',
                                                            'post_date'     =>  'DESC'
                                                            )
                    );
                    
                    //allow customisation of the query if necesarelly
                    $args   =   apply_filters('pto/interface/query/args', $args ); 
                    
                    $the_query  = new WP_Query( $args );
                    $pages      = $the_query->posts;

                    if ( !empty($pages) ) 
                        {
                            $output .= $this->walk_tree($pages, $r['depth'], $r);
                        }

                    echo    wp_kses_post    (   $output );
                }
            
            /**
            * Tree walker
            * 
            * @param mixed $pages
            * @param mixed $depth
            * @param mixed $r
            */
            function walk_tree($pages, $depth, $r) 
                {
                    $walker = new Post_Types_Order_Walker;

                    $args = array($pages, $depth, $r);
                    return call_user_func_array(array(&$walker, 'walk'), $args);
                }
            
            
        }

?>