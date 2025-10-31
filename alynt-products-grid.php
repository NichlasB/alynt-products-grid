<?php
/**
 * Plugin Name: Alynt Products Grid
 * Plugin URI: https://yourwebsite.com
 * Description: A WooCommerce mobile-responsive products grid with advanced filtering via shortcode.
 * Version: 1.0.1
 * Author: Alynt
 * Text Domain: alynt-products-grid
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * WC requires at least: 3.0
 * WC tested up to: 10.0.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ALYNT_PG_VERSION', '1.0.1');
define('ALYNT_PG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALYNT_PG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALYNT_PG_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'alynt_pg_woocommerce_missing_notice');
    return;
}

/**
 * Admin notice for missing WooCommerce
 */
function alynt_pg_woocommerce_missing_notice() {
    echo '<div class="notice notice-error"><p><strong>Alynt Products Grid</strong> requires WooCommerce to be installed and active.</p></div>';
}

/**
 * Main Plugin Class
 */
class Alynt_Products_Grid {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_alynt_pg_filter_products', array($this, 'ajax_filter_products'));
        add_action('wp_ajax_nopriv_alynt_pg_filter_products', array($this, 'ajax_filter_products'));
        add_action('wp_ajax_alynt_pg_get_category_counts', array($this, 'ajax_get_category_counts'));
        add_action('wp_ajax_nopriv_alynt_pg_get_category_counts', array($this, 'ajax_get_category_counts'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Register shortcode
        add_shortcode('alynt_products_grid', array($this, 'products_grid_shortcode'));
        
        // Load text domain
        load_plugin_textdomain('alynt-products-grid', false, dirname(ALYNT_PG_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue if shortcode is present on the page
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'alynt_products_grid')) {
            wp_enqueue_style(
                'alynt-pg-style',
                ALYNT_PG_PLUGIN_URL . 'assets/css/style.css',
                array(),
                ALYNT_PG_VERSION . '-' . time() // Cache busting
            );
            
            // Enqueue WooCommerce add to cart script
            wp_enqueue_script('wc-add-to-cart');
            
            wp_enqueue_script(
                'alynt-pg-script',
                ALYNT_PG_PLUGIN_URL . 'assets/js/script.js',
                array('jquery', 'wc-add-to-cart'),
                ALYNT_PG_VERSION . '-' . time(), // Cache busting
                true
            );
            
            // Localize script
            wp_localize_script('alynt-pg-script', 'alynt_pg_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('alynt_pg_nonce'),
                'loading_text' => __('Loading...', 'alynt-products-grid')
            ));
            
            // Ensure WooCommerce add to cart params are available
            if (!wp_script_is('wc-add-to-cart-params', 'done')) {
                wp_localize_script('alynt-pg-script', 'wc_add_to_cart_params', array(
                    'ajax_url' => WC()->ajax_url(),
                    'wc_ajax_url' => WC_AJAX::get_endpoint('%%endpoint%%'),
                    'i18n_view_cart' => esc_attr__('View cart', 'woocommerce'),
                    'cart_url' => apply_filters('woocommerce_add_to_cart_redirect', wc_get_cart_url(), null),
                    'is_cart' => is_cart(),
                    'cart_redirect_after_add' => get_option('woocommerce_cart_redirect_after_add')
                ));
            }
        }
    }
    
