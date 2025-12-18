<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Rate_Limiter {
    
    private $minute_limit = 40;
    private $daily_limit = 80000;
    private $request_log = [];
    
    public function __construct() {
        $this->load_request_log();
    }
    
    private function load_request_log() {
        $log = get_transient('sapo_rate_limiter_log');
        if ($log && is_array($log)) {
            $this->request_log = $log;
        }
    }
    
    private function save_request_log() {
        set_transient('sapo_rate_limiter_log', $this->request_log, DAY_IN_SECONDS);
    }
    
    public function check_and_wait() {
        $this->clean_old_requests();
        
        $minute_requests = $this->count_requests_in_window(60);
        $daily_requests = $this->count_requests_in_window(86400);
        
        if ($minute_requests >= $this->minute_limit) {
            $oldest_request = min($this->get_requests_in_window(60));
            $wait_time = 60 - (time() - $oldest_request) + 1;
            
            if ($wait_time > 0) {
                sleep($wait_time);
            }
        }
        
        if ($daily_requests >= $this->daily_limit) {
            throw new Sapo_Rate_Limit_Exception('Daily rate limit exceeded (80,000 requests/day)');
        }
    }
    
    public function log_request() {
        $this->request_log[] = time();
        $this->save_request_log();
    }
    
    private function clean_old_requests() {
        $one_day_ago = time() - 86400;
        $this->request_log = array_filter($this->request_log, function($timestamp) use ($one_day_ago) {
            return $timestamp > $one_day_ago;
        });
    }
    
    private function get_requests_in_window($seconds) {
        $threshold = time() - $seconds;
        return array_filter($this->request_log, function($timestamp) use ($threshold) {
            return $timestamp > $threshold;
        });
    }
    
    private function count_requests_in_window($seconds) {
        return count($this->get_requests_in_window($seconds));
    }
    
    public function get_remaining_quota() {
        $this->clean_old_requests();
        
        return [
            'minute_remaining' => max(0, $this->minute_limit - $this->count_requests_in_window(60)),
            'minute_limit' => $this->minute_limit,
            'daily_remaining' => max(0, $this->daily_limit - $this->count_requests_in_window(86400)),
            'daily_limit' => $this->daily_limit
        ];
    }
}
