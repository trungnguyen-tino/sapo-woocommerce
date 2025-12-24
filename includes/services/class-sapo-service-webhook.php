<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Webhook {
    
    public function handle_webhook($request) {
        try {
            $body = $request->get_body();
            $topic = $request->get_header('X-Sapo-Topic');
            $hmac = $request->get_header('X-Sapo-Hmac-Sha256');
            
            if (!$this->verify_webhook($body, $hmac)) {
                throw new Exception('Invalid webhook signature');
            }
            
            $data = json_decode($body, true);
            
            global $wpdb;
            $table = $wpdb->prefix . 'sapo_webhooks';
            
            $wpdb->insert($table, [
                'topic' => $topic,
                'payload' => $body,
                'processed' => 0,
                'created_at' => current_time('mysql')
            ]);
            
            $this->process_webhook($topic, $data);
            
            return [
                'success' => true,
                'message' => 'Webhook received'
            ];
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('webhook', 0, 0, 'handle_webhook', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    private function verify_webhook($body, $hmac) {
        $webhook_enabled = get_option('sapo_sync_webhook_enabled', false);
        
        if (!$webhook_enabled) {
            return false;
        }
        
        $client_secret = base64_decode(get_option('sapo_sync_client_secret', ''));
        
        if (empty($client_secret)) {
            return false;
        }
        
        $calculated_hmac = base64_encode(hash_hmac('sha256', $body, $client_secret, true));
        
        return hash_equals($calculated_hmac, $hmac);
    }
    
    private function process_webhook($topic, $data) {
        switch ($topic) {
            case 'products/update':
            case 'products/create':
                $this->handle_product_update($data);
                break;
                
            case 'inventory/update':
                $this->handle_inventory_update($data);
                break;
                
            case 'products/delete':
                $this->handle_product_delete($data);
                break;
                
            case 'orders/create':
            case 'orders/update':
            case 'orders/finalized':
            case 'orders/cancelled':
            case 'orders/fulfilled':
                $this->handle_order_update($data);
                break;
                
            case 'customers/create':
            case 'customers/update':
                $this->handle_customer_update($data);
                break;
        }
    }
    
    private function handle_product_update($data) {
        if (!isset($data['id'])) {
            return;
        }
        
        $product_service = new Sapo_Service_Product();
        
        try {
            $product_service->sync_product($data['id']);
        } catch (Exception $e) {
            Sapo_Service_Log::log('webhook', $data['id'], 0, 'product_update', 'error', $e->getMessage());
        }
    }
    
    private function handle_inventory_update($data) {
        if (!isset($data['id'])) {
            return;
        }
        
        $product_service = new Sapo_Service_Product();
        
        try {
            $product_service->update_product_stock($data['id']);
        } catch (Exception $e) {
            Sapo_Service_Log::log('webhook', $data['id'], 0, 'inventory_update', 'error', $e->getMessage());
        }
    }
    
    private function handle_product_delete($data) {
        if (!isset($data['id'])) {
            return;
        }
        
        $mapping = Sapo_DB::get_product_mapping($data['id']);
        
        if ($mapping) {
            Sapo_Service_Log::log(
                'webhook',
                $data['id'],
                $mapping->wc_product_id,
                'product_deleted',
                'info',
                'Product deleted in SAPO'
            );
        }
    }
    
    private function handle_order_update($data) {
        if (!isset($data['id'])) {
            return;
        }
        
        $order_service = new Sapo_Service_Order();
        
        try {
            $order_service->update_wc_order_from_sapo($data['id']);
        } catch (Exception $e) {
            Sapo_Service_Log::log('webhook', $data['id'], 0, 'order_update', 'error', $e->getMessage());
        }
    }
    
    private function handle_customer_update($data) {
        if (!isset($data['id'])) {
            return;
        }
        
        $customer_service = new Sapo_Service_Customer();
        
        try {
            $customer_service->sync_customer_from_sapo($data['id']);
        } catch (Exception $e) {
            Sapo_Service_Log::log('webhook', $data['id'], 0, 'customer_update', 'error', $e->getMessage());
        }
    }
}
