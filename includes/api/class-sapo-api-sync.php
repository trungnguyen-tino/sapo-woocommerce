<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_API_Sync {
    
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
        register_rest_route('sapo/v1', '/sync/inventory', array(
            'methods' => 'POST',
            'callback' => array($this, 'sync_inventory'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/sync/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/logs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_logs'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/logs/clear', array(
            'methods' => 'POST',
            'callback' => array($this, 'clear_logs'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function check_permission() {
        return current_user_can('manage_woocommerce');
    }
    
    public function sync_inventory($request) {
        try {
            $service = new Sapo_Service_Sync();
            $result = $service->start_manual_sync();
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('sync_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_status($request) {
        try {
            $service = new Sapo_Service_Sync();
            $status = $service->get_sync_status();
            
            return rest_ensure_response($status);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_logs($request) {
        try {
            $filters = [];
            
            if ($request->get_param('log_type')) {
                $filters['log_type'] = $request->get_param('log_type');
            }
            
            if ($request->get_param('status')) {
                $filters['status'] = $request->get_param('status');
            }
            
            if ($request->get_param('date_from')) {
                $filters['date_from'] = $request->get_param('date_from');
            }
            
            if ($request->get_param('date_to')) {
                $filters['date_to'] = $request->get_param('date_to');
            }
            
            $limit = $request->get_param('limit') ?? 50;
            $offset = $request->get_param('offset') ?? 0;
            
            $logs = Sapo_Service_Log::get_logs($filters, $limit, $offset);
            $total = Sapo_Service_Log::get_logs_count($filters);
            
            return rest_ensure_response([
                'logs' => $logs,
                'total' => $total
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function clear_logs($request) {
        try {
            $data = $request->get_json_params();
            $days = $data['days'] ?? null;
            
            if ($days) {
                Sapo_Service_Log::clear_logs($days);
            } else {
                Sapo_Service_Log::clear_all_logs();
            }
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Logs cleared successfully'
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function handle_webhook($request) {
        try {
            $service = new Sapo_Service_Webhook();
            $result = $service->handle_webhook($request);
            
            return rest_ensure_response($result);
            
        } catch (Exception $e) {
            return new WP_Error('webhook_error', $e->getMessage(), ['status' => 500]);
        }
    }
}
