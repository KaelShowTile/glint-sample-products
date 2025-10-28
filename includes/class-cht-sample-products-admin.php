<?php
class CHT_Sample_Products_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu'], 99);
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Sample Products Settings', 'cht-sample-products'),
            __('Sample Products', 'cht-sample-products'),
            'manage_options',
            'cht-sample-products',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function render_settings_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'glint_sample_product_setting';
        
        // Handle form submission
        if (isset($_POST['submit'])) {
            check_admin_referer('cht_sample_settings_save');
            
            $settings = [
                'sample_category' => absint($_POST['sample_category']),
                'sample_price' => wc_format_decimal($_POST['sample_price']),
                'sample_shipping_class' => absint($_POST['sample_shipping_class']),
                'after_add_to_cart' => sanitize_text_field($_POST['after_add_to_cart']),
                'custom_action' => sanitize_text_field($_POST['custom_action'])
            ];
            
            foreach ($settings as $name => $value) {
                $wpdb->update(
                    $table,
                    ['setting_value' => $value],
                    ['setting_name' => $name]
                );
            }
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }

        // Get current settings
        $settings = $wpdb->get_results("SELECT setting_name, setting_value FROM $table", OBJECT_K);
        $settings = array_column($settings, 'setting_value', 'setting_name');

        // Set defaults
        $after_add_to_cart = $settings['after_add_to_cart'] ?? 'redirect';
        $custom_action = $settings['custom_action'] ?? '';
        
        // Get categories and shipping classes
        $categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ]);
        
        $shipping_classes = get_terms([
            'taxonomy' => 'product_shipping_class',
            'hide_empty' => false
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Sample Products Settings', 'cht-sample-products'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('cht_sample_settings_save'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="sample_category"><?php esc_html_e('Sample Category', 'cht-sample-products'); ?></label>
                        </th>
                        <td>
                            <select name="sample_category" id="sample_category" class="regular-text">
                                <option value=""><?php esc_html_e('Select category', 'cht-sample-products'); ?></option>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($settings['sample_category'], $category->term_id); ?>>
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sample_price"><?php esc_html_e('Sample Price', 'cht-sample-products'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="sample_price" id="sample_price" 
                                value="<?php echo esc_attr($settings['sample_price']); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sample_shipping_class"><?php esc_html_e('Shipping Class', 'cht-sample-products'); ?></label>
                        </th>
                        <td>
                            <select name="sample_shipping_class" id="sample_shipping_class" class="regular-text">
                                <option value=""><?php esc_html_e('No shipping class', 'cht-sample-products'); ?></option>
                                <?php foreach ($shipping_classes as $class) : ?>
                                    <option value="<?php echo esc_attr($class->term_id); ?>" <?php selected($settings['sample_shipping_class'], $class->term_id); ?>>
                                        <?php echo esc_html($class->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="after_add_to_cart"><?php esc_html_e('After Add to Cart', 'cht-sample-products'); ?></label>
                        </th>
                        <td>
                            <select name="after_add_to_cart" id="after_add_to_cart" class="regular-text">
                                <option value="redirect" <?php selected($settings['after_add_to_cart'], 'redirect'); ?>>
                                    <?php esc_html_e('Redirect to cart page', 'cht-sample-products'); ?>
                                </option>
                                <option value="custom" <?php selected($settings['after_add_to_cart'], 'custom'); ?>>
                                    <?php esc_html_e('Trigger custom action', 'cht-sample-products'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr id="custom_action_row" style="<?php echo $settings['after_add_to_cart'] === 'custom' ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <label for="custom_action"><?php esc_html_e('Custom Action Name', 'cht-sample-products'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="custom_action" id="custom_action" 
                                value="<?php echo esc_attr($settings['custom_action']); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Enter the name of your custom JavaScript function', 'cht-sample-products'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <script>
            jQuery(document).ready(function($) {
                $('#after_add_to_cart').change(function() {
                    if ($(this).val() === 'custom') {
                        $('#custom_action_row').show();
                    } else {
                        $('#custom_action_row').hide();
                    }
                });
            });
            </script>

        </div>
        <?php
    }
}