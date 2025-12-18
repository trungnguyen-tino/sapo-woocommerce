<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Auth {
    
    public function get_auth_status() {
        check_ajax_referer('wp_rest', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        
        $access_token = get_option('sapo_sync_access_token', '');
        
        wp_send_json_success([
            'connected' => !empty($access_token),
            'store' => get_option('sapo_sync_store', '')
        ]);
    }
    
    public function get_authorization_url() {
        check_ajax_referer('wp_rest', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        
        try {
            $client = new Sapo_Client();
            
            $redirect_uri = admin_url('admin.php?page=sapo-sync&action=oauth-callback');
            $state = wp_create_nonce('sapo_oauth_state');
            $scopes = ['read_products', 'read_inventory'];
            
            $auth_url = $client->get_authorization_url($scopes, $redirect_uri, $state);
            
            update_option('sapo_oauth_state', $state);
            
            wp_send_json_success([
                'auth_url' => $auth_url
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }
    
    public function handle_oauth_callback() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Unauthorized');
        }
        
        if (empty($_GET['code'])) {
            wp_die('Missing authorization code');
        }
        
        $code = sanitize_text_field($_GET['code']);
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        
        $saved_state = get_option('sapo_oauth_state', '');
        
        if ($state !== $saved_state) {
            wp_die('Invalid state parameter');
        }
        
        try {
            $client = new Sapo_Client();
            $redirect_uri = admin_url('admin.php?page=sapo-sync&action=oauth-callback');
            
            $token_data = $client->complete_oauth($code, $redirect_uri);
            
            delete_option('sapo_oauth_state');
            
            Sapo_Service_Log::log('auth', 0, 0, 'oauth_connected', 'success', 'OAuth connection successful');
            
            wp_redirect(admin_url('admin.php?page=sapo-sync&message=connected'));
            exit;
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('auth', 0, 0, 'oauth_failed', 'error', $e->getMessage());
            
            wp_redirect(admin_url('admin.php?page=sapo-sync&message=error&error=' . urlencode($e->getMessage())));
            exit;
        }
    }
    
    public function disconnect() {
        check_ajax_referer('wp_rest', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        
        delete_option('sapo_sync_access_token');
        delete_option('sapo_sync_refresh_token');
        delete_option('sapo_sync_token_expires');
        
        Sapo_Service_Log::log('auth', 0, 0, 'disconnected', 'success', 'Disconnected from SAPO');
        
        wp_send_json_success(['message' => 'Disconnected successfully']);
    }
}
