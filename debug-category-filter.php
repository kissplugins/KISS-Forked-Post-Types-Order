<?php
/**
 * SECURE Debug script to check category filter functionality
 *
 * ⚠️  SECURITY NOTICE: This file now requires WordPress admin access ⚠️
 *
 * USAGE:
 * 1. Access via WordPress admin: Tools → KISS Re-Order Self Tests
 * 2. Or add ?pto_debug=1 to any admin page URL
 * 3. Must be logged in as administrator
 *
 * SECURITY FEATURES:
 * - Requires WordPress authentication
 * - Requires administrator capabilities
 * - No direct file access allowed
 * - Integrated with WordPress security system
 */

// SECURITY: Prevent any direct access - NO EXCEPTIONS
if (!defined('ABSPATH')) {
    http_response_code(403);
    die('Direct access forbidden. This debug tool requires WordPress admin access.');
}

// SECURITY: Require WordPress admin environment
if (!is_admin()) {
    wp_die(__('This debug tool can only be accessed from WordPress admin area.', 'post-types-order'));
}

// SECURITY: Require administrator capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('Insufficient permissions. Administrator access required.', 'post-types-order'));
}

// SECURITY: Check for debug parameter or self-tests page
$is_debug_request = (isset($_GET['pto_debug']) && $_GET['pto_debug'] === '1') ||
                   (isset($_GET['page']) && $_GET['page'] === 'pto-self-tests');

if (!$is_debug_request) {
    return; // Silent return if not a debug request
}

// SECURITY: Add nonce verification for extra security
if (isset($_GET['pto_debug']) && !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'pto_debug_nonce')) {
    wp_die(__('Security verification failed.', 'post-types-order'));
}

