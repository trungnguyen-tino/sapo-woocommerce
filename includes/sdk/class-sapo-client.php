<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Client {
    
    private $store;
    private $client_id;
    private $client_secret;
    private $access_token;
    private $refresh_token;
    private $token_expires;
    private $rate_limiter;
    private $oauth_handler;
    
    public function __construct($config = []) {
        $this->store = $config['store'] ?? get_option('sapo_sync_store', '');
        $this->client_id = $config['client_id'] ?? get_option('sapo_sync_client_id', '');
        $this->client_secret = $config['client_secret'] ?? $this->decrypt(get_option('sapo_sync_client_secret', ''));
        $this->access_token = $config['access_token'] ?? $this->decrypt(get_option('sapo_sync_access_token', ''));
        $this->refresh_token = $config['refresh_token'] ?? $this->decrypt(get_option('sapo_sync_refresh_token', ''));
        $this->token_expires = $config['token_expires'] ?? get_option('sapo_sync_token_expires', 0);
        
        $this->rate_limiter = new Sapo_Rate_Limiter();
        $this->oauth_handler = new Sapo_OAuth($this);
    }
    
    public function get_store() {
        return $this->store;
    }
    
    public function get_client_id() {
        return $this->client_id;
    }
    
    public function get_client_secret() {
        return $this->client_secret;
    }
    
    public function get_authorization_url($scopes = [], $redirect_uri = '', $state = '') {
        return $this->oauth_handler->get_authorization_url($scopes, $redirect_uri, $state);
    }
    
    public function complete_oauth($code, $redirect_uri) {
        $token_data = $this->oauth_handler->exchange_code_for_token($code, $redirect_uri);
        
        $this->set_token($token_data);
        
        return $token_data;
    }
    
    public function set_token($token_data) {
        $this->access_token = $token_data['access_token'];
        $this->refresh_token = $token_data['refresh_token'] ?? '';
        
        if (isset($token_data['expires_in'])) {
            $this->token_expires = time() + $token_data['expires_in'];
        }
        
        update_option('sapo_sync_access_token', $this->encrypt($this->access_token));
        update_option('sapo_sync_refresh_token', $this->encrypt($this->refresh_token));
        update_option('sapo_sync_token_expires', $this->token_expires);
    }
    
    public function refresh_access_token() {
        if (empty($this->refresh_token)) {
            throw new Sapo_Auth_Exception('No refresh token available');
        }
        
        $token_data = $this->oauth_handler->refresh_token($this->refresh_token);
        
        $this->set_token($token_data);
        
        return $token_data;
    }
    
    public function is_token_expired() {
        if (empty($this->token_expires)) {
            return true;
        }
        
        return time() >= ($this->token_expires - 300);
    }
    
    public function request($method, $path, $params = [], $data = null, $custom_headers = []) {
        if ($this->is_token_expired() && !empty($this->refresh_token)) {
            $this->refresh_access_token();
        }
        
        if (empty($this->access_token)) {
            throw new Sapo_Auth_Exception('No access token available');
        }
        
        $this->rate_limiter->check_and_wait();
        
        $url = $this->build_url($path, $params);
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json'
        ];
        
        if (!empty($custom_headers)) {
            $headers = array_merge($headers, $custom_headers);
        }
        
        $args = [
            'method' => strtoupper($method),
            'headers' => $headers,
            'timeout' => 30
        ];
        
        if ($data !== null) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        $this->rate_limiter->log_request();
        
        if (is_wp_error($response)) {
            throw new Sapo_API_Exception('Request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $response_data = json_decode($body, true);
        
        if ($status_code === 401) {
            if (!empty($this->refresh_token)) {
                $this->refresh_access_token();
                return $this->request($method, $path, $params, $data, $custom_headers);
            }
            throw new Sapo_Auth_Exception('Unauthorized', 401, $response_data);
        }
        
        if ($status_code === 429) {
            throw new Sapo_Rate_Limit_Exception('Rate limit exceeded', 429, $response_data);
        }
        
        if ($status_code < 200 || $status_code >= 300) {
            $error_message = isset($response_data['errors']) ? json_encode($response_data['errors']) : 'API request failed';
            throw new Sapo_API_Exception($error_message, $status_code, $response_data);
        }
        
        return $response_data;
    }
    
    private function build_url($path, $params = []) {
        $base_url = 'https://' . $this->store;
        $url = $base_url . $path;
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    public function products() {
        return new Sapo_Product_Resource($this);
    }
    
    public function variants() {
        return new Sapo_Variant_Resource($this);
    }
    
    public function collects() {
        return new Sapo_Collect_Resource($this);
    }
    
    public function collections() {
        return new Sapo_Collection_Resource($this);
    }
    
    public function orders() {
        return new Sapo_Order_Resource($this);
    }
    
    public function customers() {
        return new Sapo_Customer_Resource($this);
    }
    
    public function locations() {
        return new Sapo_Location_Resource($this);
    }
    
    private function encrypt($value) {
        if (empty($value)) {
            return '';
        }
        
        return base64_encode($value);
    }
    
    private function decrypt($value) {
        if (empty($value)) {
            return '';
        }
        
        return base64_decode($value);
    }
}
