<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Sync_Activator {
    
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        Sapo_DB::create_tables();
    }
    
    private static function set_default_options() {
        add_option('sapo_sync_auto_inventory', false);
        add_option('sapo_sync_interval', 900);
        add_option('sapo_sync_update_price', true);
        add_option('sapo_sync_update_stock', true);
        add_option('sapo_sync_update_images', false);
        add_option('sapo_sync_webhook_enabled', false);
        add_option('sapo_sync_debug_mode', false);
    }
}
