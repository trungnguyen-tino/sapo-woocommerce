<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Customer_Resource {
    
    private $client;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function all($params = []) {
        $default_params = [
            'limit' => 50,
            'page' => 1
        ];
        
        $params = array_merge($default_params, $params);
        
        return $this->client->request('GET', '/admin/customers.json', $params);
    }
    
    public function get($customer_id, $fields = []) {
        $params = [];
        
        if (!empty($fields)) {
            $params['fields'] = implode(',', $fields);
        }
        
        return $this->client->request('GET', "/admin/customers/{$customer_id}.json", $params);
    }
    
    public function create($customer_data) {
        return $this->client->request('POST', '/admin/customers.json', [], [
            'customer' => $customer_data
        ]);
    }
    
    public function update($customer_id, $customer_data) {
        return $this->client->request('PUT', "/admin/customers/{$customer_id}.json", [], [
            'customer' => $customer_data
        ]);
    }
    
    public function delete($customer_id) {
        return $this->client->request('DELETE', "/admin/customers/{$customer_id}.json");
    }
    
    public function search($query, $params = []) {
        $params['query'] = $query;
        
        return $this->all($params);
    }
    
    public function get_by_phone($phone_number) {
        return $this->search($phone_number);
    }
    
    public function get_by_email($email) {
        return $this->search($email);
    }
    
    public function get_addresses($customer_id) {
        return $this->client->request('GET', "/admin/customers/{$customer_id}/addresses.json");
    }
    
    public function create_address($customer_id, $address_data) {
        return $this->client->request('POST', "/admin/customers/{$customer_id}/addresses.json", [], [
            'address' => $address_data
        ]);
    }
    
    public function update_address($customer_id, $address_id, $address_data) {
        return $this->client->request('PUT', "/admin/customers/{$customer_id}/addresses/{$address_id}.json", [], [
            'address' => $address_data
        ]);
    }
    
    public function delete_address($customer_id, $address_id) {
        return $this->client->request('DELETE', "/admin/customers/{$customer_id}/addresses/{$address_id}.json");
    }
}
