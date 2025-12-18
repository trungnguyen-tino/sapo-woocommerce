<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Sync {
    
    private $client;
    
    public function __construct() {
        $this->client = new Sapo_Client();
    }
    
    public function sync_inventory_from_sapo() {
        try {
            $synced_products = Sapo_DB::get_all_synced_products(100, 0);
            
            if (empty($synced_products)) {
                return [
                    'success' => true,
                    'message' => 'No products to sync',
                    'updated' => 0
                ];
            }
            
            $updated_count = 0;
            
            foreach ($synced_products as $mapping) {
                try {
                    if ($mapping->product_type === 'variation' && $mapping->sapo_variant_id) {
                        $variant_data = $this->client->variants()->get($mapping->sapo_variant_id);
                        
                        if (!empty($variant_data['variant'])) {
                            $this->update_wc_stock($mapping->wc_product_id, $variant_data['variant']['inventory_quantity']);
                            $updated_count++;
                        }
                    } else {
                        $product_data = $this->client->products()->get($mapping->sapo_product_id);
                        
                        if (!empty($product_data['product']['variants'])) {
                            $variant = $product_data['product']['variants'][0];
                            $this->update_wc_stock($mapping->wc_product_id, $variant['inventory_quantity']);
                            $updated_count++;
                        }
                    }
                    
                    Sapo_DB::update_product_mapping($mapping->id, [
                        'last_synced' => current_time('mysql')
                    ]);
                    
                } catch (Exception $e) {
                    Sapo_Service_Log::log(
                        'sync',
                        $mapping->sapo_product_id,
                        $mapping->wc_product_id,
                        'sync_inventory',
                        'error',
                        $e->getMessage()
                    );
                }
            }
            
            Sapo_Service_Log::log(
                'sync',
                0,
                0,
                'sync_inventory',
                'success',
                "Updated {$updated_count} products"
            );
            
            return [
                'success' => true,
                'message' => 'Inventory synced successfully',
                'updated' => $updated_count
            ];
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('sync', 0, 0, 'sync_inventory', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    private function update_wc_stock($wc_product_id, $stock_quantity) {
        $product = wc_get_product($wc_product_id);
        
        if (!$product) {
            return false;
        }
        
        $current_stock = $product->get_stock_quantity();
        
        if ($current_stock != $stock_quantity) {
            $product->set_stock_quantity($stock_quantity);
            $product->set_manage_stock(true);
            
            if ($stock_quantity > 0) {
                $product->set_stock_status('instock');
            } else {
                $product->set_stock_status('outofstock');
            }
            
            $product->save();
            
            return true;
        }
        
        return false;
    }
    
    public function get_sync_status() {
        $last_sync = get_option('sapo_last_sync_time', 0);
        $is_running = get_transient('sapo_sync_running');
        
        return [
            'is_running' => (bool) $is_running,
            'last_sync' => $last_sync,
            'last_sync_formatted' => $last_sync ? date('Y-m-d H:i:s', $last_sync) : 'Never',
            'total_synced' => Sapo_DB::get_synced_products_count()
        ];
    }
    
    public function start_manual_sync() {
        if (get_transient('sapo_sync_running')) {
            throw new Exception('Sync is already running');
        }
        
        set_transient('sapo_sync_running', true, 300);
        
        try {
            $result = $this->sync_inventory_from_sapo();
            
            update_option('sapo_last_sync_time', time());
            delete_transient('sapo_sync_running');
            
            return $result;
            
        } catch (Exception $e) {
            delete_transient('sapo_sync_running');
            throw $e;
        }
    }
}
