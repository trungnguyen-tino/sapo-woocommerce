<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_WC_Hooks {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        $enabled = get_option('sapo_sync_wc_to_sapo_enabled', false);
        
        if (!$enabled) {
            return;
        }
        
        add_action('woocommerce_new_order', array($this, 'on_new_order'), 10, 1);
        add_action('woocommerce_order_status_changed', array($this, 'on_order_status_changed'), 10, 4);
        
        add_action('woocommerce_created_customer', array($this, 'on_customer_created'), 10, 1);
        add_action('woocommerce_update_customer', array($this, 'on_customer_updated'), 10, 1);
        
        add_action('woocommerce_update_product', array($this, 'on_product_updated'), 10, 1);
        add_action('woocommerce_new_product', array($this, 'on_product_created'), 10, 1);
    }
    
    public function on_new_order($order_id) {
        try {
            $auto_sync = get_option('sapo_sync_orders_auto', true);
            
            if (!$auto_sync) {
                return;
            }
            
            wp_schedule_single_event(time() + 30, 'sapo_sync_order_to_sapo', array($order_id));
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('hook', 0, $order_id, 'new_order', 'error', $e->getMessage());
        }
    }
    
    public function on_order_status_changed($order_id, $old_status, $new_status, $order) {
        try {
            $sync_status_changes = get_option('sapo_sync_order_status_changes', false);
            
            if (!$sync_status_changes) {
                return;
            }
            
            $mapping = Sapo_DB::get_order_mapping($order_id);
            
            if (!$mapping || !$mapping->sapo_order_id) {
                return;
            }
            
            if ($new_status === 'processing' || $new_status === 'completed') {
                wp_schedule_single_event(time() + 10, 'sapo_finalize_order', array($mapping->sapo_order_id));
            } elseif ($new_status === 'cancelled') {
                wp_schedule_single_event(time() + 10, 'sapo_cancel_order', array($mapping->sapo_order_id));
            }
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('hook', 0, $order_id, 'order_status_changed', 'error', $e->getMessage());
        }
    }
    
    public function on_customer_created($customer_id) {
        try {
            $auto_sync = get_option('sapo_sync_customers_auto', false);
            
            if (!$auto_sync) {
                return;
            }
            
            wp_schedule_single_event(time() + 20, 'sapo_sync_customer_to_sapo', array($customer_id));
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('hook', 0, $customer_id, 'customer_created', 'error', $e->getMessage());
        }
    }
    
    public function on_customer_updated($customer_id) {
        try {
            $sync_updates = get_option('sapo_sync_customer_updates', false);
            
            if (!$sync_updates) {
                return;
            }
            
            wp_schedule_single_event(time() + 20, 'sapo_sync_customer_to_sapo', array($customer_id));
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('hook', 0, $customer_id, 'customer_updated', 'error', $e->getMessage());
        }
    }
    
    public function on_product_updated($product_id) {
        try {
            $sync_updates = get_option('sapo_sync_product_updates_to_sapo', false);
            
            if (!$sync_updates) {
                return;
            }
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('hook', $product_id, 0, 'product_updated', 'error', $e->getMessage());
        }
    }
    
    public function on_product_created($product_id) {
        try {
            $auto_sync = get_option('sapo_sync_new_products_to_sapo', false);
            
            if (!$auto_sync) {
                return;
            }
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('hook', $product_id, 0, 'product_created', 'error', $e->getMessage());
        }
    }
}

add_action('sapo_sync_order_to_sapo', function($order_id) {
    try {
        $service = new Sapo_Service_Order();
        $service->sync_order_to_sapo($order_id);
    } catch (Exception $e) {
        Sapo_Service_Log::log('cron', 0, $order_id, 'sync_order', 'error', $e->getMessage());
    }
});

add_action('sapo_finalize_order', function($sapo_order_id) {
    try {
        $client = new Sapo_Client();
        $client->orders()->finalize($sapo_order_id);
    } catch (Exception $e) {
        Sapo_Service_Log::log('cron', $sapo_order_id, 0, 'finalize_order', 'error', $e->getMessage());
    }
});

add_action('sapo_cancel_order', function($sapo_order_id) {
    try {
        $client = new Sapo_Client();
        $client->orders()->cancel($sapo_order_id);
    } catch (Exception $e) {
        Sapo_Service_Log::log('cron', $sapo_order_id, 0, 'cancel_order', 'error', $e->getMessage());
    }
});

add_action('sapo_sync_customer_to_sapo', function($customer_id) {
    try {
        $service = new Sapo_Service_Customer();
        $service->sync_customer_to_sapo($customer_id);
    } catch (Exception $e) {
        Sapo_Service_Log::log('cron', 0, $customer_id, 'sync_customer', 'error', $e->getMessage());
    }
});
