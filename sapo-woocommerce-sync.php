<?php
/**
 * Plugin Name: SAPO WooCommerce Sync
 * Plugin URI: https://sapo.vn
 * Description: Plugin đồng bộ sản phẩm và tồn kho tự động từ SAPO.VN về WooCommerce
 * Version: 1.0.0
 * Author: Trung Nguyen
 * Author URI: https://sapo.vn
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: sapo-sync
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SAPO_SYNC_VERSION', '1.0.0');
define('SAPO_SYNC_PATH', plugin_dir_path(__FILE__));
define('SAPO_SYNC_URL', plugin_dir_url(__FILE__));

class Sapo_WooCommerce_Sync {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    private function includes() {
        require_once SAPO_SYNC_PATH . 'includes/class-sapo-sync-activator.php';
        require_once SAPO_SYNC_PATH . 'includes/class-sapo-sync-deactivator.php';
        require_once SAPO_SYNC_PATH . 'includes/class-sapo-sync.php';
        
        require_once SAPO_SYNC_PATH . 'includes/sdk/class-sapo-exception.php';
        require_once SAPO_SYNC_PATH . 'includes/sdk/class-sapo-rate-limiter.php';
        require_once SAPO_SYNC_PATH . 'includes/sdk/class-sapo-oauth.php';
        require_once SAPO_SYNC_PATH . 'includes/sdk/class-sapo-client.php';
        require_once SAPO_SYNC_PATH . 'includes/sdk/resources/class-sapo-product-resource.php';
        require_once SAPO_SYNC_PATH . 'includes/sdk/resources/class-sapo-variant-resource.php';
        
        require_once SAPO_SYNC_PATH . 'includes/database/class-sapo-db.php';
        
        require_once SAPO_SYNC_PATH . 'includes/services/class-sapo-service-auth.php';
        require_once SAPO_SYNC_PATH . 'includes/services/class-sapo-service-config.php';
        require_once SAPO_SYNC_PATH . 'includes/services/class-sapo-service-product.php';
        require_once SAPO_SYNC_PATH . 'includes/services/class-sapo-service-sync.php';
        require_once SAPO_SYNC_PATH . 'includes/services/class-sapo-service-webhook.php';
        require_once SAPO_SYNC_PATH . 'includes/services/class-sapo-service-log.php';
        
        require_once SAPO_SYNC_PATH . 'includes/api/class-sapo-api-config.php';
        require_once SAPO_SYNC_PATH . 'includes/api/class-sapo-api-product.php';
        require_once SAPO_SYNC_PATH . 'includes/api/class-sapo-api-sync.php';
        
        require_once SAPO_SYNC_PATH . 'admin/class-sapo-admin.php';
        
        require_once SAPO_SYNC_PATH . 'helpers/SapoWcProduct.php';
        require_once SAPO_SYNC_PATH . 'helpers/SapoWcCategory.php';
        require_once SAPO_SYNC_PATH . 'helpers/functions.php';
    }
    
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'check_woocommerce'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_notice'));
            return;
        }
        
        $this->init();
    }
    
    private function init() {
        $plugin = new Sapo_Sync();
        
        Sapo_Admin::instance();
        
        Sapo_API_Config::instance();
        Sapo_API_Product::instance();
        Sapo_API_Sync::instance();
    }
    
    public function activate() {
        Sapo_Sync_Activator::activate();
    }
    
    public function deactivate() {
        Sapo_Sync_Deactivator::deactivate();
    }
    
    public function woocommerce_notice() {
        echo '<div class="error"><p>' . __('SAPO WooCommerce Sync requires WooCommerce to be installed and active.', 'sapo-sync') . '</p></div>';
    }
}

function sapo_sync() {
    return Sapo_WooCommerce_Sync::instance();
}

sapo_sync();
