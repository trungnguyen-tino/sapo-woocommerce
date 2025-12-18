<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_API_Config {
    
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
        register_rest_route('sapo/v1', '/auth/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_auth_status'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/auth/url', array(
            'methods' => 'POST',
            'callback' => array($this, 'get_auth_url'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/auth/disconnect', array(
            'methods' => 'POST',
            'callback' => array($this, 'disconnect'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/config', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_config'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/config', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_config'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission() {
        return current_user_can('manage_woocommerce');
    }
    
    public function get_auth_status($request) {
        $access_token = get_option('sapo_sync_access_token', '');
        
        return rest_ensure_response([
            'connected' => !empty($access_token),
            'store' => get_option('sapo_sync_store', '')
        ]);
    }
    
    public function get_auth_url($request) {
        try {
            $client = new Sapo_Client();
            
            $redirect_uri = admin_url('admin.php?page=sapo-sync&action=oauth-callback');
            $state = wp_create_nonce('sapo_oauth_state');
            $scopes = ['read_products', 'read_inventory'];
            
            $auth_url = $client->get_authorization_url($scopes, $redirect_uri, $state);
            
            update_option('sapo_oauth_state', $state);
            
            return rest_ensure_response([
                'auth_url' => $auth_url
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('auth_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function disconnect($request) {
        delete_option('sapo_sync_access_token');
        delete_option('sapo_sync_refresh_token');
        delete_option('sapo_sync_token_expires');
        
        Sapo_Service_Log::log('auth', 0, 0, 'disconnected', 'success', 'Disconnected from SAPO');
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Disconnected successfully'
        ]);
    }
    
    public function get_config($request) {
        $config = [
            'store' => get_option('sapo_sync_store', ''),
            'client_id' => get_option('sapo_sync_client_id', ''),
            'connected' => !empty(get_option('sapo_sync_access_token', '')),
            'auto_inventory' => (bool) get_option('sapo_sync_auto_inventory', false),
            'sync_interval' => absint(get_option('sapo_sync_interval', 900)),
            'update_price' => (bool) get_option('sapo_sync_update_price', true),
            'update_stock' => (bool) get_option('sapo_sync_update_stock', true),
            'update_images' => (bool) get_option('sapo_sync_update_images', false),
            'webhook_enabled' => (bool) get_option('sapo_sync_webhook_enabled', false),
            'debug_mode' => (bool) get_option('sapo_sync_debug_mode', false)
        ];
        
        return rest_ensure_response($config);
    }
    
    public function save_config($request) {
        $data = $request->get_json_params();
        
        if (isset($data['store'])) {
            update_option('sapo_sync_store', sanitize_text_field($data['store']));
        }
        
        if (isset($data['client_id'])) {
            update_option('sapo_sync_client_id', sanitize_text_field($data['client_id']));
        }
        
        if (isset($data['client_secret'])) {
            $encrypted = base64_encode(sanitize_text_field($data['client_secret']));
            update_option('sapo_sync_client_secret', $encrypted);
        }
        
        if (isset($data['auto_inventory'])) {
            update_option('sapo_sync_auto_inventory', (bool) $data['auto_inventory']);
        }
        
        if (isset($data['sync_interval'])) {
            update_option('sapo_sync_interval', absint($data['sync_interval']));
        }
        
        if (isset($data['update_price'])) {
            update_option('sapo_sync_update_price', (bool) $data['update_price']);
        }
        
        if (isset($data['update_stock'])) {
            update_option('sapo_sync_update_stock', (bool) $data['update_stock']);
        }
        
        if (isset($data['update_images'])) {
            update_option('sapo_sync_update_images', (bool) $data['update_images']);
        }
        
        if (isset($data['webhook_enabled'])) {
            update_option('sapo_sync_webhook_enabled', (bool) $data['webhook_enabled']);
        }
        
        if (isset($data['debug_mode'])) {
            update_option('sapo_sync_debug_mode', (bool) $data['debug_mode']);
        }
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Configuration saved successfully'
        ]);
    }
}
