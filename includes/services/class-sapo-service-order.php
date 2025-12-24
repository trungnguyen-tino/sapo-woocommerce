<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Order {
    
    private $client;
    
    public function __construct() {
        $this->client = new Sapo_Client();
    }
    
    public function sync_order_to_sapo($wc_order_id) {
        try {
            $wc_order = wc_get_order($wc_order_id);
            
            if (!$wc_order) {
                throw new Exception('Order not found');
            }
            
            $mapping = Sapo_DB::get_order_mapping($wc_order_id);
            
            if ($mapping && $mapping->sapo_order_id) {
                Sapo_Service_Log::log(
                    'order',
                    $mapping->sapo_order_id,
                    $wc_order_id,
                    'sync_order',
                    'info',
                    'Order already synced to SAPO'
                );
                
                return [
                    'success' => true,
                    'sapo_order_id' => $mapping->sapo_order_id,
                    'message' => 'Order already synced'
                ];
            }
            
            $sapo_customer_id = $this->get_or_create_customer($wc_order);
            
            $order_helper = new SapoWcOrder();
            $order_data = $order_helper->transform_to_sapo($wc_order);
            
            if ($sapo_customer_id) {
                $order_data['customer_id'] = $sapo_customer_id;
            }
            
            $location_id = $this->get_location_id();
            $account_id = $this->get_account_id();
            
            $result = $this->client->orders()->create($order_data, $location_id, $account_id);
            
            if (empty($result['order']['id'])) {
                throw new Exception('Failed to create order on SAPO');
            }
            
            $sapo_order_id = $result['order']['id'];
            
            Sapo_DB::create_order_mapping([
                'sapo_order_id' => $sapo_order_id,
                'wc_order_id' => $wc_order_id,
                'sync_status' => 'synced'
            ]);
            
            if ($wc_order->get_status() === 'processing' || $wc_order->get_status() === 'completed') {
                $this->finalize_order($sapo_order_id);
            }
            
            Sapo_Service_Log::log(
                'order',
                $sapo_order_id,
                $wc_order_id,
                'sync_order',
                'success',
                'Order synced successfully to SAPO'
            );
            
            return [
                'success' => true,
                'sapo_order_id' => $sapo_order_id,
                'message' => 'Order created on SAPO'
            ];
            
        } catch (Exception $e) {
            Sapo_Service_Log::log(
                'order',
                0,
                $wc_order_id,
                'sync_order',
                'error',
                $e->getMessage()
            );
            
            throw $e;
        }
    }
    
    private function get_or_create_customer($wc_order) {
        $customer_service = new Sapo_Service_Customer();
        
        $wc_customer_id = $wc_order->get_customer_id();
        
        if ($wc_customer_id) {
            $mapping = Sapo_DB::get_customer_mapping_by_wc($wc_customer_id);
            
            if ($mapping && $mapping->sapo_customer_id) {
                return $mapping->sapo_customer_id;
            }
        }
        
        $phone = $wc_order->get_billing_phone();
        $email = $wc_order->get_billing_email();
        
        if ($phone) {
            try {
                $sapo_customer_id = $customer_service->find_customer_by_phone($phone);
                
                if ($sapo_customer_id) {
                    if ($wc_customer_id) {
                        Sapo_DB::create_customer_mapping([
                            'sapo_customer_id' => $sapo_customer_id,
                            'wc_customer_id' => $wc_customer_id
                        ]);
                    }
                    
                    return $sapo_customer_id;
                }
            } catch (Exception $e) {
            }
        }
        
        try {
            $customer_helper = new SapoWcCustomer();
            $customer_data = $customer_helper->transform_from_order($wc_order);
            
            $result = $this->client->customers()->create($customer_data);
            
            if (!empty($result['customer']['id'])) {
                $sapo_customer_id = $result['customer']['id'];
                
                if ($wc_customer_id) {
                    Sapo_DB::create_customer_mapping([
                        'sapo_customer_id' => $sapo_customer_id,
                        'wc_customer_id' => $wc_customer_id
                    ]);
                }
                
                return $sapo_customer_id;
            }
        } catch (Exception $e) {
            Sapo_Service_Log::log(
                'customer',
                0,
                $wc_customer_id,
                'create_customer',
                'error',
                $e->getMessage()
            );
        }
        
        return null;
    }
    
    private function finalize_order($sapo_order_id) {
        try {
            $this->client->orders()->finalize($sapo_order_id);
            
            Sapo_Service_Log::log(
                'order',
                $sapo_order_id,
                0,
                'finalize_order',
                'success',
                'Order finalized on SAPO'
            );
            
            return true;
        } catch (Exception $e) {
            Sapo_Service_Log::log(
                'order',
                $sapo_order_id,
                0,
                'finalize_order',
                'error',
                $e->getMessage()
            );
            
            return false;
        }
    }
    
    public function update_wc_order_from_sapo($sapo_order_id) {
        try {
            $result = $this->client->orders()->get($sapo_order_id);
            
            if (empty($result['order'])) {
                throw new Exception('Order not found on SAPO');
            }
            
            $sapo_order = $result['order'];
            
            $mapping = Sapo_DB::get_order_mapping_by_sapo($sapo_order_id);
            
            if (!$mapping || !$mapping->wc_order_id) {
                return false;
            }
            
            $order_helper = new SapoWcOrder();
            $order_helper->update_wc_order_status($mapping->wc_order_id, $sapo_order['status']);
            
            return true;
            
        } catch (Exception $e) {
            Sapo_Service_Log::log(
                'order',
                $sapo_order_id,
                0,
                'update_from_sapo',
                'error',
                $e->getMessage()
            );
            
            return false;
        }
    }
    
    private function get_location_id() {
        $location_id = get_option('sapo_sync_location_id');
        
        if (!$location_id) {
            try {
                $location = $this->client->locations()->get_primary();
                
                if ($location && isset($location['id'])) {
                    $location_id = $location['id'];
                    update_option('sapo_sync_location_id', $location_id);
                }
            } catch (Exception $e) {
            }
        }
        
        return $location_id;
    }
    
    private function get_account_id() {
        return get_option('sapo_sync_account_id');
    }
}
