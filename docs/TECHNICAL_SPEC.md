# Mô Tả Kỹ Thuật - Plugin SAPO WooCommerce Sync

> **Version:** 1.0.0  
> **Author:** Trung Nguyen  
> **Last Updated:** 2025-12-19

---

## 📋 Thông Tin Plugin

**Plugin Name:** SAPO WooCommerce Sync  
**Version:** 1.0.0  
**Requires WordPress:** 5.8+  
**Requires PHP:** 7.4+  
**Requires WooCommerce:** 6.0+  
**Description:** Plugin đồng bộ sản phẩm và tồn kho tự động từ SAPO.VN về WooCommerce

---

## 🏗️ Kiến Trúc Tổng Quan

### **Technology Stack**

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend Core** | PHP 7.4+ | Business logic, API integration |
| **SAPO SDK** | PHP Custom SDK | OAuth 2.0, API calls, rate limiting |
| **Database** | MySQL via $wpdb | Data persistence, mapping tables |
| **Admin UI** | ReactJS (Inline, No Build) | SPA Dashboard |
| **API Layer** | WordPress REST API | Frontend ↔ Backend communication |
| **Frontend Libs** | React 18, Babel Standalone | Zero build process |
| **Styling** | Inline CSS / Tailwind CDN | Simple styling |
| **Scheduling** | WP Cron | Auto sync inventory |

### **Cấu Trúc Thư Mục**

```
sapo-woocommerce-sync/
├── sapo-woocommerce-sync.php       # Main plugin file
├── uninstall.php                    # Cleanup on delete
├── README.md
├── LICENSE.txt
│
├── includes/                        # PHP Backend Logic
│   ├── class-sapo-sync.php          # Main controller
│   ├── class-sapo-sync-activator.php
│   ├── class-sapo-sync-deactivator.php
│   │
│   ├── sdk/                         # SAPO PHP SDK
│   │   ├── class-sapo-client.php    # Main SDK client
│   │   ├── class-sapo-oauth.php     # OAuth 2.0 handler
│   │   ├── class-sapo-rate-limiter.php
│   │   ├── class-sapo-exception.php
│   │   └── resources/               # API Resource classes
│   │       ├── class-sapo-product-resource.php
│   │       └── class-sapo-variant-resource.php
│   │
│   ├── services/                    # Business Logic Services
│   │   ├── class-sapo-service-auth.php
│   │   ├── class-sapo-service-config.php
│   │   ├── class-sapo-service-product.php
│   │   ├── class-sapo-service-sync.php
│   │   ├── class-sapo-service-webhook.php
│   │   └── class-sapo-service-log.php
│   │
│   ├── api/                         # REST API Controllers
│   │   ├── class-sapo-api-config.php
│   │   ├── class-sapo-api-product.php
│   │   └── class-sapo-api-sync.php
│   │
│   └── database/                    # Database Layer
│       └── class-sapo-db.php
│
├── admin/                           # Admin Panel
│   ├── class-sapo-admin.php         # Admin controller
│   ├── assets/
│   │   ├── css/
│   │   │   └── admin.css
│   │   └── js/
│   │       └── admin-utils.js       # Helper utilities
│   │
│   └── views/                       # React Admin Views
│       ├── dashboard.php            # Main React dashboard
│       ├── settings.php             # Settings page
│       └── logs.php                 # Sync logs page
│
├── helpers/                         # Utility Helpers
│   ├── SapoWcProduct.php           # WC Product helper
│   ├── SapoWcCategory.php          # WC Category helper
│   └── functions.php                # Global functions
│
└── languages/                       # i18n
    └── sapo-sync-vi.po
```

---

## 🔐 SAPO PHP SDK - OAuth 2.0 Implementation

### **1. Class: `Sapo_Client` (Main SDK)**

