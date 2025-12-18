<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_API_Attributes {
    
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
        register_rest_route('sapo/v1', '/attributes/mappings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_mappings'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/attributes/mappings', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_mapping'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/attributes/mappings/(?P<option>[a-z0-9]+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_mapping'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission() {
        return current_user_can('manage_woocommerce');
    }
    
    public function get_mappings($request) {
        try {
            $mappings = Sapo_DB::get_all_attribute_mappings();
            
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
            
            $sapo_option = sanitize_text_field($data['sapo_option'] ?? '');
            $wc_attribute_name = sanitize_text_field($data['wc_attribute_name'] ?? '');
            $wc_attribute_slug = sanitize_title($data['wc_attribute_slug'] ?? '');
            
            if (empty($sapo_option) || empty($wc_attribute_name) || empty($wc_attribute_slug)) {
                return new WP_Error('invalid_data', 'Missing required fields', ['status' => 400]);
            }
            
            if (!in_array($sapo_option, ['option1', 'option2', 'option3'])) {
                return new WP_Error('invalid_option', 'Invalid SAPO option', ['status' => 400]);
            }
            
            Sapo_DB::save_attribute_mapping($sapo_option, $wc_attribute_name, $wc_attribute_slug);
            
            Sapo_Service_Log::log(
                'config',
                0,
                0,
                'attribute_mapping_saved',
                'success',
                "Mapped {$sapo_option} to {$wc_attribute_name}"
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
            $sapo_option = $request->get_param('option');
            
            if (!in_array($sapo_option, ['option1', 'option2', 'option3'])) {
                return new WP_Error('invalid_option', 'Invalid SAPO option', ['status' => 400]);
            }
            
            Sapo_DB::delete_attribute_mapping($sapo_option);
            
            Sapo_Service_Log::log(
                'config',
                0,
                0,
                'attribute_mapping_deleted',
                'success',
                "Deleted mapping for {$sapo_option}"
            );
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Mapping deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
