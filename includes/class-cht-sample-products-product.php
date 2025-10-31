<?php
class CHT_Sample_Products_Product {
    public static function init() {
        add_filter('woocommerce_product_data_tabs', [__CLASS__, 'add_product_data_tab']);
        add_action('woocommerce_product_data_panels', [__CLASS__, 'add_product_data_tab_content']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_cht_create_sample_product', [__CLASS__, 'create_sample_product']);
        add_action('wp_ajax_cht_delete_sample_product', [__CLASS__, 'delete_sample_product']);
    }

    public static function enqueue_scripts() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'product') {
            wp_enqueue_script(
                'cht-sample-products-admin',
                CHT_SAMPLE_PRODUCTS_URL . 'assets/js/admin.js',
                ['jquery'],
                CHT_SAMPLE_PRODUCTS_VERSION,
                true
            );
            
            wp_localize_script('cht-sample-products-admin', 'cht_sample_products', [
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
        }
    }

    // Helper function to get sample category ID
    private static function get_sample_category_id() {
        static $category_id = null;
        
        if (is_null($category_id)) {
            global $wpdb;
            $table = $wpdb->prefix . 'glint_sample_product_setting';
            $category_id = $wpdb->get_var($wpdb->prepare(
                "SELECT setting_value FROM $table WHERE setting_name = %s",
                'sample_category'
            ));
        }
        
        return $category_id;
    }

    // Helper function to check if product is a sample
    private static function is_sample_product($product_id) {
        $sample_category_id = self::get_sample_category_id();
        
        if (!$sample_category_id) {
            return false;
        }
        
        $terms = get_the_terms($product_id, 'product_cat');
        
        if (!$terms || is_wp_error($terms)) {
            return false;
        }
        
        foreach ($terms as $term) {
            if ($term->term_id == $sample_category_id) {
                return true;
            }
        }
        
        return false;
    }

    public static function add_product_data_tab($tabs) {
        global $post;
        
        // Skip if we don't have a product
        if (!$post || $post->post_type !== 'product') {
            return $tabs;
        }
        
        // Don't show tab for sample products
        if (self::is_sample_product($post->ID)) {
            return $tabs;
        }
        
        $tabs['sample_product'] = [
            'label'    => __('Sample', 'cht-sample-products'),
            'target'   => 'sample_product_data',
            'class'    => ['show_if_simple', 'show_if_variable'],
            'priority' => 100,
        ];
        
        return $tabs;
    }

    public static function add_product_data_tab_content() {
        global $post;
        
        // Skip if we don't have a product
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        // Don't show content for sample products
        if (self::is_sample_product($post->ID)) {
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'glint_sample_product';
        $sample_id = $wpdb->get_var($wpdb->prepare(
            "SELECT sample_product_id FROM $table WHERE original_product_id = %d", 
            $post->ID
        ));
        ?>
        <div id="sample_product_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php if ($sample_id) : ?>
                    <p class="form-field">
                        <label><?php esc_html_e('Sample Status:', 'cht-sample-products'); ?></label>
                        <span style="display: inline-block; padding: 5px 0;">
                            <?php esc_html_e('Active', 'cht-sample-products'); ?>
                        </span>
                    </p>
                    <p class="form-field">
                        <a href="<?php echo esc_url(get_edit_post_link($sample_id)); ?>" 
                            class="button button-primary">
                            <?php esc_html_e('Edit Sample Product', 'cht-sample-products'); ?>
                        </a>
                        <button type="button" class="button button-secondary cht-delete-sample" 
                            data-product-id="<?php echo esc_attr($post->ID); ?>">
                            <?php esc_html_e('Delete Sample', 'cht-sample-products'); ?>
                        </button>
                    </p>
                <?php else : ?>
                    <p class="form-field">
                        <label><?php esc_html_e('Sample Status:', 'cht-sample-products'); ?></label>
                        <span style="display: inline-block; padding: 5px 0; color: #dc3232;">
                            <?php esc_html_e('No sample created', 'cht-sample-products'); ?>
                        </span>
                    </p>
                    <p class="form-field">
                        <button type="button" class="button button-primary cht-create-sample" 
                            data-product-id="<?php echo esc_attr($post->ID); ?>">
                            <?php esc_html_e('Create Sample Product', 'cht-sample-products'); ?>
                        </button>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public static function create_sample_product() {
        global $wpdb;
        $original_id = absint($_POST['product_id']);
        
        // Get settings
        $settings_table = $wpdb->prefix . 'glint_sample_product_setting';
        $settings = $wpdb->get_results("SELECT setting_name, setting_value FROM $settings_table", OBJECT_K);
        $settings = array_column($settings, 'setting_value', 'setting_name');
        
        // Get original product
        $original = wc_get_product($original_id);
        
        if (!$original) {
            wp_send_json_error(['message' => 'Invalid product']);
        }
        
        // Create sample product
        $sample = new WC_Product();
        $sample->set_name('Sample of ' . $original->get_name());
        $sample->set_description($original->get_description());
        $sample->set_short_description($original->get_short_description());
        $sample->set_price($settings['sample_price']);
        $sample->set_regular_price($settings['sample_price']);
        $sample->set_manage_stock(false);
        $sample->set_stock_status('instock');
        $sample->set_status('publish');
        
        // Set category
        if ($settings['sample_category']) {
            $sample->set_category_ids([absint($settings['sample_category'])]);
        }
        
        // Set shipping class
        if ($settings['sample_shipping_class']) {
            $sample->set_shipping_class_id(absint($settings['sample_shipping_class']));
        }
        
        // Copy images
        $image_id = $original->get_image_id();
        $gallery_ids = $original->get_gallery_image_ids();
        
        if ($image_id) {
            $sample->set_image_id($image_id);
        }
        if (!empty($gallery_ids)) {
            $sample->set_gallery_image_ids($gallery_ids);
        }
        
        $sample_id = $sample->save();
        
        // Save relationship
        $table = $wpdb->prefix . 'glint_sample_product';
        $wpdb->insert($table, [
            'original_product_id' => $original_id,
            'sample_product_id' => $sample_id
        ], ['%d', '%d']);
        
        wp_send_json_success([
            'edit_url' => get_edit_post_link($sample_id, '')
        ]);
    }

    public static function delete_sample_product() {
        global $wpdb;
        $original_id = absint($_POST['product_id']);
        $table = $wpdb->prefix . 'glint_sample_product';
        
        // Get sample ID
        $sample_id = $wpdb->get_var($wpdb->prepare(
            "SELECT sample_product_id FROM $table WHERE original_product_id = %d", 
            $original_id
        ));
        
        if ($sample_id) {
            // Delete sample product
            wp_delete_post($sample_id, true);
            
            // Delete relationship
            $wpdb->delete($table, ['original_product_id' => $original_id], ['%d']);
        }
        
        wp_send_json_success();
    }
}
