<?php
/**
 * Plugin Name: CHT Sample Product System
 * Description: Different with GTO Sample product system, this plugin create extra products as sample products
 * Version: 1.0.1
 * Author: Kael
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('CHT_SAMPLE_PRODUCTS_VERSION', '1.0.0');
define('CHT_SAMPLE_PRODUCTS_PATH', plugin_dir_path(__FILE__));
define('CHT_SAMPLE_PRODUCTS_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once CHT_SAMPLE_PRODUCTS_PATH . 'includes/class-cht-sample-products-activator.php';
require_once CHT_SAMPLE_PRODUCTS_PATH . 'includes/class-cht-sample-products-admin.php';
require_once CHT_SAMPLE_PRODUCTS_PATH . 'includes/class-cht-sample-products-product.php';
require_once CHT_SAMPLE_PRODUCTS_PATH . 'includes/class-cht-sample-products-frontend.php';
require_once CHT_SAMPLE_PRODUCTS_PATH . 'includes/class-cht-sample-products-seo.php';

// Register activation hook
register_activation_hook(__FILE__, ['CHT_Sample_Products_Activator', 'activate']);

// Initialize plugin functionality
add_action('plugins_loaded', function() {
    CHT_Sample_Products_Admin::init();
    CHT_Sample_Products_Product::init();
    CHT_Sample_Products_Frontend::init();
    CHT_Sample_Products_SEO::init();
});

if (!function_exists('cht_add_sample_btn')) {
    function cht_add_sample_btn($product_id = null) {
        if (!$product_id) {
            global $product;
            $product_id = $product->get_id();
        }
        CHT_Sample_Products_Frontend::render_sample_button($product_id);
    }
}

if (!function_exists('cht_check_sample_id')) {
    function cht_check_sample_id($product_id= null) {
        if (!$product_id) {
            global $product;
            $product_id = $product->get_id();
        }
        error_log('get orginal product id is: ' . $product_id);
        return CHT_Sample_Products_Frontend::get_sample_product_id($product_id);
    }
}