?>
<div class="wrap">
    <h1><?php echo sprintf(__('KISS Re-Order - Category Filter Debug - v%s', 'post-types-order'), defined('PTO_VERSION') ? PTO_VERSION : 'Unknown'); ?></h1>

    <div class="notice notice-info">
        <p><strong><?php _e('Security Notice:', 'post-types-order'); ?></strong></p>
        <p><?php _e('This debug tool is now secure and requires administrator access. It integrates with WordPress security systems.', 'post-types-order'); ?></p>
    </div>

    <div class="notice notice-warning">
        <p><strong><?php _e('Debug Information:', 'post-types-order'); ?></strong></p>
        <p><?php _e('This tool helps diagnose category filter functionality. Use it to understand why filters may not appear for certain post types.', 'post-types-order'); ?></p>
    </div>

    <h2><?php _e('Available Post Types Analysis', 'post-types-order'); ?></h2>

    <?php
    // Get all public post types
    $post_types = get_post_types(array('public' => true), 'objects');

    foreach ($post_types as $post_type) {
        ?>
        <div class="postbox">
            <h3 class="hndle"><span><?php echo sprintf(__('Post Type: %s (%s)', 'post-types-order'), esc_html($post_type->name), esc_html($post_type->label)); ?></span></h3>
            <div class="inside">
                <?php
                // Get taxonomies for this post type
                $taxonomies = get_object_taxonomies($post_type->name, 'objects');

                if (empty($taxonomies)) {
                    echo '<p>' . __('No taxonomies found for this post type.', 'post-types-order') . '</p>';
                    continue;
                }

                echo '<h4>' . __('Taxonomies:', 'post-types-order') . '</h4>';
                echo '<ul>';
                foreach ($taxonomies as $taxonomy) {
                    echo '<li><strong>' . esc_html($taxonomy->name) . '</strong> (' . esc_html($taxonomy->label) . ')';
                    echo ' - ' . __('Hierarchical:', 'post-types-order') . ' ' . ($taxonomy->hierarchical ? __('Yes', 'post-types-order') : __('No', 'post-types-order'));

                    if ($taxonomy->hierarchical) {
                        // Get terms for this taxonomy
                        $terms = get_terms(array(
                            'taxonomy' => $taxonomy->name,
                            'hide_empty' => false,
                        ));

                        if (!empty($terms) && !is_wp_error($terms)) {
                            echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;' . __('Terms:', 'post-types-order') . ' ';
                            $term_names = array();
                            foreach ($terms as $term) {
                                $term_names[] = $term->name . ' (' . $term->count . ')';
                            }
                            echo implode(', ', $term_names);
                        } else {
                            echo '<br>&nbsp;&nbsp;&nbsp;&nbsp;' . __('No terms found', 'post-types-order');
                        }
                    }
                    echo '</li>';
                }
                echo '</ul>';

                // Check if this post type would show the category filter
                $has_hierarchical_taxonomies = false;
                foreach ($taxonomies as $taxonomy) {
                    if (!$taxonomy->hierarchical) continue;

                    $terms = get_terms(array(
                        'taxonomy' => $taxonomy->name,
                        'hide_empty' => false,
                    ));

                    if (!empty($terms) && !is_wp_error($terms)) {
                        $has_hierarchical_taxonomies = true;
                        break;
                    }
                }

                $filter_status = $has_hierarchical_taxonomies ? __('YES', 'post-types-order') : __('NO', 'post-types-order');
                $status_class = $has_hierarchical_taxonomies ? 'notice-success' : 'notice-warning';
                ?>
                <div class="notice <?php echo $status_class; ?> inline">
                    <p><strong><?php _e('Category Filter Would Show:', 'post-types-order'); ?></strong> <?php echo $filter_status; ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

    <h2><?php _e('Plugin Information', 'post-types-order'); ?></h2>
    <div class="postbox">
        <div class="inside">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Plugin Status:', 'post-types-order'); ?></th>
                    <td>
                        <?php
                        $plugin_active = function_exists('is_plugin_active') ? is_plugin_active('KISS-Forked-Post-Types-Order/post-types-order.php') : true;
                        echo $plugin_active ? '<span style="color: green;">✓ ' . __('Active', 'post-types-order') . '</span>' : '<span style="color: red;">✗ ' . __('Inactive', 'post-types-order') . '</span>';
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Plugin Version:', 'post-types-order'); ?></th>
                    <td><?php echo defined('PTO_VERSION') ? PTO_VERSION : __('Not defined', 'post-types-order'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('WordPress Version:', 'post-types-order'); ?></th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Debug Access:', 'post-types-order'); ?></th>
                    <td><span style="color: green;">✓ <?php _e('Secure (Admin Only)', 'post-types-order'); ?></span></td>
                </tr>
            </table>
        </div>
    </div>

    <h2><?php _e('Sample Posts Analysis', 'post-types-order'); ?></h2>
    <div class="postbox">
        <div class="inside">
            <?php
            // Check if we have any posts with categories
            $posts_with_categories = get_posts(array(
                'post_type' => 'post',
                'posts_per_page' => 5,
                'meta_query' => array(
                    array(
                        'key' => '_wp_attached_file',
                        'compare' => 'NOT EXISTS'
                    )
                )
            ));

            if (!empty($posts_with_categories)) {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>' . __('Post Title', 'post-types-order') . '</th><th>' . __('Categories', 'post-types-order') . '</th></tr></thead>';
                echo '<tbody>';
                foreach ($posts_with_categories as $post) {
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($post->post_title) . '</strong></td>';
                    echo '<td>';
                    $categories = get_the_category($post->ID);
                    if (!empty($categories)) {
                        $cat_names = array();
                        foreach ($categories as $cat) {
                            $cat_names[] = $cat->name;
                        }
                        echo implode(', ', $cat_names);
                    } else {
                        echo '<em>' . __('No categories', 'post-types-order') . '</em>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>' . __('No posts found.', 'post-types-order') . '</p>';
            }
            ?>
        </div>
    </div>

    <h2><?php _e('Troubleshooting Recommendations', 'post-types-order'); ?></h2>
    <div class="postbox">
        <div class="inside">
            <ul>
                <li><?php _e('If "Category Filter Would Show" is "NO" for the post type you\'re testing, that\'s why the filter doesn\'t appear.', 'post-types-order'); ?></li>
                <li><?php _e('Make sure the post type has hierarchical taxonomies (like categories) with actual terms.', 'post-types-order'); ?></li>
                <li><?php _e('For the "post" post type, make sure you have categories created and assigned to posts.', 'post-types-order'); ?></li>
                <li><?php _e('The filter only shows for hierarchical taxonomies (categories), not flat taxonomies (tags).', 'post-types-order'); ?></li>
                <li><?php _e('Check that your post type is public and supports the taxonomy you\'re testing.', 'post-types-order'); ?></li>
            </ul>
        </div>
    </div>

    <div class="notice notice-success">
        <p><strong><?php _e('Security Status:', 'post-types-order'); ?></strong> <?php _e('This debug tool is now secure and requires administrator access only.', 'post-types-order'); ?></p>
    </div>

</div>
<?php
