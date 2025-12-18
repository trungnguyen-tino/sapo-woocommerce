<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Config {
    
    public function get_config() {
        check_ajax_referer('wp_rest', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        
        $config = [
            'store' => get_option('sapo_sync_store', ''),
            'client_id' => get_option('sapo_sync_client_id', ''),
            'connected' => !empty(get_option('sapo_sync_access_token', '')),
            'auto_inventory' => get_option('sapo_sync_auto_inventory', false),
            'sync_interval' => get_option('sapo_sync_interval', 900),
            'update_price' => get_option('sapo_sync_update_price', true),
            'update_stock' => get_option('sapo_sync_update_stock', true),
            'update_images' => get_option('sapo_sync_update_images', false),
            'webhook_enabled' => get_option('sapo_sync_webhook_enabled', false),
            'debug_mode' => get_option('sapo_sync_debug_mode', false)
        ];
        
        wp_send_json_success($config);
    }
    
    public function save_config() {
        check_ajax_referer('wp_rest', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
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
        
        wp_send_json_success(['message' => 'Configuration saved successfully']);
    }
    
    public function remove_config() {
        check_ajax_referer('wp_rest', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }
        
        delete_option('sapo_sync_access_token');
        delete_option('sapo_sync_refresh_token');
        delete_option('sapo_sync_token_expires');
        
        wp_send_json_success(['message' => 'Disconnected successfully']);
    }
}
