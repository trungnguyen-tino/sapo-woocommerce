<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Service_Log {
    
    public static function log($log_type, $sapo_product_id, $wc_product_id, $action, $status = 'success', $message = '') {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_logs';
        
        $data = [
            'log_type' => sanitize_text_field($log_type),
            'sapo_product_id' => absint($sapo_product_id),
            'wc_product_id' => absint($wc_product_id),
            'action' => sanitize_text_field($action),
            'status' => sanitize_text_field($status),
            'message' => sanitize_textarea_field($message),
            'created_at' => current_time('mysql')
        ];
        
        return $wpdb->insert($table, $data);
    }
    
    public static function get_logs($filters = [], $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_logs';
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['log_type'])) {
            $where[] = 'log_type = %s';
            $params[] = $filters['log_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $params[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
    
    public static function get_logs_count($filters = []) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_logs';
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['log_type'])) {
            $where[] = 'log_type = %s';
            $params[] = $filters['log_type'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = 'status = %s';
            $params[] = $filters['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_var($sql);
    }
    
    public static function clear_logs($days = 30) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_logs';
        
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query(
            $wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $date)
        );
    }
    
    public static function clear_all_logs() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_logs';
        
        return $wpdb->query("TRUNCATE TABLE {$table}");
    }
}
