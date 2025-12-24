<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Location_Resource {
    
    private $client;
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function all($params = []) {
        return $this->client->request('GET', '/admin/locations.json', $params);
    }
    
    public function get($location_id) {
        return $this->client->request('GET', "/admin/locations/{$location_id}.json");
    }
    
    public function get_primary() {
        $result = $this->all();
        
        if (isset($result['locations'])) {
            foreach ($result['locations'] as $location) {
                if (!empty($location['is_primary'])) {
                    return $location;
                }
            }
            
            return !empty($result['locations'][0]) ? $result['locations'][0] : null;
        }
        
        return null;
    }
}
