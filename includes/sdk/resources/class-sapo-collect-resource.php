<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Collect_Resource {
    
    private $client;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function all($params = []) {
        return $this->client->request('GET', '/admin/collects.json', $params);
    }
    
    public function get_by_product($product_id) {
        $params = [
            'product_id' => $product_id
        ];
        
        return $this->client->request('GET', '/admin/collects.json', $params);
    }
    
    public function get_by_collection($collection_id) {
        $params = [
            'collection_id' => $collection_id
        ];
        
        return $this->client->request('GET', '/admin/collects.json', $params);
    }
    
    public function count($params = []) {
        return $this->client->request('GET', '/admin/collects/count.json', $params);
    }
}
