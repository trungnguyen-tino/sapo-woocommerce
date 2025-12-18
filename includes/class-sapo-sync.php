<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Sync {
    
    public function __construct() {
        $this->init_hooks();
        $this->init_cron();
    }
    
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('admin_notices', array($this, 'check_requirements'));
    }
    
    public function register_rest_routes() {
    }
    
    private function init_cron() {
        $auto_sync = get_option('sapo_sync_auto_inventory', false);
        
        if ($auto_sync) {
            $interval = get_option('sapo_sync_interval', 900);
            
            add_filter('cron_schedules', function($schedules) use ($interval) {
                $schedules['sapo_sync_interval'] = array(
                    'interval' => $interval,
                    'display' => sprintf(__('Every %d minutes', 'sapo-sync'), $interval / 60)
                );
                return $schedules;
            });
            
            if (!wp_next_scheduled('sapo_sync_inventory')) {
                wp_schedule_event(time(), 'sapo_sync_interval', 'sapo_sync_inventory');
            }
            
            add_action('sapo_sync_inventory', array($this, 'auto_sync_inventory'));
        }
    }
    
    public function auto_sync_inventory() {
        try {
            $service = new Sapo_Service_Sync();
            $service->sync_inventory_from_sapo();
        } catch (Exception $e) {
            Sapo_Service_Log::log('error', 0, 0, 'auto_sync_inventory', 'error', $e->getMessage());
        }
    }
    
    public function check_requirements() {
        if (!ini_get('allow_url_fopen')) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo __('SAPO Sync: allow_url_fopen is disabled. This may affect image synchronization.', 'sapo-sync');
            echo '</p></div>';
        }
        
        if (!function_exists('curl_init')) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo __('SAPO Sync: cURL is not enabled. This may affect API communication.', 'sapo-sync');
            echo '</p></div>';
        }
    }
}
