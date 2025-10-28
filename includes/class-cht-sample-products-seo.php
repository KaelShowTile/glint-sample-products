<?php
class CHT_Sample_Products_SEO {
    public static function init() {
        // Remove existing robots meta tags
        add_action('wp_head', [__CLASS__, 'remove_existing_robots_tag'], 1);
        
        // Add our custom robots meta
        add_filter('wp_robots', [__CLASS__, 'modify_robots_meta']);
    }

    public static function remove_existing_robots_tag() {
        if (!is_product()) return;
        
        global $post;
        if (self::is_sample_product($post->ID)) {
            // Remove standard robots tag
            remove_action('wp_head', 'wp_robots');
            
            // Remove Yoast SEO robots tag if exists
            add_filter('wpseo_robots', '__return_false');
            
            // Remove Rank Math robots tag if exists
            add_filter('rank_math/frontend/robots', '__return_false');
        }
    }

    public static function modify_robots_meta($robots) {
        if (is_product() && self::is_sample_product(get_queried_object_id())) {
            $robots = [
                'noindex' => true,
                'nofollow' => true
            ];
        }
        return $robots;
    }

    private static function is_sample_product($product_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'glint_sample_product_setting';
        $sample_category = $wpdb->get_var(
            "SELECT setting_value FROM $table WHERE setting_name = 'sample_category'"
        );
        
        if (!$sample_category) return false;
        
        return has_term($sample_category, 'product_cat', $product_id);
    }
}