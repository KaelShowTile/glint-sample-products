<?php
class CHT_Sample_Products_Activator {
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_settings = $wpdb->prefix . 'glint_sample_product_setting';
        $table_products = $wpdb->prefix . 'glint_sample_product';

        $sql_settings = "CREATE TABLE $table_settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(100) NOT NULL,
            setting_value longtext NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_products = "CREATE TABLE $table_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_product_id bigint(20) NOT NULL,
            sample_product_id bigint(20) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_settings);
        dbDelta($sql_products);

        // Set default settings
        $defaults = [
            'sample_category' => '',
            'sample_price' => '0',
            'sample_shipping_class' => '',
            'after_add_to_cart' => '',
            'custom_action' => ''
        ];
        
        foreach ($defaults as $name => $value) {
            $wpdb->replace($table_settings, [
                'setting_name' => $name,
                'setting_value' => $value
            ]);
        }
    }
}