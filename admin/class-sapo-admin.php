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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_init', array($this, 'handle_oauth_callback'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('SAPO Sync', 'sapo-sync'),
            __('SAPO Sync', 'sapo-sync'),
            'manage_woocommerce',
            'sapo-sync',
            array($this, 'render_admin_page'),
            'dashicons-update',
            56
        );
    }
    
    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_sapo-sync') {
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
    
    public function render_admin_page() {
        include SAPO_SYNC_PATH . 'admin/views/dashboard.php';
    }
}
