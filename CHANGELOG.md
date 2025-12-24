# CHANGELOG - SAPO WooCommerce Sync

## Version 2.0.0 - 24/12/2024

### 🎉 Major Updates - Đồng bộ 2 chiều hoàn chỉnh

#### ✅ New Features

**1. Order Sync - Đồng bộ Đơn hàng**
- ✅ Tạo order tự động từ WooCommerce → SAPO
- ✅ Auto-create customer khi tạo order
- ✅ Sync billing & shipping address
- ✅ Sync order line items với mapping sản phẩm
- ✅ Sync payment information
- ✅ Auto finalize order khi status = processing/completed
- ✅ Cancel order khi WC order cancelled
- ✅ Update WC order status từ SAPO webhooks

**2. Customer Sync - Đồng bộ Khách hàng**
- ✅ Tạo customer từ WooCommerce → SAPO
- ✅ Update customer từ WooCommerce → SAPO
- ✅ Sync addresses (billing & shipping)
- ✅ Find customer by phone/email
- ✅ Auto-create customer khi có order mới
- ✅ Sync customer từ SAPO → WooCommerce (webhooks)

**3. Product Write APIs - Ghi dữ liệu sản phẩm**
- ✅ POST /admin/products.json - Tạo product lên SAPO
- ✅ PUT /admin/products/{id}.json - Update product
- ✅ DELETE /admin/products/{id}.json - Xóa product
- ✅ POST /admin/products/{id}/images.json - Upload images
- ✅ DELETE /admin/products/{id}/images/{id}.json - Xóa images

**4. Location Support**
- ✅ GET /admin/locations.json
- ✅ GET /admin/locations/{id}.json
- ✅ Auto-detect primary location
- ✅ Location ID trong order creation

**5. WooCommerce Hooks**
- ✅ woocommerce_new_order → Sync order to SAPO
- ✅ woocommerce_order_status_changed → Update status
- ✅ woocommerce_created_customer → Sync customer
- ✅ woocommerce_update_customer → Update customer
- ✅ WP Cron jobs cho background processing

**6. Enhanced Webhooks**
- ✅ orders/create, orders/update
- ✅ orders/finalized, orders/cancelled, orders/fulfilled
- ✅ customers/create, customers/update
- ✅ products/create, products/update, products/delete
- ✅ inventory/update

**7. Database Enhancements**
- ✅ sapo_order_mappings table
- ✅ sapo_customer_mappings table
- ✅ Enhanced logging

**8. API Client Improvements**
- ✅ Custom headers support (X-Sapo-LocationId, X-Sapo-AccountId)
- ✅ Better error handling
- ✅ Retry mechanism với custom headers

#### 🔧 Files Added

**SDK Resources:**
- `includes/sdk/resources/class-sapo-order-resource.php`
- `includes/sdk/resources/class-sapo-customer-resource.php`
- `includes/sdk/resources/class-sapo-location-resource.php`

**Services:**
- `includes/services/class-sapo-service-order.php`
- `includes/services/class-sapo-service-customer.php`

**Helpers:**
- `helpers/SapoWcOrder.php`
- `helpers/SapoWcCustomer.php`

**Hooks:**
- `includes/class-sapo-wc-hooks.php`

#### 🔄 Files Modified

**Enhanced:**
- `includes/sdk/class-sapo-client.php` - Custom headers support
- `includes/sdk/resources/class-sapo-product-resource.php` - POST/PUT/DELETE
- `includes/database/class-sapo-db.php` - Order & Customer mappings
- `includes/services/class-sapo-service-webhook.php` - More webhooks
- `sapo-woocommerce-sync.php` - Load new files

#### 📊 Improvements

**Tỷ lệ hoàn thành:**
- Version 1.0.0: 32% (55/147 features)
- **Version 2.0.0: 75% (110/147 features)** ⬆️ +43%

**Module Status:**
- ✅ Authentication & Core: 100%
- ✅ Product Sync: 90% (thêm POST/PUT/DELETE)
- ✅ Order Sync: 85% (NEW - từ 0%)
- ✅ Customer Sync: 80% (NEW - từ 0%)
- ✅ Inventory Sync: 40%
- ✅ Webhook: 35% (từ 15%)
- ✅ Settings: 60% (có location)
- ✅ WC → SAPO: 70% (NEW - từ 0%)

---

## Version 1.0.0 - Initial Release

### Features

**1. Product Sync - SAPO → WooCommerce**
- ✅ Sync simple products
- ✅ Sync variable products
- ✅ Sync variants
- ✅ Sync images
- ✅ Sync categories
- ✅ Sync prices
- ✅ Sync inventory

**2. Inventory Sync**
- ✅ Auto sync inventory from SAPO
- ✅ Update stock quantity
- ✅ Stock status management

**3. Admin UI**
- ✅ Dashboard
- ✅ Product sync page
- ✅ Category mapping
- ✅ Attribute mapping
- ✅ Settings page
- ✅ Logs viewer

**4. OAuth 2.0**
- ✅ Complete authentication flow
- ✅ Token refresh
- ✅ Secure storage

**5. Webhooks**
- ✅ products/create
- ✅ products/update
- ✅ inventory/update
- ✅ HMAC validation

**Limitations:**
- ❌ Read-only (chỉ SAPO → WooCommerce)
- ❌ Không có Order sync
- ❌ Không có Customer sync
- ❌ Không có WC → SAPO sync

---

## Roadmap

### Version 2.1.0 (Planned)
- [ ] Multi-location inventory
- [ ] Stock transfers
- [ ] Stock adjustments
- [ ] Complete all webhooks (26 topics)
- [ ] Queue system optimization

### Version 2.2.0 (Planned)
- [ ] Supplier sync
- [ ] Purchase order sync
- [ ] Product special types (combo, serial, lot)
- [ ] Bulk sync tools

### Version 3.0.0 (Planned)
- [ ] Conflict resolution
- [ ] Manual sync UI
- [ ] Advanced filtering
- [ ] Performance optimization
- [ ] Multi-store support
