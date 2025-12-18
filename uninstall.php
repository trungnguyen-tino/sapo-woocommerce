<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sapo_sync_products");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sapo_sync_config");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sapo_sync_logs");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sapo_webhooks");

delete_option('sapo_sync_store');
delete_option('sapo_sync_client_id');
delete_option('sapo_sync_client_secret');
delete_option('sapo_sync_access_token');
delete_option('sapo_sync_refresh_token');
delete_option('sapo_sync_token_expires');
delete_option('sapo_sync_auto_inventory');
delete_option('sapo_sync_interval');
delete_option('sapo_sync_update_price');
delete_option('sapo_sync_update_stock');
delete_option('sapo_sync_update_images');
delete_option('sapo_sync_webhook_enabled');
delete_option('sapo_sync_debug_mode');
