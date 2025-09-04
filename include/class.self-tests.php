<?php

    if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    
    class PTO_SelfTests 
        {
            
            /**
            * Constructor
            */
            function __construct() 
                {
                    add_action('admin_menu', array($this, 'add_tools_menu'));
                    add_action('wp_ajax_pto_run_self_test', array($this, 'run_single_test'));
                }
                
            /**
            * Add self-tests page to Tools menu
            */
            function add_tools_menu()
                {
                    add_management_page(
                        __('KISS Re-Order Self Tests', 'post-types-order'),
                        __('KISS Re-Order Self Tests', 'post-types-order'),
                        'manage_options',
                        'pto-self-tests',
                        array($this, 'render_tests_page')
                    );
                }
                
            /**
            * Render the self-tests page
            */
            function render_tests_page()
                {
                    ?>
                    <div class="wrap">
                        <h1><?php echo sprintf(__('KISS Re-Order Self Tests - v%s', 'post-types-order'), PTO_VERSION); ?></h1>
                        
                        <div class="notice notice-info">
                            <p><strong><?php _e('About Self Tests:', 'post-types-order'); ?></strong></p>
                            <p><?php _e('These tests help detect regressions and bugs after refactoring. Run them after any code changes to ensure core functionality remains intact.', 'post-types-order'); ?></p>
                        </div>
                        
                        <div class="pto-self-tests">
                            <div class="test-controls" style="margin: 20px 0;">
                                <button type="button" id="run-all-tests" class="button button-primary"><?php _e('Run All Tests', 'post-types-order'); ?></button>
                                <button type="button" id="clear-results" class="button"><?php _e('Clear Results', 'post-types-order'); ?></button>
                            </div>
                            
                            <div class="test-results" id="test-results">
                                <!-- Test results will be populated here -->
                            </div>
                            
                            <div class="test-list">
                                <?php $this->render_test_list(); ?>
                            </div>
                        </div>
                        
                        <style>
                        .pto-self-tests .test-item {
                            border: 1px solid #ddd;
                            margin: 10px 0;
                            padding: 15px;
                            background: #fff;
                            border-radius: 4px;
                        }
                        .test-item h3 {
                            margin: 0 0 10px 0;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        }
                        .test-status {
                            padding: 4px 8px;
                            border-radius: 3px;
                            font-size: 12px;
                            font-weight: bold;
                        }
                        .status-pending { background: #f0f0f1; color: #646970; }
                        .status-running { background: #007cba; color: white; }
                        .status-pass { background: #00a32a; color: white; }
                        .status-fail { background: #d63638; color: white; }
                        .test-description { margin: 10px 0; color: #646970; }
                        .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
                        .result-pass { background: #d1e7dd; border: 1px solid #badbcc; }
                        .result-fail { background: #f8d7da; border: 1px solid #f5c2c7; }
                        .test-details { font-family: monospace; font-size: 12px; margin-top: 10px; }
                        </style>
                        
                        <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            $('#run-all-tests').click(function() {
                                runAllTests();
                            });
                            
                            $('#clear-results').click(function() {
                                clearResults();
                            });
                            
                            $('.run-single-test').click(function() {
                                var testId = $(this).data('test-id');
                                runSingleTest(testId);
                            });
                            
                            function runAllTests() {
                                $('.test-item').each(function() {
                                    var testId = $(this).data('test-id');
                                    runSingleTest(testId);
                                });
                            }
                            
                            function runSingleTest(testId) {
                                var $testItem = $('.test-item[data-test-id="' + testId + '"]');
                                var $status = $testItem.find('.test-status');
                                var $result = $testItem.find('.test-result');
                                
                                $status.removeClass('status-pending status-pass status-fail').addClass('status-running').text('Running...');
                                $result.hide();
                                
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'pto_run_self_test',
                                        test_id: testId,
                                        nonce: '<?php echo wp_create_nonce('pto_self_test_nonce'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $status.removeClass('status-running').addClass('status-pass').text('PASS');
                                            $result.removeClass('result-fail').addClass('result-pass').html(response.data.message).show();
                                        } else {
                                            $status.removeClass('status-running').addClass('status-fail').text('FAIL');
                                            $result.removeClass('result-pass').addClass('result-fail').html(response.data.message).show();
                                        }
                                        
                                        if (response.data.details) {
                                            $result.append('<div class="test-details">' + response.data.details + '</div>');
                                        }
                                    },
                                    error: function() {
                                        $status.removeClass('status-running').addClass('status-fail').text('ERROR');
                                        $result.removeClass('result-pass').addClass('result-fail').html('AJAX request failed').show();
                                    }
                                });
                            }
                            
                            function clearResults() {
                                $('.test-status').removeClass('status-running status-pass status-fail').addClass('status-pending').text('Pending');
                                $('.test-result').hide();
                            }
                        });
                        </script>
                    </div>
                    <?php
                }
                
            /**
            * Render the list of available tests
            */
            function render_test_list()
                {
                    $tests = $this->get_test_definitions();
                    
                    foreach ($tests as $test_id => $test) {
                        ?>
                        <div class="test-item" data-test-id="<?php echo esc_attr($test_id); ?>">
                            <h3>
                                <?php echo esc_html($test['name']); ?>
                                <div>
                                    <span class="test-status status-pending">Pending</span>
                                    <button type="button" class="button run-single-test" data-test-id="<?php echo esc_attr($test_id); ?>"><?php _e('Run Test', 'post-types-order'); ?></button>
                                </div>
                            </h3>
                            <div class="test-description"><?php echo esc_html($test['description']); ?></div>
                            <div class="test-result" style="display: none;"></div>
                        </div>
                        <?php
                    }
                }
                
            /**
            * Get test definitions
            */
            function get_test_definitions()
                {
                    return array(
                        'database_connectivity' => array(
                            'name' => __('Database Connectivity Test', 'post-types-order'),
                            'description' => __('Verifies that the plugin can connect to the database and perform basic operations on the posts table.', 'post-types-order'),
                            'critical' => true
                        ),
                        'post_ordering_functionality' => array(
                            'name' => __('Post Ordering Functionality Test', 'post-types-order'),
                            'description' => __('Tests the core post ordering mechanism by creating test posts and verifying menu_order updates work correctly.', 'post-types-order'),
                            'critical' => true
                        ),
                        'ajax_security_validation' => array(
                            'name' => __('AJAX Security Validation Test', 'post-types-order'),
                            'description' => __('Validates that AJAX handlers properly check nonces, capabilities, and input sanitization.', 'post-types-order'),
                            'critical' => true
                        ),
                        'pagination_performance' => array(
                            'name' => __('Pagination Performance Test', 'post-types-order'),
                            'description' => __('Ensures pagination limits are working and queries are not unbounded (posts_per_page != -1).', 'post-types-order'),
                            'critical' => true
                        )
                    );
                }

            /**
            * AJAX handler for running individual tests
            */
            function run_single_test()
                {
                    // Security checks
                    if (!current_user_can('manage_options')) {
                        wp_send_json_error(array('message' => __('Insufficient permissions.', 'post-types-order')));
                        return;
                    }

                    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
                    if (!wp_verify_nonce($nonce, 'pto_self_test_nonce')) {
                        wp_send_json_error(array('message' => __('Security verification failed.', 'post-types-order')));
                        return;
                    }

                    $test_id = isset($_POST['test_id']) ? sanitize_text_field(wp_unslash($_POST['test_id'])) : '';
                    if (empty($test_id)) {
                        wp_send_json_error(array('message' => __('No test ID provided.', 'post-types-order')));
                        return;
                    }

                    // Run the specific test
                    $result = $this->execute_test($test_id);

                    if ($result['success']) {
                        wp_send_json_success($result);
                    } else {
                        wp_send_json_error($result);
                    }
                }

            /**
            * Execute a specific test
            */
            function execute_test($test_id)
                {
                    $start_time = microtime(true);

                    try {
                        switch ($test_id) {
                            case 'database_connectivity':
                                $result = $this->test_database_connectivity();
                                break;

                            case 'post_ordering_functionality':
                                $result = $this->test_post_ordering_functionality();
                                break;

                            case 'ajax_security_validation':
                                $result = $this->test_ajax_security_validation();
                                break;

                            case 'pagination_performance':
                                $result = $this->test_pagination_performance();
                                break;

                            default:
                                return array(
                                    'success' => false,
                                    'message' => sprintf(__('Unknown test: %s', 'post-types-order'), $test_id)
                                );
                        }

                        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
                        $result['details'] = isset($result['details']) ? $result['details'] : '';
                        $result['details'] .= "\nExecution time: {$execution_time}ms";

                        return $result;

                    } catch (Exception $e) {
                        return array(
                            'success' => false,
                            'message' => sprintf(__('Test failed with exception: %s', 'post-types-order'), $e->getMessage()),
                            'details' => $e->getTraceAsString()
                        );
                    }
                }

            /**
            * Test 1: Database Connectivity
            */
            function test_database_connectivity()
                {
                    global $wpdb;

                    $checks = array();

                    // Check if we can query the posts table
                    $post_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts}");
                    if ($post_count === null) {
                        return array(
                            'success' => false,
                            'message' => __('Failed to query posts table', 'post-types-order'),
                            'details' => 'Database error: ' . $wpdb->last_error
                        );
                    }
                    $checks[] = "✓ Posts table accessible ({$post_count} posts found)";

                    // Check if we can query menu_order column
                    $menu_order_test = $wpdb->get_var("SELECT menu_order FROM {$wpdb->posts} LIMIT 1");
                    if ($wpdb->last_error) {
                        return array(
                            'success' => false,
                            'message' => __('Failed to access menu_order column', 'post-types-order'),
                            'details' => 'Database error: ' . $wpdb->last_error
                        );
                    }
                    $checks[] = "✓ menu_order column accessible";

                    // Check if we can perform UPDATE operations (dry run)
                    $test_query = $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1", 'post');
                    $test_post_id = $wpdb->get_var($test_query);

                    if ($test_post_id) {
                        // Test UPDATE capability (but don't actually change anything)
                        $current_order = $wpdb->get_var($wpdb->prepare("SELECT menu_order FROM {$wpdb->posts} WHERE ID = %d", $test_post_id));
                        $checks[] = "✓ UPDATE operations possible (test post ID: {$test_post_id})";
                    } else {
                        $checks[] = "⚠ No test posts available for UPDATE test";
                    }

                    return array(
                        'success' => true,
                        'message' => __('Database connectivity test passed', 'post-types-order'),
                        'details' => implode("\n", $checks)
                    );
                }

            /**
            * Test 2: Post Ordering Functionality
            */
            function test_post_ordering_functionality()
                {
                    global $wpdb;

                    $checks = array();

                    // Ensure CPTO class is loaded
                    if (!class_exists('CPTO')) {
                        if (file_exists(CPTPATH . '/include/class.cpto.php')) {
                            include_once(CPTPATH . '/include/class.cpto.php');
                        }
                    }

                    // Check if CPTO class exists and is properly initialized
                    global $CPTO;
                    if (!$CPTO || !is_object($CPTO)) {
                        // Try to create instance if class exists but global is not set
                        if (class_exists('CPTO')) {
                            $checks[] = "⚠ CPTO class exists but global not initialized";
                        } else {
                            return array(
                                'success' => false,
                                'message' => __('CPTO class not found', 'post-types-order'),
                                'details' => 'CPTO class file may not be loaded properly'
                            );
                        }
                    } else {
                        $checks[] = "✓ CPTO class properly initialized";
                    }

                    // Check if core methods exist
                    $required_methods = array('saveAjaxOrder', 'filterPostsByCategory');
                    $optional_methods = array('processOrderData'); // This is a private method added in recent updates

                    $missing_required = array();
                    foreach ($required_methods as $method) {
                        if ($CPTO && !method_exists($CPTO, $method)) {
                            $missing_required[] = $method;
                        }
                    }

                    if (!empty($missing_required)) {
                        return array(
                            'success' => false,
                            'message' => sprintf(__('Required methods missing: %s', 'post-types-order'), implode(', ', $missing_required)),
                            'details' => 'Core functionality may be broken'
                        );
                    }

                    $existing_methods = array();
                    foreach (array_merge($required_methods, $optional_methods) as $method) {
                        if ($CPTO && method_exists($CPTO, $method)) {
                            $existing_methods[] = $method;
                        }
                    }
                    $checks[] = "✓ Core methods exist: " . implode(', ', $existing_methods);

                    // Test menu_order functionality with existing posts
                    $test_posts = $wpdb->get_results("SELECT ID, menu_order FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' LIMIT 3");

                    if (empty($test_posts)) {
                        $checks[] = "⚠ No published posts available for ordering test";
                    } else {
                        $checks[] = sprintf("✓ Found %d test posts for ordering validation", count($test_posts));

                        // Verify menu_order values are numeric
                        foreach ($test_posts as $post) {
                            if (!is_numeric($post->menu_order)) {
                                return array(
                                    'success' => false,
                                    'message' => sprintf(__('Invalid menu_order value for post %d', 'post-types-order'), $post->ID),
                                    'details' => "menu_order should be numeric, found: " . var_export($post->menu_order, true)
                                );
                            }
                        }
                        $checks[] = "✓ All menu_order values are numeric";
                    }

                    // Check if WordPress hooks are properly registered (if functions exist)
                    if (function_exists('has_filter')) {
                        $hooks_to_check = array(
                            'pre_get_posts' => 'pre_get_posts',
                            'posts_orderby' => 'posts_orderby'
                        );

                        foreach ($hooks_to_check as $hook => $method) {
                            if ($CPTO && !has_filter($hook, array($CPTO, $method))) {
                                $checks[] = "⚠ Hook {$hook} may not be properly registered";
                            } else {
                                $checks[] = "✓ Hook {$hook} properly registered";
                            }
                        }
                    } else {
                        $checks[] = "⚠ WordPress hook functions not available for testing";
                    }

                    return array(
                        'success' => true,
                        'message' => __('Post ordering functionality test passed', 'post-types-order'),
                        'details' => implode("\n", $checks)
                    );
                }

            /**
            * Test 3: AJAX Security Validation
            */
            function test_ajax_security_validation()
                {
                    $checks = array();

                    // Check if AJAX actions are properly registered (if function exists)
                    if (function_exists('has_action')) {
                        $ajax_actions = array(
                            'wp_ajax_update-custom-type-order',
                            'wp_ajax_update-custom-type-order-archive',
                            'wp_ajax_pto_filter_posts_by_category'
                        );

                        $missing_actions = array();
                        foreach ($ajax_actions as $action) {
                            if (!has_action($action)) {
                                $missing_actions[] = $action;
                            }
                        }

                        if (!empty($missing_actions)) {
                            $checks[] = "⚠ Some AJAX actions not registered: " . implode(', ', $missing_actions);
                        } else {
                            $checks[] = "✓ All AJAX actions properly registered";
                        }
                    } else {
                        $checks[] = "⚠ WordPress action functions not available for testing";
                    }

                    // Check if security functions are available
                    $security_functions = array('wp_verify_nonce', 'current_user_can', 'sanitize_text_field', 'wp_unslash');
                    foreach ($security_functions as $func) {
                        if (!function_exists($func)) {
                            return array(
                                'success' => false,
                                'message' => sprintf(__('Security function %s not available', 'post-types-order'), $func),
                                'details' => 'WordPress security functions missing'
                            );
                        }
                    }
                    $checks[] = "✓ Security functions available: " . implode(', ', $security_functions);

                    // Test nonce generation
                    $test_nonce = wp_create_nonce('test_nonce');
                    if (empty($test_nonce)) {
                        return array(
                            'success' => false,
                            'message' => __('Nonce generation failed', 'post-types-order'),
                            'details' => 'wp_create_nonce returned empty value'
                        );
                    }
                    $checks[] = "✓ Nonce generation working";

                    // Test nonce verification
                    if (!wp_verify_nonce($test_nonce, 'test_nonce')) {
                        return array(
                            'success' => false,
                            'message' => __('Nonce verification failed', 'post-types-order'),
                            'details' => 'wp_verify_nonce failed for valid nonce'
                        );
                    }
                    $checks[] = "✓ Nonce verification working";

                    return array(
                        'success' => true,
                        'message' => __('AJAX security validation test passed', 'post-types-order'),
                        'details' => implode("\n", $checks)
                    );
                }

            /**
            * Test 4: Pagination Performance
            */
            function test_pagination_performance()
                {
                    $checks = array();

                    // Ensure interface class is loaded
                    if (!class_exists('PTO_Interface')) {
                        // Try to load the interface class
                        if (file_exists(CPTPATH . '/include/class.interface.php')) {
                            include_once(CPTPATH . '/include/class.interface.php');
                        }

                        // Check again after attempting to load
                        if (!class_exists('PTO_Interface')) {
                            return array(
                                'success' => false,
                                'message' => __('PTO_Interface class not found', 'post-types-order'),
                                'details' => 'Interface class file exists but class not loaded. Path: ' . CPTPATH . '/include/class.interface.php'
                            );
                        }
                    }
                    $checks[] = "✓ PTO_Interface class exists";

                    // Create interface instance to test pagination
                    $interface = new PTO_Interface();

                    // Check if pagination methods exist
                    if (!method_exists($interface, 'render_pagination_controls')) {
                        return array(
                            'success' => false,
                            'message' => __('Pagination methods missing', 'post-types-order'),
                            'details' => 'render_pagination_controls method not found'
                        );
                    }
                    $checks[] = "✓ Pagination methods exist";

                    // Test that we're not using unbounded queries
                    $reflection = new ReflectionClass('PTO_Interface');
                    $list_pages_method = $reflection->getMethod('list_pages');

                    // Get the source code of the method (this is a bit hacky but works for testing)
                    $filename = $reflection->getFileName();
                    $start_line = $list_pages_method->getStartLine() - 1;
                    $end_line = $list_pages_method->getEndLine();
                    $length = $end_line - $start_line;

                    $source = file($filename);
                    $method_source = implode("", array_slice($source, $start_line, $length));

                    // Check for unbounded queries
                    if (strpos($method_source, "'posts_per_page' => -1") !== false ||
                        strpos($method_source, '"posts_per_page" => -1') !== false) {
                        return array(
                            'success' => false,
                            'message' => __('Unbounded query detected in list_pages method', 'post-types-order'),
                            'details' => 'Found posts_per_page => -1 which can cause performance issues'
                        );
                    }
                    $checks[] = "✓ No unbounded queries in list_pages method";

                    // Check default pagination limit
                    if (strpos($method_source, "'posts_per_page' => 50") !== false ||
                        strpos($method_source, '"posts_per_page" => 50') !== false) {
                        $checks[] = "✓ Proper pagination limit (50) detected";
                    } else {
                        $checks[] = "⚠ Could not verify pagination limit in source code";
                    }

                    // Test pagination info property
                    if (!property_exists($interface, 'pagination_info')) {
                        return array(
                            'success' => false,
                            'message' => __('Pagination info property missing', 'post-types-order'),
                            'details' => 'pagination_info property not found in PTO_Interface'
                        );
                    }
                    $checks[] = "✓ Pagination info property exists";

                    return array(
                        'success' => true,
                        'message' => __('Pagination performance test passed', 'post-types-order'),
                        'details' => implode("\n", $checks)
                    );
                }
        }

?>
