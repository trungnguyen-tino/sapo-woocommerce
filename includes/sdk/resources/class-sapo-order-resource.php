<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Order_Resource {
    
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
        
        return $this->client->request('GET', '/admin/orders.json', $params);
    }
    
    public function get($order_id, $fields = []) {
        $params = [];
        
        if (!empty($fields)) {
            $params['fields'] = implode(',', $fields);
        }
        
        return $this->client->request('GET', "/admin/orders/{$order_id}.json", $params);
    }
    
    public function create($order_data, $location_id = null, $account_id = null) {
        $headers = [];
        
        if ($location_id) {
            $headers['X-Sapo-LocationId'] = $location_id;
        }
        
        if ($account_id) {
            $headers['X-Sapo-AccountId'] = $account_id;
        }
        
        return $this->client->request('POST', '/admin/orders.json', [], [
            'order' => $order_data
        ], $headers);
    }
    
    public function update($order_id, $order_data, $location_id = null, $account_id = null) {
        $headers = [];
        
        if ($location_id) {
            $headers['X-Sapo-LocationId'] = $location_id;
        }
        
        if ($account_id) {
            $headers['X-Sapo-AccountId'] = $account_id;
        }
        
        return $this->client->request('PUT', "/admin/orders/{$order_id}.json", [], [
            'order' => $order_data
        ], $headers);
    }
    
    public function finalize($order_id) {
        return $this->client->request('POST', "/admin/orders/{$order_id}/finalize.json");
    }
    
    public function cancel($order_id, $reason = '') {
        $data = [];
        if ($reason) {
            $data['reason'] = $reason;
        }
        
        return $this->client->request('POST', "/admin/orders/{$order_id}/cancel.json", [], $data);
    }
    
    public function count($params = []) {
        return $this->client->request('GET', '/admin/orders/count.json', $params);
    }
    
    public function search($query, $params = []) {
        $params['query'] = $query;
        
        return $this->all($params);
    }
    
    public function get_by_ids($ids = []) {
        if (empty($ids)) {
            return ['orders' => []];
        }
        
        $params = [
            'ids' => implode(',', $ids)
        ];
        
        return $this->client->request('GET', '/admin/orders.json', $params);
    }
}
