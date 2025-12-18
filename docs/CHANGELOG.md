# Changelog

## Version 1.0.0 - 2025-12-19

### ✨ Features

#### Core Plugin
- ✅ WordPress plugin structure với singleton pattern
- ✅ WooCommerce dependency check
- ✅ Activation/deactivation hooks
- ✅ Database tables setup (products, config, logs, webhooks)
- ✅ Uninstall cleanup script

#### SAPO PHP SDK
- ✅ OAuth 2.0 authentication với auto token refresh
- ✅ Rate limiter (40 req/min, 80k req/day)
- ✅ Product Resource API
- ✅ Variant Resource API
- ✅ Custom exceptions (Auth, RateLimit, API)
- ✅ HMAC webhook verification

#### Product Sync
- ✅ Simple product creation từ SAPO
- ✅ Variable product với attributes & variations
- ✅ Product images sync (thumbnail + gallery)
- ✅ Variant images sync theo variant_ids
- ✅ Category auto-create và mapping
- ✅ SKU, price, stock, weight sync
- ✅ Barcode metadata support

#### Auto Inventory Sync
- ✅ WP Cron scheduled tasks
- ✅ Configurable sync interval (5-60 phút)
- ✅ Batch processing cho nhiều products
- ✅ Stock status auto update (instock/outofstock)

#### REST API
- ✅ 15+ endpoints cho authentication, products, sync, logs
- ✅ Permission checks (`manage_woocommerce`)
- ✅ JSON request/response
- ✅ Error handling với WP_Error

#### ReactJS Admin Dashboard
- ✅ Component-based architecture
- ✅ Zero build process (Babel Standalone)
- ✅ 7 components tách riêng files
- ✅ Toast notifications
- ✅ 5 main views: Dashboard, Products, Synced, Settings, Logs
- ✅ Bulk product sync
- ✅ Real-time sync status
- ✅ Log filtering và pagination

#### Helpers & Utilities
- ✅ WooCommerce product helper (create/update)
- ✅ Category helper
- ✅ Image download và WordPress media integration
- ✅ HTML sanitization
- ✅ Global utility functions

### 🔧 Improvements

#### Code Quality
- ✅ Input validation và sanitization
- ✅ Exception handling
- ✅ Duplicate image prevention
- ✅ Content field fallback (content → body_html)
- ✅ Array và null checks

#### Performance
- ✅ Image URL deduplication
- ✅ Lazy loading data theo view
- ✅ Transient caching cho rate limiter
- ✅ Optimized database queries

#### Security
- ✅ Nonce verification
- ✅ Capability checks
- ✅ Token encryption (base64)
- ✅ SQL injection prevention với $wpdb->prepare
- ✅ XSS prevention với sanitization

### 📚 Documentation
- ✅ README.md - Installation & usage guide
- ✅ TECHNICAL_SPEC.md - Technical specification
- ✅ IMAGE_SYNC.md - Image sync logic
- ✅ ADMIN_STRUCTURE.md - Admin component structure
- ✅ CHANGELOG.md - Version history

### 🐛 Bug Fixes
- ✅ Fixed variant image mapping theo image_id
- ✅ Fixed product images position sorting
- ✅ Fixed variant_ids empty array check
- ✅ Fixed product name sanitization
- ✅ Fixed create_variation signature (added sapo_product param)

### 📦 Files Structure
```
sapo-woocommerce-sync/
├── admin/
│   ├── class-sapo-admin.php
│   ├── assets/
│   │   ├── css/admin.css
│   │   └── js/
│   │       ├── app.jsx
│   │       └── components/ (7 files)
│   └── views/dashboard.php
├── includes/
│   ├── sdk/ (6 files)
│   ├── database/ (1 file)
│   ├── services/ (6 files)
│   └── api/ (3 files)
├── helpers/ (3 files)
├── docs/ (3 files)
└── README.md, CHANGELOG.md, LICENSE
```

### 🔜 Roadmap

#### Version 1.1.0
- [ ] Product search trong admin
- [ ] Pagination cho products list
- [ ] Export/import mappings
- [ ] Email notifications
- [ ] Bulk actions improvements

#### Version 1.2.0
- [ ] Product categories sync
- [ ] Custom fields mapping
- [ ] Advanced filtering
- [ ] Scheduled reports
- [ ] Performance dashboard

#### Version 2.0.0
- [ ] Multi-store support
- [ ] Order sync (SAPO → WC)
- [ ] Customer sync
- [ ] Advanced webhooks
- [ ] API rate limit optimization

### 🙏 Credits
- Developer: Trung Nguyen
- SAPO API: https://support.sapo.vn
- WooCommerce: https://woocommerce.com
- React: https://react.dev
