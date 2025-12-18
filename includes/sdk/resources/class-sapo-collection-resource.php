<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Collection_Resource {
    
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
        
        return $this->client->request('GET', '/admin/collections.json', $params);
    }
    
    public function get($collection_id, $fields = []) {
        $params = [];
        
        if (!empty($fields)) {
            $params['fields'] = implode(',', $fields);
        }
        
        return $this->client->request('GET', "/admin/collections/{$collection_id}.json", $params);
    }
    
    public function count($params = []) {
        return $this->client->request('GET', '/admin/collections/count.json', $params);
    }
}