```php
class Sapo_Client {
    private $store;           // tenant.mysapo.net
    private $client_id;
    private $client_secret;
    private $access_token;
    private $refresh_token;
    private $rate_limiter;
    private $oauth_handler;
    
    public function __construct($config = []) {
        $this->store = $config['store'] ?? '';
        $this->client_id = $config['client_id'] ?? '';
        $this->client_secret = $config['client_secret'] ?? '';
        $this->access_token = $config['access_token'] ?? null;
        $this->refresh_token = $config['refresh_token'] ?? null;
        
        $this->rate_limiter = new Sapo_Rate_Limiter();
        $this->oauth_handler = new Sapo_OAuth($this);
    }
    
    public function get_authorization_url($scopes = [], $redirect_uri = '', $state = '') {}
    
    public function complete_oauth($code, $redirect_uri) {}
    
    public function refresh_access_token() {}
    
    public function is_token_expired() {}
    
    public function request($method, $path, $params = [], $data = null) {}
    
    public function products() { return new Sapo_Product_Resource($this); }
    
    public function variants() { return new Sapo_Variant_Resource($this); }
}
```

**Features:**
- ✅ OAuth 2.0 Authorization Code Flow
- ✅ Token refresh tự động
- ✅ Rate limiting: 40 requests/phút, 80,000 requests/ngày
- ✅ Error handling với custom exceptions
- ✅ Request/Response logging

### **2. Class: `Sapo_OAuth`**

```php
class Sapo_OAuth {
    private $client;
    
    const AUTH_URL = 'https://{store}/admin/oauth/authorize';
    const TOKEN_URL = 'https://{store}/admin/oauth/access_token';
    
    public function get_authorization_url($scopes, $redirect_uri, $state) {
        // Return: https://tenant.mysapo.net/admin/oauth/authorize?...
    }
    
    public function exchange_code_for_token($code, $redirect_uri) {
        // POST to TOKEN_URL
        // Return: ['access_token', 'refresh_token', 'expires_in', 'scope']
    }
    
    public function refresh_token($refresh_token) {
        // POST refresh_token grant
    }
}
```

### **3. Class: `Sapo_Rate_Limiter`**

```php
class Sapo_Rate_Limiter {
    private $minute_limit = 40;    // 40 req/min
    private $daily_limit = 80000;   // 80k req/day
    private $request_log = [];
    
    public function check_and_wait() {
        // Check if can make request
        // Auto sleep if exceed limit
    }
    
    public function log_request() {
        // Track request timestamps
    }
    
    public function get_remaining_quota() {}
}
```

### **4. Resource Classes**

```php
class Sapo_Product_Resource {
    private $client;
    
    public function all($params = []) {
        // GET /admin/products.json
    }
    
    public function get($product_id) {
        // GET /admin/products/{id}.json
    }
    
    public function count($params = []) {
        // GET /admin/products/count.json
    }
    
    public function get_by_ids($ids = []) {
        // GET /admin/products.json?ids=1,2,3
    }
}

class Sapo_Variant_Resource {
    public function all($product_id) {
        // GET /admin/products/{id}/variants.json
    }
    
    public function get($variant_id) {
        // GET /admin/variants/{id}.json
    }
    
    public function count($product_id) {
        // GET /admin/products/{id}/variants/count.json
    }
}
```

---

## 💾 Database Schema

