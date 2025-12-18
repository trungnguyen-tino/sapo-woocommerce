<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_OAuth {
    
    private $client;
    
    const AUTH_PATH = '/admin/oauth/authorize';
    const TOKEN_PATH = '/admin/oauth/access_token';
    
    public function __construct($client) {
        $this->client = $client;
    }
    
    public function get_authorization_url($scopes = [], $redirect_uri = '', $state = '') {
        $store = $this->client->get_store();
        
        if (empty($store)) {
            throw new Sapo_Auth_Exception('Store name is required');
        }
        
        $params = [
            'client_id' => $this->client->get_client_id(),
            'scope' => implode(',', $scopes),
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code'
        ];
        
        if (!empty($state)) {
            $params['state'] = $state;
        }
        
        $base_url = $this->get_base_url($store);
        return $base_url . self::AUTH_PATH . '?' . http_build_query($params);
    }
    
    public function exchange_code_for_token($code, $redirect_uri) {
        $store = $this->client->get_store();
        $base_url = $this->get_base_url($store);
        
        $data = [
            'client_id' => $this->client->get_client_id(),
            'client_secret' => $this->client->get_client_secret(),
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        ];
        
        $response = $this->make_token_request($base_url . self::TOKEN_PATH, $data);
        
        return $response;
    }
    
    public function refresh_token($refresh_token) {
        $store = $this->client->get_store();
        $base_url = $this->get_base_url($store);
        
        $data = [
            'client_id' => $this->client->get_client_id(),
            'client_secret' => $this->client->get_client_secret(),
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ];
        
        $response = $this->make_token_request($base_url . self::TOKEN_PATH, $data);
        
        return $response;
    }
    
    private function make_token_request($url, $data) {
        $args = [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode($data),
            'timeout' => 30
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Sapo_Auth_Exception('Token request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            $error_message = isset($data['error_description']) ? $data['error_description'] : 'Unknown error';
            throw new Sapo_Auth_Exception('Token request failed: ' . $error_message, $status_code);
        }
        
        return $data;
    }
    
    private function get_base_url($store) {
        if (strpos($store, 'http') === 0) {
            return rtrim($store, '/');
        }
        
        return 'https://' . $store;
    }
}
