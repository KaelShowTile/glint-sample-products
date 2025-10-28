<?php
class CHT_Sample_Products_Frontend {
    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_cht_add_sample_to_cart', [__CLASS__, 'add_sample_to_cart']);
        add_action('wp_ajax_nopriv_cht_add_sample_to_cart', [__CLASS__, 'add_sample_to_cart']);
    }

    public static function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_script(
                'cht-sample-products-frontend',
                CHT_SAMPLE_PRODUCTS_URL . 'assets/js/frontend.js',
                ['jquery'],
                CHT_SAMPLE_PRODUCTS_VERSION,
                true
            );

            // Get settings
            global $wpdb;
            $table = $wpdb->prefix . 'glint_sample_product_setting';
            $settings = $wpdb->get_results("SELECT setting_name, setting_value FROM $table", OBJECT_K);
            $settings = array_column($settings, 'setting_value', 'setting_name');
            
            // Localize with settings
            wp_localize_script('cht-sample-products-frontend', 'cht_sample_frontend', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cht_sample_frontend_nonce'),
                'cart_url' => wc_get_cart_url(),
                'i18n' => [
                    'adding' => __('Adding...', 'cht-sample-products'),
                    'added' => __('Sample Added!', 'cht-sample-products'),
                    'error' => __('Error adding sample', 'cht-sample-products')
                ],
                'after_add_to_cart' => $settings['after_add_to_cart'] ?? 'redirect',
                'custom_action' => $settings['custom_action'] ?? ''
            ]);
        }
    }

    public static function add_sample_to_cart() {
        check_ajax_referer('cht_sample_frontend_nonce', 'nonce');
        
        $original_id = absint($_POST['product_id']);
        $sample_id = self::get_sample_product_id($original_id);
        
        if (!$sample_id) {
            wp_send_json_error(['message' => __('Sample product not found', 'cht-sample-products')]);
        }
        
        // Get current cart contents
        $current_cart = WC()->cart->get_cart();
        
        // Add sample product to cart (preserving existing items)
        $added = WC()->cart->add_to_cart($sample_id, 1);
        
        if ($added) {
            $data = [
            'message' => __('Sample added to cart!', 'cht-sample-products'),
            //'fragments' => self::get_refreshed_fragments(),
            'cart_url' => wc_get_cart_url()
        ];
        wp_send_json_success($data);
        } else {
            wp_send_json_error(['message' => __('Could not add sample to cart', 'cht-sample-products')]);
        }
    }
    
    public static function get_sample_product_id($original_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'glint_sample_product';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT sample_product_id FROM $table WHERE original_product_id = %d",
            $original_id
        ));
    }
    
    public static function render_sample_button($product_id) {
        // Only show for non-sample products
        if (self::is_sample_product($product_id)) {
            return;
        }
        
        $sample_id = self::get_sample_product_id($product_id);
        
        if (!$sample_id) {
            return;
        }
        
        $sample_product = wc_get_product($sample_id);
        
        // Only show if sample product exists and is purchasable
        if (!$sample_product || !$sample_product->is_purchasable()) {
            return;
        }
        
        echo '<div class="cht-sample-button-container">';
        echo '<button class="button cht-order-sample sample-button" id="sample-button-notification" data-product-id="' . esc_attr($product_id) . '">';
        echo __('Order Sample', 'cht-sample-products');
        echo '</button>';
        echo '</div>';
    }
    
    private static function is_sample_product($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'glint_sample_product_setting';
        $sample_category = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_name = %s",
            'sample_category'
        ));
        
        if (!$sample_category) {
            return false;
        }
        
        return has_term($sample_category, 'product_cat', $product_id);
    }
}