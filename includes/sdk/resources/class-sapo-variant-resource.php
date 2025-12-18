<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Variant_Resource {
    
    private $client;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function all($product_id, $params = []) {
        return $this->client->request('GET', "/admin/products/{$product_id}/variants.json", $params);
    }
    
    public function get($variant_id, $fields = []) {
        $params = [];
        
        if (!empty($fields)) {
            $params['fields'] = implode(',', $fields);
        }
        
        return $this->client->request('GET', "/admin/variants/{$variant_id}.json", $params);
    }
    
    public function count($product_id) {
        return $this->client->request('GET', "/admin/products/{$product_id}/variants/count.json");
    }
    
    public function get_since($product_id, $since_id) {
        $params = [
            'since_id' => $since_id
        ];
        
        return $this->client->request('GET', "/admin/products/{$product_id}/variants.json", $params);
    }
}
