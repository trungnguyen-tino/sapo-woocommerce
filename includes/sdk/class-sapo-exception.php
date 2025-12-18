<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Exception extends Exception {
    
    protected $response_data;
    
    public function __construct($message = '', $code = 0, $response_data = null, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->response_data = $response_data;
    }
    
    public function get_response_data() {
        return $this->response_data;
    }
}

class Sapo_Auth_Exception extends Sapo_Exception {}

class Sapo_Rate_Limit_Exception extends Sapo_Exception {}

class Sapo_API_Exception extends Sapo_Exception {}
