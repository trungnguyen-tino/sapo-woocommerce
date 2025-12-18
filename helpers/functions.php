<?php

if (!defined('ABSPATH')) {
    exit;
}

function sapo_get_client() {
    return new Sapo_Client();
}

function sapo_is_connected() {
    $access_token = get_option('sapo_sync_access_token', '');
    return !empty($access_token);
}

function sapo_log($log_type, $sapo_product_id, $wc_product_id, $action, $status = 'success', $message = '') {
    Sapo_Service_Log::log($log_type, $sapo_product_id, $wc_product_id, $action, $status, $message);
}

function sapo_format_price($price) {
    return number_format($price, 0, ',', '.');
}

function sapo_download_image($image_url) {
    if (empty($image_url)) {
        return false;
    }
    
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
        return false;
    }
    
    $file_array = [
        'name' => basename($image_url),
        'tmp_name' => $tmp
    ];
    
    $id = media_handle_sideload($file_array, 0);
    
    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        return false;
    }
    
    return $id;
}

function sapo_sanitize_html($html) {
    return wp_kses_post($html);
}

function sapo_get_sync_stats() {
    return [
        'total_synced' => Sapo_DB::get_synced_products_count(),
        'last_sync' => get_option('sapo_last_sync_time', 0),
        'is_auto_sync' => get_option('sapo_sync_auto_inventory', false)
    ];
}

function sapo_format_date($timestamp) {
    if (empty($timestamp)) {
        return 'Never';
    }
    
    return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
}
