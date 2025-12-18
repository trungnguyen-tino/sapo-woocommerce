<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Product {
    
    private $client;
    
    public function __construct() {
        $this->client = new Sapo_Client();
    }
    
    public function get_products($params = []) {
        try {
            $default_params = [
                'limit' => 50,
                'page' => 1
            ];
            
            $params = array_merge($default_params, $params);
            
            $result = $this->client->products()->all($params);
            
            return $result;
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('product', 0, 0, 'get_products', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    public function get_product($product_id) {
        try {
            $result = $this->client->products()->get($product_id);
            
            return $result;
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('product', $product_id, 0, 'get_product', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    public function sync_product($sapo_product_id) {
        try {
            $product_data = $this->client->products()->get($sapo_product_id);
            
            if (empty($product_data['product'])) {
                throw new Exception('Product not found in SAPO');
            }
            
            $product = $product_data['product'];
            
            $mapping = Sapo_DB::get_product_mapping($sapo_product_id);
            
            if ($mapping) {
                $wc_product_id = $this->update_wc_product($product, $mapping->wc_product_id);
            } else {
                $wc_product_id = $this->create_wc_product($product);
            }
            
            Sapo_Service_Log::log(
                'product',
                $sapo_product_id,
                $wc_product_id,
                'sync_product',
                'success',
                'Product synced successfully'
            );
            
            return [
                'success' => true,
                'wc_product_id' => $wc_product_id,
                'message' => 'Product synced successfully'
            ];
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('product', $sapo_product_id, 0, 'sync_product', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    public function sync_products($product_ids) {
        $results = [];
        
        foreach ($product_ids as $product_id) {
            try {
                $result = $this->sync_product($product_id);
                $results[] = [
                    'sapo_product_id' => $product_id,
                    'success' => true,
                    'wc_product_id' => $result['wc_product_id']
                ];
            } catch (Exception $e) {
                $results[] = [
                    'sapo_product_id' => $product_id,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    private function create_wc_product($sapo_product) {
        $helper = new SapoWcProduct();
        
        $has_variants = isset($sapo_product['variants']) && count($sapo_product['variants']) > 1;
        
        if ($has_variants) {
            $wc_product_id = $helper->create_variable_product($sapo_product);
        } else {
            $wc_product_id = $helper->create_simple_product($sapo_product);
        }
        
        if ($wc_product_id && !is_wp_error($wc_product_id)) {
            $mapping_data = [
                'sapo_product_id' => $sapo_product['id'],
                'wc_product_id' => $wc_product_id,
                'product_type' => $has_variants ? 'variable' : 'simple'
            ];
            
            Sapo_DB::create_product_mapping($mapping_data);
            
            if ($has_variants) {
                foreach ($sapo_product['variants'] as $variant) {
                    $variation_id = $helper->create_variation($wc_product_id, $variant);
                    
                    if ($variation_id && !is_wp_error($variation_id)) {
                        Sapo_DB::create_product_mapping([
                            'sapo_product_id' => $sapo_product['id'],
                            'sapo_variant_id' => $variant['id'],
                            'wc_product_id' => $variation_id,
                            'product_type' => 'variation'
                        ]);
                    }
                }
            }
        }
        
        return $wc_product_id;
    }
    
    private function update_wc_product($sapo_product, $wc_product_id) {
        $helper = new SapoWcProduct();
        
        $update_price = get_option('sapo_sync_update_price', true);
        $update_stock = get_option('sapo_sync_update_stock', true);
        $update_images = get_option('sapo_sync_update_images', false);
        
        $helper->update_product($wc_product_id, $sapo_product, [
            'update_price' => $update_price,
            'update_stock' => $update_stock,
            'update_images' => $update_images
        ]);
        
        $mapping = Sapo_DB::get_product_mapping($sapo_product['id']);
        if ($mapping) {
            Sapo_DB::update_product_mapping($mapping->id, [
                'last_synced' => current_time('mysql')
            ]);
        }
        
        return $wc_product_id;
    }
    
    public function update_product_stock($sapo_product_id) {
        try {
            $product_data = $this->client->products()->get($sapo_product_id);
            
            if (empty($product_data['product'])) {
                throw new Exception('Product not found');
            }
            
            $product = $product_data['product'];
            $mapping = Sapo_DB::get_product_mapping($sapo_product_id);
            
            if (!$mapping) {
                throw new Exception('Product mapping not found');
            }
            
            $helper = new SapoWcProduct();
            $helper->update_stock($mapping->wc_product_id, $product);
            
            Sapo_Service_Log::log(
                'product',
                $sapo_product_id,
                $mapping->wc_product_id,
                'update_stock',
                'success',
                'Stock updated successfully'
            );
            
            return ['success' => true, 'message' => 'Stock updated'];
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('product', $sapo_product_id, 0, 'update_stock', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    public function update_product_price($sapo_product_id) {
        try {
            $product_data = $this->client->products()->get($sapo_product_id);
            
            if (empty($product_data['product'])) {
                throw new Exception('Product not found');
            }
            
            $product = $product_data['product'];
            $mapping = Sapo_DB::get_product_mapping($sapo_product_id);
            
            if (!$mapping) {
                throw new Exception('Product mapping not found');
            }
            
            $helper = new SapoWcProduct();
            $helper->update_price($mapping->wc_product_id, $product);
            
            Sapo_Service_Log::log(
                'product',
                $sapo_product_id,
                $mapping->wc_product_id,
                'update_price',
                'success',
                'Price updated successfully'
            );
            
            return ['success' => true, 'message' => 'Price updated'];
            
        } catch (Exception $e) {
            Sapo_Service_Log::log('product', $sapo_product_id, 0, 'update_price', 'error', $e->getMessage());
            throw $e;
        }
    }
    
    public function delete_mapping($mapping_id) {
        return Sapo_DB::delete_product_mapping($mapping_id);
    }
    
    public function get_synced_products($limit = 50, $offset = 0) {
        return Sapo_DB::get_all_synced_products($limit, $offset);
    }
}
