# SAPO WooCommerce Sync - Version 2.0.0

## 🎉 Cập nhật lớn - Đồng bộ 2 chiều hoàn chỉnh

Plugin đã được **triển khai lại hoàn toàn** với 75% tính năng hoàn thành (từ 32%).

---

## ✨ Tính năng mới

### 1. **Đồng bộ Đơn hàng** (WooCommerce → SAPO)

Tự động tạo đơn hàng lên SAPO khi có đơn mới trên WooCommerce:

```php
✅ Tự động tạo order
✅ Tự động tạo customer (nếu chưa có)
✅ Sync billing & shipping address
✅ Sync order items với mapping
✅ Sync payment info
✅ Auto finalize khi paid
✅ Auto cancel khi cancelled
```

**Settings:**
- `sapo_sync_wc_to_sapo_enabled` - Bật/tắt sync WC → SAPO
- `sapo_sync_orders_auto` - Auto sync orders
- `sapo_sync_order_status_changes` - Sync status changes

### 2. **Đồng bộ Khách hàng** (Bidirectional)

```php
✅ WC → SAPO: Tạo/update customer
✅ SAPO → WC: Sync từ webhooks
✅ Find by phone/email
✅ Sync addresses
```

**Settings:**
- `sapo_sync_customers_auto` - Auto sync customers
- `sapo_sync_customer_updates` - Sync updates

### 3. **Product Write APIs** (WC → SAPO)

Bây giờ có thể ghi dữ liệu lên SAPO:

```php
✅ POST /admin/products.json
✅ PUT /admin/products/{id}.json
✅ DELETE /admin/products/{id}.json
✅ Upload/delete images
```

### 4. **Location Support**

```php
✅ GET locations
✅ Auto-detect primary location
✅ Location ID trong orders
```

### 5. **Enhanced Webhooks**

Tăng từ 4 → 11 webhook topics:

```php
✅ orders/create, orders/update, orders/finalized
✅ orders/cancelled, orders/fulfilled
✅ customers/create, customers/update
✅ products/*, inventory/update
```

---

## 📁 Cấu trúc mới

### Files mới

```
includes/
├── sdk/resources/
│   ├── class-sapo-order-resource.php       ✨ NEW
│   ├── class-sapo-customer-resource.php    ✨ NEW
│   └── class-sapo-location-resource.php    ✨ NEW
├── services/
│   ├── class-sapo-service-order.php        ✨ NEW
│   └── class-sapo-service-customer.php     ✨ NEW
├── class-sapo-wc-hooks.php                 ✨ NEW
helpers/
├── SapoWcOrder.php                         ✨ NEW
└── SapoWcCustomer.php                      ✨ NEW
```

### Files cập nhật

```
✓ class-sapo-client.php - Custom headers
✓ class-sapo-product-resource.php - POST/PUT/DELETE
✓ class-sapo-db.php - Order & Customer mappings
✓ class-sapo-service-webhook.php - More handlers
✓ sapo-woocommerce-sync.php - Load files
```

---

## 🔧 Cài đặt & Cấu hình

### Bước 1: Activate lại plugin

```bash
# Deactivate
wp plugin deactivate sapo-woocommerce-sync

# Activate để tạo tables mới
wp plugin activate sapo-woocommerce-sync
```

Plugin sẽ tự động tạo 2 tables mới:
- `wp_sapo_order_mappings`
- `wp_sapo_customer_mappings`

### Bước 2: Cấu hình Settings

**WooCommerce → SAPO Settings → General:**

```php
✓ Enable WC → SAPO sync
✓ Auto sync new orders
✓ Sync order status changes
✓ Auto sync customers
```

**SAPO API Settings:**

```php
Location ID: [Auto-detect hoặc nhập thủ công]
Account ID: [Optional]
Source ID: 1387871 (default)
Price List ID: 529736 (default)
```

**Payment Method Mapping:**

```php
cod → 698232
bacs → [SAPO payment method ID]
```

### Bước 3: Test

#### Test Order Sync

```php
// Tạo đơn hàng test trên WooCommerce
// Kiểm tra logs
WooCommerce → SAPO Sync → Logs

// Kiểm tra mapping
SELECT * FROM wp_sapo_order_mappings;
```

#### Test Customer Sync

```php
// Tạo customer mới
// Check customer trên SAPO
// Check mapping
SELECT * FROM wp_sapo_customer_mappings;
```

---

## 🔍 API Usage

### Order Resource

```php
$client = new Sapo_Client();

// Create order
$order_data = [
    'customer_id' => 123,
    'order_line_items' => [...]
];
$result = $client->orders()->create($order_data, $location_id);

// Finalize order
$client->orders()->finalize($order_id);

// Cancel order
$client->orders()->cancel($order_id, 'Out of stock');
```

### Customer Resource

