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
    
    public function create($product_data, $location_id = null) {
        $headers = [];
        
        if ($location_id) {
            $headers['X-Sapo-LocationId'] = $location_id;
        }
        
        return $this->client->request('POST', '/admin/products.json', [], [
            'product' => $product_data
        ], $headers);
    }
    
    public function update($product_id, $product_data) {
        return $this->client->request('PUT', "/admin/products/{$product_id}.json", [], [
            'product' => $product_data
        ]);
    }
    
    public function delete($product_id) {
        return $this->client->request('DELETE', "/admin/products/{$product_id}.json");
    }
    
    public function create_image($product_id, $image_data) {
        return $this->client->request('POST', "/admin/products/{$product_id}/images.json", [], [
            'image' => $image_data
        ]);
    }
    
    public function delete_image($product_id, $image_id) {
        return $this->client->request('DELETE', "/admin/products/{$product_id}/images/{$image_id}.json");
    }
}
