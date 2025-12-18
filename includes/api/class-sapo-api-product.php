<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_API_Product {
    
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
        register_rest_route('sapo/v1', '/products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_products'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/products/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/products/sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_products'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/products/synced', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_synced_products'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/products/(?P<id>\d+)/update-stock', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_stock'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/products/(?P<id>\d+)/update-price', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_price'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/products/(?P<id>\d+)/re-sync', array(
            'methods' => 'POST',
            'callback' => array($this, 'resync_product'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/products/mapping/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_mapping'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission() {
        return current_user_can('manage_woocommerce');
    }
    
    public function get_products($request) {
        try {
            $params = [
                'limit' => $request->get_param('limit') ?? 50,
                'page' => $request->get_param('page') ?? 1
            ];
            
            if ($request->get_param('search')) {
                $params['query'] = $request->get_param('search');
            }
            
            $service = new Sapo_Service_Product();
            $result = $service->get_products($params);
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_product($request) {
        try {
            $product_id = $request->get_param('id');
            
            $service = new Sapo_Service_Product();
            $result = $service->get_product($product_id);
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function sync_products($request) {
        try {
            $data = $request->get_json_params();
            $product_ids = $data['product_ids'] ?? [];
            
            if (empty($product_ids)) {
                return new WP_Error('invalid_data', 'No product IDs provided', ['status' => 400]);
            }
            
            $service = new Sapo_Service_Product();
            $results = $service->sync_products($product_ids);
            
            return rest_ensure_response([
                'success' => true,
                'results' => $results
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_synced_products($request) {
        try {
            $limit = $request->get_param('limit') ?? 50;
            $offset = $request->get_param('offset') ?? 0;
            
            $service = new Sapo_Service_Product();
            $products = $service->get_synced_products($limit, $offset);
            $total = Sapo_DB::get_synced_products_count();
            
            return rest_ensure_response([
                'products' => $products,
                'total' => $total
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function update_stock($request) {
        try {
            $product_id = $request->get_param('id');
            
            $service = new Sapo_Service_Product();
            $result = $service->update_product_stock($product_id);
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function update_price($request) {
        try {
            $product_id = $request->get_param('id');
            
            $service = new Sapo_Service_Product();
            $result = $service->update_product_price($product_id);
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function resync_product($request) {
        try {
            $product_id = $request->get_param('id');
            
            $service = new Sapo_Service_Product();
            $result = $service->sync_product($product_id);
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function delete_mapping($request) {
        try {
            $mapping_id = $request->get_param('id');
            
            $service = new Sapo_Service_Product();
            $service->delete_mapping($mapping_id);
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Mapping deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
