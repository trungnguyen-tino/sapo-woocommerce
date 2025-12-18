<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_Admin {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'handle_oauth_callback'));
    }
    
    public function register_menu() {
        add_menu_page(
            __('SAPO Sync', 'sapo-sync'),
            __('SAPO Sync', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-sync',
            array($this, 'render_dashboard_page'),
            'dashicons-update',
            56
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Dashboard', 'sapo-sync'),
            __('Dashboard', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-sync',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Sản phẩm SAPO', 'sapo-sync'),
            __('Products', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-products',
            array($this, 'render_products_page')
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Đã đồng bộ', 'sapo-sync'),
            __('Synced Products', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-synced',
            array($this, 'render_synced_page')
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Category Mapping', 'sapo-sync'),
            __('Categories', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-categories',
            array($this, 'render_categories_page')
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Attribute Mapping', 'sapo-sync'),
            __('Attributes', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-attributes',
            array($this, 'render_attributes_page')
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Cài đặt', 'sapo-sync'),
            __('Settings', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Nhật ký', 'sapo-sync'),
            __('Logs', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-logs',
            array($this, 'render_logs_page')
        );
        
        add_submenu_page(
            'sapo-sync',
            __('Test & Debug', 'sapo-sync'),
            __('Test & Debug', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-debug',
            array($this, 'render_debug_page')
        );
    }
    
    public function enqueue_assets($hook) {
        $sapo_pages = array(
            'toplevel_page_sapo-sync',
            'sapo-sync_page_sapo-products',
            'sapo-sync_page_sapo-synced',
            'sapo-sync_page_sapo-categories',
            'sapo-sync_page_sapo-attributes',
            'sapo-sync_page_sapo-settings',
            'sapo-sync_page_sapo-logs',
            'sapo-sync_page_sapo-debug'
        );
        
        if (!in_array($hook, $sapo_pages)) {
            return;
        }
        
        wp_enqueue_style(
            'sapo-admin-css',
            SAPO_SYNC_URL . 'admin/assets/css/admin.css',
            array(),
            SAPO_SYNC_VERSION
        );
        
        wp_enqueue_script('react', 'https://unpkg.com/react@18/umd/react.production.min.js', array(), '18.0.0', true);
        wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js', array('react'), '18.0.0', true);
        wp_enqueue_script('babel-standalone', 'https://unpkg.com/@babel/standalone/babel.min.js', array(), '7.0.0', true);
        
        wp_localize_script('react', 'wpApiSettings', array(
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    public function handle_oauth_callback() {
        if (isset($_GET['page']) && $_GET['page'] === 'sapo-sync' && isset($_GET['action']) && $_GET['action'] === 'oauth-callback') {
            $auth_service = new Sapo_Service_Auth();
            $auth_service->handle_oauth_callback();
        }
    }
    
    public function render_dashboard_page() {
        include SAPO_SYNC_PATH . 'admin/views/dashboard.php';
    }
    
    public function render_products_page() {
        include SAPO_SYNC_PATH . 'admin/views/products.php';
    }
    
    public function render_synced_page() {
        include SAPO_SYNC_PATH . 'admin/views/synced.php';
    }
    
    public function render_categories_page() {
        include SAPO_SYNC_PATH . 'admin/views/categories.php';
    }
    
    public function render_attributes_page() {
        include SAPO_SYNC_PATH . 'admin/views/attributes.php';
    }
    
    public function render_settings_page() {
        include SAPO_SYNC_PATH . 'admin/views/settings.php';
    }
    
    public function render_logs_page() {
        include SAPO_SYNC_PATH . 'admin/views/logs.php';
    }
    
    public function render_debug_page() {
        include SAPO_SYNC_PATH . 'admin/views/debug.php';
    }
}