    /**
     * Products grid shortcode
     */
    public function products_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => 4,
            'categories' => '',
            'special' => '',
            'per_page' => 12,
            'breakpoint_5' => 1200,
            'breakpoint_4' => 992,
            'breakpoint_3' => 768,
            'breakpoint_2' => 576,
        ), $atts, 'alynt_products_grid');
        
        // Validate columns (max 5)
        $atts['columns'] = min(5, max(1, intval($atts['columns'])));
        $atts['per_page'] = min(100, max(1, intval($atts['per_page']))); // Max 100 products
        
        // Start output buffering
        ob_start();
        
        // Include the template
        $this->render_products_grid($atts);
        
        return ob_get_clean();
    }
    
    /**
     * Render products grid
     */
    private function render_products_grid($atts) {
        // Get all product categories that have products
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true, // Only show categories with products
        ));
        
        // Parse category restrictions
        $restricted_categories = array();
        if (!empty($atts['categories'])) {
            $cat_list = explode(',', $atts['categories']);
            foreach ($cat_list as $cat) {
                $cat = trim($cat);
                if (is_numeric($cat)) {
                    $restricted_categories[] = intval($cat);
                } else {
                    $term = get_term_by('slug', $cat, 'product_cat');
                    if ($term) {
                        $restricted_categories[] = $term->term_id;
                    }
                }
            }
        }
        
        // Parse special categories
        $special_categories = array();
        if (!empty($atts['special'])) {
            $cat_list = explode(',', $atts['special']);
            foreach ($cat_list as $cat) {
                $cat = trim($cat);
                if (is_numeric($cat)) {
                    $special_categories[] = intval($cat);
                } else {
                    $term = get_term_by('slug', $cat, 'product_cat');
                    if ($term) {
                        $special_categories[] = $term->term_id;
                    }
                }
            }
        }
        
        // Filter categories if restrictions are set
        if (!empty($restricted_categories)) {
            $categories = array_filter($categories, function($cat) use ($restricted_categories) {
                return in_array($cat->term_id, $restricted_categories);
            });
        }
        
        // Get initial products (without category restrictions for pagination calculation)
        $products_data = $this->get_products_data(array(
            'categories' => array(), // Don't apply category restrictions to initial query
            'per_page' => $atts['per_page'],
            'page' => 1,
            'search' => '',
        ));
        
        include ALYNT_PG_PLUGIN_DIR . 'templates/products-grid.php';
    }
    
    /**
     * Get products data
     */
    private function get_products_data($args) {
        $defaults = array(
            'categories' => array(),
            'per_page' => 12,
            'page' => 1,
            'search' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WP_Query arguments - simple approach
        $query_args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged' => $args['page'],
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        // Add category filter
        if (!empty($args['categories'])) {
            $category_tax_query = array();
            if (count($args['categories']) === 1) {
                // Single category - use IN operator
                $category_tax_query = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $args['categories'],
                        'operator' => 'IN',
                    )
                );
            } else {
                // Multiple categories - use AND logic
                $category_tax_query = array('relation' => 'AND');
                foreach ($args['categories'] as $category_id) {
                    $category_tax_query[] = array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => array($category_id),
                        'operator' => 'IN',
                    );
                }
            }
            
            // Set the category tax_query
            $query_args['tax_query'] = $category_tax_query;
        }
        
        // Add search filter - search only in post title
        if (!empty($args['search'])) {
            $search_term = sanitize_text_field($args['search']);
            // Use a custom title-only search
            $title_search_filter = function($where, $wp_query) use ($search_term) {
                global $wpdb;
                if ($wp_query->get('search_title_only')) {
                    $where .= " AND {$wpdb->posts}.post_title LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%'";
                }
                return $where;
            };
            
            add_filter('posts_where', $title_search_filter, 10, 2);
            $query_args['search_title_only'] = true;
        }
        
        $query = new WP_Query($query_args);
        
        // Remove the search filter after query
        if (!empty($args['search'])) {
            remove_filter('posts_where', $title_search_filter, 10);
        }
        
        $products = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                
                if ($product) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'title' => $product->get_name(),
                        'price' => $product->get_price_html(),
                        'image' => wp_get_attachment_image_src($product->get_image_id(), 'medium'),
                        'categories' => wp_get_post_terms($product->get_id(), 'product_cat'),
                        'permalink' => $product->get_permalink(),
                        'in_stock' => $product->is_in_stock(),
                        'add_to_cart_url' => $product->add_to_cart_url(),
                    );
                }
            }
        }
        
        wp_reset_postdata();
        
        return array(
            'products' => $products,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $args['page'],
        );
    }
    
    /**
     * Convert category slugs or IDs to IDs
     */
    private function normalize_category_ids($categories) {
        if (empty($categories)) {
            return array();
        }
        
        $category_ids = array();
        foreach ($categories as $cat) {
            if (is_numeric($cat)) {
                // Already an ID
                $category_ids[] = intval($cat);
            } else {
                // Try to get term by slug
                $term = get_term_by('slug', sanitize_text_field($cat), 'product_cat');
                if ($term && !is_wp_error($term)) {
                    $category_ids[] = $term->term_id;
                }
            }
        }
        
        return $category_ids;
    }
    
    /**
     * AJAX filter products
     */
    public function ajax_filter_products() {
        check_ajax_referer('alynt_pg_nonce', 'nonce');
        
        $current_user_id = get_current_user_id();
        
        // Ensure WooCommerce session and customer are properly initialized for AJAX
        // This is required for customer group pricing filters to work correctly
        if (class_exists('WC')) {
            WC()->frontend_includes();
            if (is_null(WC()->cart)) {
                WC()->cart = new WC_Cart();
            }
            if (is_null(WC()->customer)) {
                WC()->customer = new WC_Customer($current_user_id, true);
            }
            if (is_null(WC()->session)) {
                WC()->initialize_session();
            }
        }
        
        // Ensure Customer Groups plugin filters are active for AJAX
        // The woocommerce_get_price_html filter needs to be registered for pricing to work
        if (class_exists('WCCG_Public')) {
            global $wp_filter;
            $has_filter = isset($wp_filter['woocommerce_get_price_html']) && 
                         !empty($wp_filter['woocommerce_get_price_html']->callbacks);
            
            // If filter not active, force Customer Groups initialization
            if (!$has_filter) {
                WCCG_Public::instance();
            }
        }
        
        $categories = !empty($_POST['categories']) ? $this->normalize_category_ids($_POST['categories']) : array();
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = min(100, max(1, intval($_POST['per_page'] ?? 12)));
        
        $products_data = $this->get_products_data(array(
            'categories' => $categories,
            'search' => $search,
            'page' => $page,
            'per_page' => $per_page,
        ));
        
        ob_start();
        foreach ($products_data['products'] as $product) {
            include ALYNT_PG_PLUGIN_DIR . 'templates/product-card.php';
        }
        $products_html = ob_get_clean();
        
        wp_send_json_success(array(
            'products_html' => $products_html,
            'total' => $products_data['total'],
            'pages' => $products_data['pages'],
            'current_page' => $products_data['current_page'],
        ));
    }
    
    /**
     * AJAX get category counts
     */
    public function ajax_get_category_counts() {
        check_ajax_referer('alynt_pg_nonce', 'nonce');
        
        $categories = !empty($_POST['categories']) ? $this->normalize_category_ids($_POST['categories']) : array();
        $search = sanitize_text_field($_POST['search'] ?? '');
        $all_categories = !empty($_POST['all_categories']) ? array_map('intval', $_POST['all_categories']) : array();
        
        $category_counts = array();
        
        foreach ($all_categories as $cat_id) {
            // Calculate count for this category combined with current filters
            $test_categories = $categories;
            
            // If this category is not already selected, add it to test
            if (!in_array($cat_id, $test_categories)) {
                $test_categories[] = $cat_id;
            }
            
            $query_args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids'
            );
            
            // Apply category filtering logic
            if (!empty($test_categories)) {
                if (count($test_categories) === 1) {
                    // Single category
                    $query_args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $test_categories,
                            'operator' => 'IN',
                        )
                    );
                } else {
                    // Multiple categories - use AND logic
                    $tax_queries = array('relation' => 'AND');
                    foreach ($test_categories as $category_id) {
                        $tax_queries[] = array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => array($category_id),
                            'operator' => 'IN',
                        );
                    }
                    $query_args['tax_query'] = $tax_queries;
                }
            }
            
            // Add title-only search if search term exists
            if (!empty($search)) {
                $title_search_filter = function($where, $wp_query) use ($search) {
                    global $wpdb;
                    if ($wp_query->get('search_title_only')) {
                        $where .= " AND {$wpdb->posts}.post_title LIKE '%" . esc_sql($wpdb->esc_like($search)) . "%'";
                    }
                    return $where;
                };
                
                add_filter('posts_where', $title_search_filter, 10, 2);
                $query_args['search_title_only'] = true;
            }
            
            $query = new WP_Query($query_args);
            $category_counts[$cat_id] = $query->found_posts;
            
            // Remove the search filter after query
            if (!empty($search)) {
                remove_filter('posts_where', $title_search_filter, 10);
            }
        }
        
        wp_send_json_success($category_counts);
    }
}

// Initialize the plugin
new Alynt_Products_Grid();