```sql
-- Mapping products SAPO <-> WooCommerce
CREATE TABLE {prefix}_sapo_sync_products (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sapo_product_id BIGINT NOT NULL,
    sapo_variant_id BIGINT NULL,
    wc_product_id BIGINT NOT NULL,
    product_type VARCHAR(20) DEFAULT 'simple',
    store VARCHAR(255) NOT NULL,
    last_synced DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sapo_product (sapo_product_id),
    INDEX idx_wc_product (wc_product_id),
    UNIQUE KEY unique_mapping (sapo_product_id, sapo_variant_id, store)
);

-- Sync configuration
CREATE TABLE {prefix}_sapo_sync_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(255) UNIQUE NOT NULL,
    config_value LONGTEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sync logs
CREATE TABLE {prefix}_sapo_sync_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    log_type VARCHAR(50) NOT NULL,
    sapo_product_id BIGINT NULL,
    wc_product_id BIGINT NULL,
    action VARCHAR(100) NOT NULL,
    status VARCHAR(20) DEFAULT 'success',
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_type (log_type),
    INDEX idx_created_at (created_at)
);

-- Webhook deliveries (for SAPO webhooks)
CREATE TABLE {prefix}_sapo_webhooks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    webhook_id VARCHAR(255),
    topic VARCHAR(100) NOT NULL,
    payload LONGTEXT,
    processed TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_processed (processed)
);
```

---

## 🔄 Core Features

### **Feature 1: OAuth 2.0 Authentication**

**Flow:**
1. User clicks "Kết nối SAPO" trong admin
2. Redirect đến SAPO OAuth URL
3. User authorize trên SAPO
4. Callback về WordPress với `code`
5. Exchange code → access_token + refresh_token
6. Lưu tokens vào wp_options (encrypted)

**API Endpoints:**
- `POST /wp-json/sapo/v1/auth/connect` - Tạo authorization URL
- `GET /wp-json/sapo/v1/auth/callback` - Handle OAuth callback
- `POST /wp-json/sapo/v1/auth/disconnect` - Revoke tokens

### **Feature 2: Product Sync**

**Sync Modes:**
- **Manual Sync:** User chọn products từ SAPO → Import WooCommerce
- **Bulk Sync:** Import tất cả products
- **Selected Sync:** Chọn IDs cụ thể

**Product Mapping:**
```
SAPO Product (has variants) → WooCommerce Variable Product
  ├── SAPO Variant 1 → WC Variation 1
  ├── SAPO Variant 2 → WC Variation 2
  └── SAPO Variant 3 → WC Variation 3

SAPO Product (no variants) → WooCommerce Simple Product
```

**Data Mapping:**

| SAPO Field | WooCommerce Field |
|-----------|------------------|
| `name` | `post_title` |
| `body_html` | `post_content` |
| `images[].src` | Featured Image + Gallery |
| `variant.price` | `_regular_price` |
| `variant.sku` | `_sku` |
| `variant.inventory_quantity` | `_stock` |
| `variant.barcode` | Custom meta `_barcode` |
| `variant.grams` | `_weight` |
| `product_type` | Category (auto create) |

**API Endpoints:**
- `GET /wp-json/sapo/v1/products` - Lấy danh sách products từ SAPO
- `POST /wp-json/sapo/v1/products/sync` - Sync products
- `POST /wp-json/sapo/v1/products/sync-selected` - Sync selected IDs
- `GET /wp-json/sapo/v1/products/synced` - Lấy products đã sync
- `DELETE /wp-json/sapo/v1/products/mapping/{id}` - Xóa mapping

### **Feature 3: Auto Inventory Sync**

**Cron Job:**
```php
// Every 15 minutes (configurable)
wp_schedule_event(time(), 'every_15_minutes', 'sapo_sync_inventory');

add_action('sapo_sync_inventory', function() {
    $service = new Sapo_Service_Sync();
    $service->sync_inventory_from_sapo();
});
```

**Sync Logic:**
1. Lấy danh sách WC products đã mapping
2. For each product:
   - Get variant từ SAPO API
   - Compare `inventory_quantity`
   - Update WC `_stock` nếu khác
   - Log thay đổi

**Settings:**
- ✅ Enable/Disable auto sync
- ✅ Sync interval (5, 15, 30, 60 minutes)
- ✅ Sync chỉ products đã map
- ✅ Email notification khi có lỗi

**API Endpoints:**
- `POST /wp-json/sapo/v1/sync/inventory` - Manual inventory sync
- `GET /wp-json/sapo/v1/sync/status` - Get sync status

### **Feature 4: Product Management**

