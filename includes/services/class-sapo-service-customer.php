<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Customer {
    
    private $client;
    
    public function __construct() {
        $this->client = new Sapo_Client();
    }
    
    public function sync_customer_to_sapo($wc_customer_id) {
        try {
            $wc_customer = new WC_Customer($wc_customer_id);
            
            if (!$wc_customer->get_id()) {
                throw new Exception('Customer not found');
            }
            
            $mapping = Sapo_DB::get_customer_mapping_by_wc($wc_customer_id);
            
            if ($mapping && $mapping->sapo_customer_id) {
                return $this->update_customer($mapping->sapo_customer_id, $wc_customer);
            }
            
            return $this->create_customer($wc_customer);
            
        } catch (Exception $e) {
            Sapo_Service_Log::log(
                'customer',
                0,
                $wc_customer_id,
                'sync_customer',
                'error',
                $e->getMessage()
            );
            
            throw $e;
        }
    }
    
    private function create_customer($wc_customer) {
        $customer_helper = new SapoWcCustomer();
        $customer_data = $customer_helper->transform_to_sapo($wc_customer);
        
        $result = $this->client->customers()->create($customer_data);
        
        if (empty($result['customer']['id'])) {
            throw new Exception('Failed to create customer on SAPO');
        }
        
        $sapo_customer_id = $result['customer']['id'];
        
        Sapo_DB::create_customer_mapping([
            'sapo_customer_id' => $sapo_customer_id,
            'wc_customer_id' => $wc_customer->get_id()
        ]);
        
        Sapo_Service_Log::log(
            'customer',
            $sapo_customer_id,
            $wc_customer->get_id(),
            'create_customer',
            'success',
            'Customer created on SAPO'
        );
        
        return [
            'success' => true,
            'sapo_customer_id' => $sapo_customer_id,
            'message' => 'Customer created'
        ];
    }
    
    private function update_customer($sapo_customer_id, $wc_customer) {
        $customer_helper = new SapoWcCustomer();
        $customer_data = $customer_helper->transform_to_sapo($wc_customer);
        
        $result = $this->client->customers()->update($sapo_customer_id, $customer_data);
        
        Sapo_Service_Log::log(
            'customer',
            $sapo_customer_id,
            $wc_customer->get_id(),
            'update_customer',
            'success',
            'Customer updated on SAPO'
        );
        
        return [
            'success' => true,
            'sapo_customer_id' => $sapo_customer_id,
            'message' => 'Customer updated'
        ];
    }
    
    public function find_customer_by_phone($phone) {
        try {
            $result = $this->client->customers()->get_by_phone($phone);
            
            if (!empty($result['customers'][0]['id'])) {
                return $result['customers'][0]['id'];
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function find_customer_by_email($email) {
        try {
            $result = $this->client->customers()->get_by_email($email);
            
            if (!empty($result['customers'][0]['id'])) {
                return $result['customers'][0]['id'];
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function sync_customer_from_sapo($sapo_customer_id) {
        try {
            $result = $this->client->customers()->get($sapo_customer_id);
            
            if (empty($result['customer'])) {
                throw new Exception('Customer not found on SAPO');
            }
            
            $sapo_customer = $result['customer'];
            
            $mapping = Sapo_DB::get_customer_mapping_by_sapo($sapo_customer_id);
            
            if ($mapping && $mapping->wc_customer_id) {
                return $mapping->wc_customer_id;
            }
            
            $customer_helper = new SapoWcCustomer();
            $wc_customer_id = $customer_helper->create_wc_customer($sapo_customer);
            
            return $wc_customer_id;
            
        } catch (Exception $e) {
            Sapo_Service_Log::log(
                'customer',
                $sapo_customer_id,
                0,
                'sync_from_sapo',
                'error',
                $e->getMessage()
            );
            
            throw $e;
        }
    }
}
