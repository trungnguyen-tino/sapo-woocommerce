<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Product_Resource {
    
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
        
        return $this->client->request('GET', '/admin/products.json', $params);
    }
    
    public function get($product_id, $fields = []) {
        $params = [];
        
        if (!empty($fields)) {
            $params['fields'] = implode(',', $fields);
        }
        
        return $this->client->request('GET', "/admin/products/{$product_id}.json", $params);
    }
    
    public function count($params = []) {
        return $this->client->request('GET', '/admin/products/count.json', $params);
    }
    
    public function get_by_ids($ids = []) {
        if (empty($ids)) {
            return ['products' => []];
        }
        
        $params = [
            'ids' => implode(',', $ids)
        ];
        
        return $this->client->request('GET', '/admin/products.json', $params);
    }
    
    public function search($query, $params = []) {
        $params['query'] = $query;
        
        return $this->all($params);
    }
    
    public function get_by_category($category_id, $params = []) {
        $params['collection_id'] = $category_id;
        
        return $this->all($params);
    }
    
    public function get_since($since_id, $params = []) {
        $params['since_id'] = $since_id;
        
        return $this->all($params);
    }
}
