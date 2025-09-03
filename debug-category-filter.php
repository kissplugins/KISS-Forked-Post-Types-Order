<?php
/**
 * Debug script to check category filter functionality
 * Place this file in the plugin root and access via browser to debug
 * Remove after debugging is complete
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // For debugging purposes, we'll allow direct access
    // In production, this should be removed
    define('ABSPATH', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/');
    require_once(ABSPATH . 'wp-config.php');
}

echo "<h2>Post Types Order - Category Filter Debug</h2>";

// Check if we're in WordPress admin
if (!is_admin()) {
    echo "<p><strong>Note:</strong> This debug should be run from WordPress admin area.</p>";
}

// Get all public post types
$post_types = get_post_types(array('public' => true), 'objects');

echo "<h3>Available Post Types:</h3>";
foreach ($post_types as $post_type) {
    echo "<h4>Post Type: " . esc_html($post_type->name) . " (" . esc_html($post_type->label) . ")</h4>";
    
    // Get taxonomies for this post type
    $taxonomies = get_object_taxonomies($post_type->name, 'objects');
    
    if (empty($taxonomies)) {
        echo "<p>No taxonomies found for this post type.</p>";
        continue;
    }
    
    echo "<ul>";
    foreach ($taxonomies as $taxonomy) {
        echo "<li><strong>" . esc_html($taxonomy->name) . "</strong> (" . esc_html($taxonomy->label) . ")";
        echo " - Hierarchical: " . ($taxonomy->hierarchical ? 'Yes' : 'No');
        
        if ($taxonomy->hierarchical) {
            // Get terms for this taxonomy
            $terms = get_terms(array(
                'taxonomy' => $taxonomy->name,
                'hide_empty' => false,
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;Terms: ";
                $term_names = array();
                foreach ($terms as $term) {
                    $term_names[] = $term->name . " (" . $term->count . ")";
                }
                echo implode(', ', $term_names);
            } else {
                echo "<br>&nbsp;&nbsp;&nbsp;&nbsp;No terms found";
            }
        }
        echo "</li>";
    }
    echo "</ul>";
    
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
    
    echo "<p><strong>Category Filter Would Show:</strong> " . ($has_hierarchical_taxonomies ? 'YES' : 'NO') . "</p>";
    echo "<hr>";
}

// Check current plugin status
echo "<h3>Plugin Information:</h3>";
echo "<p><strong>Plugin Active:</strong> " . (is_plugin_active('KISS-post-types-order-fork/post-types-order.php') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Plugin Version:</strong> " . (defined('PTO_VERSION') ? PTO_VERSION : 'Not defined') . "</p>";

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

echo "<h3>Sample Posts (Post Type: post):</h3>";
if (!empty($posts_with_categories)) {
    foreach ($posts_with_categories as $post) {
        echo "<p><strong>" . esc_html($post->post_title) . "</strong>";
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $cat_names = array();
            foreach ($categories as $cat) {
                $cat_names[] = $cat->name;
            }
            echo " - Categories: " . implode(', ', $cat_names);
        } else {
            echo " - No categories";
        }
        echo "</p>";
    }
} else {
    echo "<p>No posts found.</p>";
}

echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If 'Category Filter Would Show' is 'NO' for the post type you're testing, that's why the filter doesn't appear.</li>";
echo "<li>Make sure the post type has hierarchical taxonomies (like categories) with actual terms.</li>";
echo "<li>For the 'post' post type, make sure you have categories created and assigned to posts.</li>";
echo "<li>The filter only shows for hierarchical taxonomies (categories), not flat taxonomies (tags).</li>";
echo "</ul>";

echo "<p><em>Remember to delete this debug file after troubleshooting!</em></p>";
?>
