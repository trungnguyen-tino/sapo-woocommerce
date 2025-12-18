<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_DB {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $sql_products = "CREATE TABLE {$wpdb->prefix}sapo_sync_products (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            sapo_product_id BIGINT(20) NOT NULL,
            sapo_variant_id BIGINT(20) NULL,
            wc_product_id BIGINT(20) NOT NULL,
            product_type VARCHAR(20) DEFAULT 'simple',
            store VARCHAR(255) NOT NULL,
            last_synced DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_sapo_product (sapo_product_id),
            KEY idx_wc_product (wc_product_id),
            UNIQUE KEY unique_mapping (sapo_product_id, sapo_variant_id, store)
        ) $charset_collate;";
        
        dbDelta($sql_products);
        
        $sql_config = "CREATE TABLE {$wpdb->prefix}sapo_sync_config (
            id INT(11) NOT NULL AUTO_INCREMENT,
            config_key VARCHAR(255) NOT NULL,
            config_value LONGTEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_key (config_key)
        ) $charset_collate;";
        
        dbDelta($sql_config);
        
        $sql_logs = "CREATE TABLE {$wpdb->prefix}sapo_sync_logs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            log_type VARCHAR(50) NOT NULL,
            sapo_product_id BIGINT(20) NULL,
            wc_product_id BIGINT(20) NULL,
            action VARCHAR(100) NOT NULL,
            status VARCHAR(20) DEFAULT 'success',
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_log_type (log_type),
            KEY idx_created_at (created_at)
        ) $charset_collate;";
        
        dbDelta($sql_logs);
        
        $sql_webhooks = "CREATE TABLE {$wpdb->prefix}sapo_webhooks (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            webhook_id VARCHAR(255),
            topic VARCHAR(100) NOT NULL,
            payload LONGTEXT,
            processed TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_processed (processed)
        ) $charset_collate;";
        
        dbDelta($sql_webhooks);
    }
    
    public static function get_product_mapping($sapo_product_id, $sapo_variant_id = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_products';
        
        if ($sapo_variant_id) {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE sapo_product_id = %d AND sapo_variant_id = %d",
                $sapo_product_id,
                $sapo_variant_id
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} WHERE sapo_product_id = %d AND sapo_variant_id IS NULL",
                $sapo_product_id
            );
        }
        
        return $wpdb->get_row($sql);
    }
    
    public static function create_product_mapping($data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_products';
        
        $insert_data = [
            'sapo_product_id' => $data['sapo_product_id'],
            'sapo_variant_id' => $data['sapo_variant_id'] ?? null,
            'wc_product_id' => $data['wc_product_id'],
            'product_type' => $data['product_type'] ?? 'simple',
            'store' => $data['store'] ?? get_option('sapo_sync_store', ''),
            'last_synced' => current_time('mysql')
        ];
        
        $wpdb->insert($table, $insert_data);
        
        return $wpdb->insert_id;
    }
    
    public static function update_product_mapping($id, $data) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_products';
        
        $update_data = [];
        
        if (isset($data['wc_product_id'])) {
            $update_data['wc_product_id'] = $data['wc_product_id'];
        }
        
        if (isset($data['last_synced'])) {
            $update_data['last_synced'] = $data['last_synced'];
        } else {
            $update_data['last_synced'] = current_time('mysql');
        }
        
        return $wpdb->update($table, $update_data, ['id' => $id]);
    }
    
    public static function delete_product_mapping($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_products';
        
        return $wpdb->delete($table, ['id' => $id]);
    }
    
    public static function get_all_synced_products($limit = 50, $offset = 0) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_products';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY last_synced DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        );
        
        return $wpdb->get_results($sql);
    }
    
    public static function get_synced_products_count() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_products';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
    
    public static function get_wc_product_mapping($wc_product_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'sapo_sync_products';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE wc_product_id = %d",
            $wc_product_id
        );
        
        return $wpdb->get_row($sql);
    }
}
