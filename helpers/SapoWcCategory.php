<?php

if (!defined('ABSPATH')) {
    exit;
}

class SapoWcCategory {
    
    public function get_or_create_category($category_name) {
        if (empty($category_name)) {
            return false;
        }
        
        $term = term_exists($category_name, 'product_cat');
        
        if ($term) {
            return $term['term_id'];
        }
        
        $result = wp_insert_term($category_name, 'product_cat');
        
        if (is_wp_error($result)) {
            return false;
        }
        
        return $result['term_id'];
    }
    
    public function get_all_categories() {
        return get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false
        ]);
    }
}
