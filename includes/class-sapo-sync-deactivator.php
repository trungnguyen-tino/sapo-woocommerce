<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Sync_Deactivator {
    
    public static function deactivate() {
        $timestamp = wp_next_scheduled('sapo_sync_inventory');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'sapo_sync_inventory');
        }
        
        flush_rewrite_rules();
    }
}