**Actions:**
- ✅ View SAPO products (pagination, search)
- ✅ Preview product trước khi sync
- ✅ Update price từ SAPO
- ✅ Update stock từ SAPO
- ✅ Delete mapping (không xóa WC product)
- ✅ Re-sync specific product

**API Endpoints:**
- `GET /wp-json/sapo/v1/products/search?q=iphone` - Search SAPO products
- `POST /wp-json/sapo/v1/products/{id}/update-price` - Update price
- `POST /wp-json/sapo/v1/products/{id}/update-stock` - Update stock
- `POST /wp-json/sapo/v1/products/{id}/re-sync` - Re-sync product

### **Feature 5: Webhook Handler** (Optional)

Nhận real-time updates từ SAPO khi:
- Product created
- Product updated
- Product deleted
- Inventory updated

**Endpoint:** `POST /wp-json/sapo/v1/webhook`

**Security:**
- Verify HMAC signature từ SAPO
- Check webhook topic
- Queue processing (WP background jobs)

---

## 🎨 ReactJS Admin Dashboard

### **Dashboard Structure**

```jsx
const { useState, useEffect } = React;

function SapoAdmin() {
    const [view, setView] = useState('dashboard');
    const [connected, setConnected] = useState(false);
    const [loading, setLoading] = useState(false);
    const [products, setProducts] = useState([]);
    const [syncedProducts, setSyncedProducts] = useState([]);
    const [toast, setToast] = useState(null);
    
    const api = async (endpoint, options = {}) => {
        const res = await fetch(wpApiSettings.root + endpoint, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wpApiSettings.nonce,
                ...options.headers
            }
        });
        if (!res.ok) throw new Error('API Error');
        return res.json();
    };
    
    useEffect(() => {
        checkConnection();
        loadProducts();
        loadSyncedProducts();
    }, []);
    
    return (
        <div className="sapo-admin-app">
            {!connected && <ConnectView onConnect={checkConnection} />}
            {connected && view === 'dashboard' && <DashboardView />}
            {connected && view === 'products' && <ProductsView />}
            {connected && view === 'settings' && <SettingsView />}
            {connected && view === 'logs' && <LogsView />}
            {toast && <Toast message={toast.message} type={toast.type} />}
        </div>
    );
}
```

### **Views Breakdown**

**1. ConnectView** - OAuth setup
- Input: Store name (tenant.mysapo.net)
- Input: Client ID, Client Secret
- Button: "Kết nối SAPO" → Redirect OAuth

**2. DashboardView** - Overview
- ✅ Tổng products đã sync
- ✅ Sync status (đang chạy/idle)
- ✅ Last sync time
- ✅ Quick actions: Manual sync, Settings

**3. ProductsView** - Product management
- **Tab 1: SAPO Products** (chưa sync)
  - Table: Image | Name | SKU | Price | Stock | Actions
  - Actions: Preview, Sync, Bulk sync
  - Pagination
- **Tab 2: Synced Products**
  - Table: SAPO ID | WC ID | Name | Stock (SAPO) | Stock (WC) | Actions
  - Actions: Update stock, Update price, Re-sync, Delete mapping

**4. SettingsView**
- **OAuth Settings:** Store, Client ID, Secret, Disconnect
- **Sync Settings:**
  - Enable auto sync: Yes/No
  - Sync interval: 15 mins (dropdown)
  - Sync behavior: Update price/stock/images
- **Advanced:**
  - Enable webhooks
  - Debug mode
  - Clear logs

**5. LogsView**
- Filter: Type (all/error/success), Date range
- Table: Time | Type | Action | Product | Message
- Actions: Clear logs

---

## 🔧 Configuration & Settings

### **wp_options Storage**