```php
$client = new Sapo_Client();

// Create customer
$customer_data = [
    'name' => 'Nguyễn Văn A',
    'phone_number' => '0987654321',
    'addresses' => [...]
];
$result = $client->customers()->create($customer_data);

// Find by phone
$result = $client->customers()->get_by_phone('0987654321');
```

### Location Resource

```php
$client = new Sapo_Client();

// Get all locations
$locations = $client->locations()->all();

// Get primary location
$primary = $client->locations()->get_primary();
```

---

## 🎯 Workflow

### Order Sync Flow

```
WooCommerce Order Created
    ↓
WooCommerce Hook: woocommerce_new_order
    ↓
Schedule WP Cron: sapo_sync_order_to_sapo (delay 30s)
    ↓
Sapo_Service_Order::sync_order_to_sapo()
    ↓
1. Find or Create Customer
    ↓
2. Transform WC Order → SAPO JSON
    ↓
3. POST /admin/orders.json
    ↓
4. Save mapping
    ↓
5. Auto finalize (if paid)
```

### Customer Sync Flow

```
WooCommerce Customer Created
    ↓
Hook: woocommerce_created_customer
    ↓
Schedule: sapo_sync_customer_to_sapo (delay 20s)
    ↓
Sapo_Service_Customer::sync_customer_to_sapo()
    ↓
1. Transform WC Customer → SAPO JSON
    ↓
2. POST /admin/customers.json
    ↓
3. Save mapping
```

---

## 📊 So sánh Version

| Feature | v1.0.0 | v2.0.0 |
|---------|--------|--------|
| **Tổng %** | 32% | **75%** |
| Product Sync | 75% SAPO→WC | **90%** + POST/PUT/DELETE |
| Order Sync | 0% | **85%** NEW |
| Customer Sync | 0% | **80%** NEW |
| Inventory | 40% | 40% |
| Webhooks | 15% (4 topics) | **35%** (11 topics) |
| WC → SAPO | 0% | **70%** NEW |
| Location | 0% | **60%** NEW |

---

## ⚠️ Breaking Changes

### Database

2 tables mới được tạo tự động khi activate:
- `wp_sapo_order_mappings`
- `wp_sapo_customer_mappings`

### Settings

Thêm options mới:
```php
sapo_sync_wc_to_sapo_enabled
sapo_sync_orders_auto
sapo_sync_order_status_changes
sapo_sync_customers_auto
sapo_sync_customer_updates
sapo_sync_location_id
sapo_sync_account_id
sapo_sync_source_id
sapo_sync_price_list_id
sapo_payment_method_mapping
```

### WP Cron

Các scheduled events mới:
```php
sapo_sync_order_to_sapo
sapo_finalize_order
sapo_cancel_order
sapo_sync_customer_to_sapo
```

---

## 🐛 Debug

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Check Logs

```php
// Admin → SAPO Sync → Logs
// hoặc database
SELECT * FROM wp_sapo_sync_logs 
WHERE log_type = 'order' 
ORDER BY created_at DESC;
```

### Check Mappings

```php
// Orders
SELECT o.id, o.wc_order_id, o.sapo_order_id, o.sync_status
FROM wp_sapo_order_mappings o;

// Customers  
SELECT c.id, c.wc_customer_id, c.sapo_customer_id
FROM wp_sapo_customer_mappings c;
```

### Check WP Cron

```bash
wp cron event list
wp cron event run sapo_sync_order_to_sapo
```

---

## 🚀 Performance

### Async Processing

Tất cả sync operations đều chạy background qua WP Cron:
- Orders: delay 30s
- Customers: delay 20s
- Status changes: delay 10s

### Rate Limiting

```php
Sapo_Rate_Limiter:
- Max: 40 requests/10s
- Auto retry with exponential backoff
```

---

## 📝 TODO - Còn thiếu

### Phase 3 (Tuần 5-6)

- [ ] Multi-location inventory
- [ ] Stock transfers webhooks
- [ ] Complete all 26 webhooks
- [ ] Queue system optimization

### Phase 4 (Tuần 7-8)

- [ ] Supplier sync
- [ ] Purchase orders
- [ ] Product special types
- [ ] Conflict resolution UI

---

## 🤝 Support

**Developer:** Trung Nguyen  
**Version:** 2.0.0  
**Release Date:** 24/12/2024  
**WordPress:** 5.8+  
**PHP:** 7.4+  
**WooCommerce:** 6.0+

---

## ✅ Checklist sau khi update

- [ ] Deactivate & Activate plugin
- [ ] Check tables created
- [ ] Configure settings
- [ ] Test create order
- [ ] Test create customer
- [ ] Check logs
- [ ] Check mappings
- [ ] Monitor WP Cron
- [ ] Test webhooks

---

**Chúc mừng! Plugin đã sẵn sàng cho đồng bộ 2 chiều.** 🎉
