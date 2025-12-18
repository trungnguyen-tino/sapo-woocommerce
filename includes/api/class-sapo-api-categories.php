<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_API_Categories {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('sapo/v1', '/categories/mappings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_mappings'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/categories/mappings', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_mapping'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/categories/mappings/(?P<collection_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_mapping'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/categories/sapo-collections', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sapo_collections'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/categories/wc-categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_wc_categories'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission() {
        return current_user_can('manage_woocommerce');
    }
    
    public function get_mappings($request) {
        try {
            $mappings = Sapo_DB::get_all_category_mappings();
            
            return rest_ensure_response([
                'success' => true,
                'mappings' => $mappings
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function save_mapping($request) {
        try {
            $data = $request->get_json_params();
            
            $sapo_collection_id = absint($data['sapo_collection_id'] ?? 0);
            $sapo_collection_name = sanitize_text_field($data['sapo_collection_name'] ?? '');
            $wc_category_id = absint($data['wc_category_id'] ?? 0);
            $wc_category_name = sanitize_text_field($data['wc_category_name'] ?? '');
            $auto_create = (bool) ($data['auto_create'] ?? false);
            
            if (empty($sapo_collection_id) || empty($wc_category_id)) {
                return new WP_Error('invalid_data', 'Missing collection or category ID', ['status' => 400]);
            }
            
            Sapo_DB::save_category_mapping(
                $sapo_collection_id,
                $sapo_collection_name,
                $wc_category_id,
                $wc_category_name,
                $auto_create
            );
            
            Sapo_Service_Log::log(
                'config',
                0,
                0,
                'category_mapping_saved',
                'success',
                "Mapped SAPO collection {$sapo_collection_id} to WC category {$wc_category_id}"
            );
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Mapping saved successfully'
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function delete_mapping($request) {
        try {
            $collection_id = $request->get_param('collection_id');
            
            Sapo_DB::delete_category_mapping($collection_id);
            
            Sapo_Service_Log::log(
                'config',
                0,
                0,
                'category_mapping_deleted',
                'success',
                "Deleted mapping for collection {$collection_id}"
            );
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Mapping deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_sapo_collections($request) {
        try {
            $client = new Sapo_Client();
            $result = $client->collections()->all(['limit' => 250]);
            
            return rest_ensure_response([
                'success' => true,
                'collections' => $result['collections'] ?? []
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_wc_categories($request) {
        try {
            $categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            
            if (is_wp_error($categories)) {
                throw new Exception($categories->get_error_message());
            }
            
            $formatted = array_map(function($cat) {
                return [
                    'id' => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'count' => $cat->count
                ];
            }, $categories);
            
            return rest_ensure_response([
                'success' => true,
                'categories' => $formatted
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