```php
// OAuth credentials (encrypted)
sapo_sync_store: "tenant.mysapo.net"
sapo_sync_client_id: "xxxx"
sapo_sync_client_secret: "encrypted:xxxx"
sapo_sync_access_token: "encrypted:xxxx"
sapo_sync_refresh_token: "encrypted:xxxx"
sapo_sync_token_expires: 1234567890

// Sync settings
sapo_sync_auto_inventory: true
sapo_sync_interval: 900  // seconds
sapo_sync_update_price: true
sapo_sync_update_stock: true
sapo_sync_update_images: false
sapo_sync_webhook_enabled: false
sapo_sync_debug_mode: false
```

---

## 📝 Implementation Plan

### **Phase 1: Core SDK (Week 1)**
- [ ] Tạo plugin structure
- [ ] Implement `Sapo_Client` với OAuth 2.0
- [ ] Implement Rate Limiter
- [ ] Implement Product/Variant resources
- [ ] Unit tests cho SDK

### **Phase 2: Database & Services (Week 2)**
- [ ] Create database tables
- [ ] Implement `Sapo_Service_Auth`
- [ ] Implement `Sapo_Service_Product`
- [ ] Implement `Sapo_Service_Sync`
- [ ] Helper classes (SapoWcProduct)

### **Phase 3: REST API (Week 3)**
- [ ] Auth endpoints
- [ ] Product endpoints
- [ ] Sync endpoints
- [ ] Webhook endpoint

### **Phase 4: ReactJS Admin (Week 4)**
- [ ] ConnectView
- [ ] DashboardView
- [ ] ProductsView (SAPO products)
- [ ] ProductsView (Synced products)
- [ ] SettingsView
- [ ] LogsView

### **Phase 5: Auto Sync & Polish (Week 5)**
- [ ] WP Cron setup
- [ ] Inventory sync logic
- [ ] Error handling & logging
- [ ] Testing & debugging
- [ ] Documentation

---

## 🚀 Deployment Checklist

- [ ] Test OAuth flow với SAPO sandbox
- [ ] Test product sync (simple + variable)
- [ ] Test auto inventory sync
- [ ] Test rate limiting
- [ ] Security audit (sanitization, nonce, encryption)
- [ ] Performance test (100+ products)
- [ ] Error handling coverage
- [ ] WordPress 5.8+ compatibility
- [ ] WooCommerce 6.0+ compatibility
- [ ] PHP 7.4+ & 8.0+ compatibility
- [ ] i18n/l10n ready
- [ ] Documentation: README, User guide

---

## 📚 API Scopes Required

```
read_products       # Đọc products
write_products      # Tạo/update products (if needed)
read_inventory      # Đọc inventory
```

---

## 🔒 Security Best Practices

1. **OAuth Tokens:** Encrypt trước khi lưu wp_options
2. **Nonce Verification:** All AJAX/REST requests
3. **Permission Check:** `current_user_can('manage_woocommerce')`
4. **Sanitization:** `sanitize_text_field()`, `absint()`
5. **Escaping Output:** `esc_html()`, `esc_url()`, `esc_attr()`
6. **SQL Injection:** Use `$wpdb->prepare()`
7. **CSRF Protection:** WordPress nonce
8. **Webhook HMAC:** Verify SAPO signature

---

## 📊 Performance Optimization

1. **Batch Processing:** Sync 10 products per request
2. **Background Jobs:** Use Action Scheduler (WooCommerce)
3. **Caching:** Transient API cho rate limiter
4. **Image Optimization:** Download ảnh async
5. **Database Indexes:** Optimize queries
6. **Lazy Loading:** React components

---

## 🔗 References

- [SAPO Client SDK (TypeScript)](https://github.com/epoxyteam/sapo-client-sdk)
- [SAPO API - Products](https://support.sapo.vn/phuong-thuc-get-cua-product#index)
- [SAPO API - Variants](https://support.sapo.vn/product-variant)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [WooCommerce REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/)

---

**Document Version:** 1.0.0  
**Last Updated:** 2025-12-19  
**Maintainer:** Trung Nguyen
