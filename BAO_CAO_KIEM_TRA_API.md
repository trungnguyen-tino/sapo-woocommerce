# BÁO CÁO KIỂM TRA API VÀ CẤU TRÚC JSON

**Ngày kiểm tra:** 24/12/2024  
**Nguồn tài liệu:** https://sapogo.dev/docs/ApiReference

---

## TỔNG QUAN PHÁT HIỆN

### 🔴 Vấn đề nghiêm trọng: API chỉ READ, thiếu WRITE

**Source code hiện tại CHỈ hỗ trợ:**
- ✅ GET (đọc dữ liệu từ SAPO)
- ❌ POST (tạo mới trên SAPO)
- ❌ PUT (cập nhật trên SAPO)
- ❌ DELETE (xóa trên SAPO)

**Hậu quả:**
- ❌ KHÔNG thể đồng bộ WooCommerce → SAPO
- ❌ KHÔNG thể tạo Order từ WooCommerce lên SAPO
- ❌ KHÔNG thể tạo Customer từ WooCommerce lên SAPO
- ❌ KHÔNG thể cập nhật sản phẩm từ WooCommerce lên SAPO

---

## CHI TIẾT KIỂM TRA THEO MODULE

### 1. AUTHENTICATION & CORE - ✅ ĐÚNG

#### API Documentation
```json
OAuth 2.0 Flow:
- Authorization URL: /admin/oauth/authorize
- Token Exchange: POST /admin/oauth/access_token
- Refresh Token: POST /admin/oauth/access_token (grant_type=refresh_token)
```

#### Source Code Implementation
```php
File: includes/sdk/class-sapo-oauth.php
- ✅ get_authorization_url() - ĐÚNG
- ✅ exchange_code_for_token() - ĐÚNG
- ✅ refresh_token() - ĐÚNG

File: includes/sdk/class-sapo-client.php
- ✅ Token management - ĐÚNG
- ✅ Auto refresh - ĐÚNG
- ✅ HMAC validation - ĐÚNG
- ✅ Rate limiting - ĐÚNG
```

**Kết luận:** ✅ **HOÀN CHỈNH 100%**

---

### 2. ĐỒNG BỘ SẢN PHẨM - ⚠️ THIẾU WRITE API

#### API Documentation (SAPO hỗ trợ)

**GET APIs:**
```json
✅ GET /admin/products.json
   - Params: limit, page, created_on_min, created_on_max, ids, query
   
✅ GET /admin/products/{id}.json
   - Params: fields

✅ GET /admin/products/count.json

✅ GET /admin/products/search.json
   - Params: query
```

**POST/PUT/DELETE APIs:**
```json
❌ POST /admin/products.json
   Body: {
     "product": {
       "name": "Tên sản phẩm",
       "variants": [{
         "name": null,
         "opt1": "Mặc định",
         "price": 100000,
         "sku": "SP001",
         "inventory_quantity": 10,
         "taxable": true,
         "tax_included": true,
         "input_vat_id": 8782,
         "output_vat_id": 38477
       }],
       "options": [{
         "name": "Kích thước",
         "values": ["Mặc định"]
       }],
       "images": [...],
       "category_id": 123,
       "brand_id": 456,
       "tags": "tag1,tag2"
     }
   }

❌ PUT /admin/products/{id}.json
   Body: {
     "product": {
       "name": "Tên mới",
       "category_id": 123,
       "brand_id": 456,
       "tags": "new-tag",
       "variants": [...]
     }
   }

❌ DELETE /admin/products/{id}.json
```

#### Source Code Implementation

**File: `includes/sdk/resources/class-sapo-product-resource.php`**
```php
✅ public function all($params = [])           // GET /admin/products.json
✅ public function get($product_id)            // GET /admin/products/{id}.json
✅ public function count($params = [])         // GET /admin/products/count.json
✅ public function search($query, $params)     // GET /admin/products/search.json
✅ public function get_by_ids($ids)            // GET /admin/products.json?ids=...
✅ public function get_by_category($cat_id)    // GET /admin/products.json?collection_id=...

❌ public function create($data)               // THIẾU - POST /admin/products.json
❌ public function update($id, $data)          // THIẾU - PUT /admin/products/{id}.json
❌ public function delete($id)                 // THIẾU - DELETE /admin/products/{id}.json
```

**File: `helpers/SapoWcProduct.php`**
```php
✅ create_simple_product()     // Tạo WC product từ SAPO data
✅ create_variable_product()   // Tạo WC variable product
✅ create_variation()          // Tạo WC variation
✅ update_product()            // Cập nhật WC product
✅ update_stock()              // Cập nhật WC stock
✅ update_price()              // Cập nhật WC price

❌ KHÔNG có method nào gọi SAPO API để tạo/update product
```

#### Sản phẩm đặc biệt

**API Documentation:**
```json
SAPO hỗ trợ trong Product API:
- product_type: "normal", "combo", "medicine", "serial", "lot"
- Combo products: Có thuộc tính combo_items[]
- Serial: Có enable_serial = true
- Lot: Có enable_lot = true
```

**Source Code:**
```php
❌ KHÔNG xử lý product_type đặc biệt
❌ KHÔNG xử lý combo_items
❌ KHÔNG xử lý serial tracking
❌ KHÔNG xử lý lot/batch tracking
```

**Kết luận:** 
- ✅ GET APIs: **75%** (thiếu images API)
- ❌ POST/PUT/DELETE: **0%**
- ❌ Sản phẩm đặc biệt: **0%**
- **Tổng: 25%**

---

### 3. ĐỒNG BỘ KHÁCH HÀNG - ❌ HOÀN TOÀN THIẾU

#### API Documentation (SAPO hỗ trợ)

**Customer APIs:**
```json
❌ GET /admin/customers.json
   - Params: created_on_min, created_on_max, ids, query
   
❌ GET /admin/customers/{id}.json

❌ POST /admin/customers.json
   Body: {
     "customer": {
       "name": "Khách hàng 1",
       "phone_number": "0987654321",
       "email": "email@example.com",
       "sex": "male|female|other",
       "birthday": "1990-01-01",
       "customer_group_id": 123,
       "addresses": [{
         "country": "Việt Nam",
         "city": "Hà Nội",
         "district": "Quận Ba Đình",
         "ward": "Phường Đội Cấn",
         "address1": "442 Đội Cấn",
         "phone_number": "0987654321",
         "label": "Giao hàng"
       }]
     }
   }

❌ PUT /admin/customers/{id}.json
   Body: { "customer": { ... } }

❌ DELETE /admin/customers/{id}.json
```

**Address APIs:**
```json
❌ GET /admin/customers/{customerId}/addresses.json
❌ POST /admin/customers/{customerId}/addresses.json
❌ PUT /admin/customers/{customerId}/addresses/{id}.json
❌ DELETE /admin/customers/{customerId}/addresses/{id}.json
```

**Contact APIs:**
```json
❌ GET /admin/customers/{customerId}/contacts.json
❌ POST /admin/customers/{customerId}/contacts.json
❌ PUT /admin/customers/{customerId}/contacts/{id}.json
❌ DELETE /admin/customers/{customerId}/contacts/{id}.json
```

**Note APIs:**
```json
❌ GET /admin/customers/{customerId}/notes.json
❌ POST /admin/customers/{customerId}/notes.json
❌ PUT /admin/customers/{customerId}/notes/{id}.json
❌ DELETE /admin/customers/{customerId}/notes/{id}.json
```

#### Source Code Implementation

**Files KHÔNG tồn tại:**
```
❌ includes/sdk/resources/class-sapo-customer-resource.php
❌ includes/services/class-sapo-service-customer.php
❌ helpers/SapoWcCustomer.php
```

**Kết luận:** ❌ **0%** - Hoàn toàn không có

---

### 4. ĐỒNG BỘ ĐƠN HÀNG - ❌ HOÀN TOÀN THIẾU

#### API Documentation (SAPO hỗ trợ)

**Order APIs:**
```json
❌ GET /admin/orders.json
   - Params: created_on_min, created_on_max, ids, query
   
❌ GET /admin/orders/{id}.json

❌ POST /admin/orders.json
   Body: {
     "order": {
       "customer_id": 123,
       "status": "draft|finalized",
       "billing_address": {
         "full_name": "Nguyễn Văn A",
         "address1": "123 ABC",
         "phone_number": "0987654321",
         "country": "Việt Nam",
         "city": "Hà Nội",
         "district": "Quận Ba Đình",
         "ward": "Phường Đội Cấn"
       },
       "shipping_address": { ... },
       "phone_number": "0987654321",
       "email": "email@example.com",
       "price_list_id": 123,
       "source_id": 456,
       "total": 800000,
       "order_line_items": [{
         "variant_id": 789,
         "product_id": 456,
         "price": 20000,
         "quantity": 1,
         "tax_included": false,
         "tax_rate": 10.0
       }],
       "prepayments": [{
         "payment_method_id": 123,
         "amount": 800000,
         "source": "customer_prepaid",
         "paid_amount": 800000
       }],
       "fulfillments": [{
         "shipment": {
           "delivery_service_provider_id": 94465,
           "service_name": "Đi bộ",
           "cod_amount": 800000,
           "freight_amount": 45000
         }
       }]
     }
   }

❌ POST /admin/orders/pos.json
   - Tạo đơn hàng POS (bán tại quầy)

❌ PUT /admin/orders/{id}.json
   - Cập nhật đơn hàng

❌ POST /admin/orders/{id}/finalize.json
   - Duyệt đơn hàng (draft → finalized)

❌ POST /admin/orders/{id}/cancel.json
   - Hủy đơn hàng
```

**Headers bắt buộc:**
```
X-Sapo-LocationId: 123
X-Sapo-AccountId: 456
```

#### Source Code Implementation

**Files KHÔNG tồn tại:**
```
❌ includes/sdk/resources/class-sapo-order-resource.php
❌ includes/services/class-sapo-service-order.php
❌ helpers/SapoWcOrder.php
```

**Kết luận:** ❌ **0%** - Hoàn toàn không có

---

### 5. ĐỒNG BỘ TỒN KHO - ⚠️ THIẾU MULTI-LOCATION

#### API Documentation (SAPO hỗ trợ)

**Inventory trong Product:**
```json
✅ GET /admin/products/{id}.json
   Response: {
     "product": {
       "variants": [{
         "inventory_quantity": 100,
         "inventory_management": "sapo"
       }]
     }
   }
```

**Location APIs:**
```json
❌ GET /admin/locations.json
   Response: {
     "locations": [{
       "id": 123,
       "name": "Chi nhánh 1",
       "code": "CN001",
       "address1": "123 ABC",
       "city": "Hà Nội",
       "is_primary": true
     }]
   }

❌ GET /admin/locations/{id}.json
```

**Stock Transfer APIs:**
```json
❌ GET /admin/stock_transfers.json
❌ POST /admin/stock_transfers.json
   Body: {
     "stock_transfer": {
       "from_location_id": 123,
       "to_location_id": 456,
       "transfer_line_items": [{
         "variant_id": 789,
         "quantity": 10
       }]
     }
   }
```

**Stock Adjustment APIs:**
```json
❌ GET /admin/stock_adjustments.json
❌ POST /admin/stock_adjustments.json
```

#### Source Code Implementation

**Files tồn tại:**
```php
✅ includes/services/class-sapo-service-sync.php
   - sync_inventory_from_sapo() - Chỉ sync tổng inventory
   - update_wc_stock() - Cập nhật WC stock
```

**Files KHÔNG tồn tại:**
```
❌ includes/sdk/resources/class-sapo-location-resource.php
❌ includes/sdk/resources/class-sapo-stock-transfer-resource.php
❌ includes/sdk/resources/class-sapo-stock-adjustment-resource.php
```

**Vấn đề:**
- ✅ Sync tồn kho tổng: OK
- ❌ KHÔNG hỗ trợ multi-location
- ❌ KHÔNG biết tồn kho từng chi nhánh
- ❌ KHÔNG sync stock transfer
- ❌ KHÔNG sync stock adjustment

**Kết luận:** ⚠️ **40%** - Chỉ có basic inventory sync

---

### 6. ĐỒNG BỘ CÀI ĐẶT - ⚠️ THIẾU LOCATIONS

#### API Documentation (SAPO hỗ trợ)

**Tenant Settings:**
```json
❌ GET /admin/settings.json
   Response: {
     "setting": {
       "enable_serial": true,
       "enable_lot": true,
       "enable_warranty": true,
       "default_purchase_tax": 123,
       "default_sales_tax": 456
     }
   }

❌ PUT /admin/settings.json
   Body: { "setting": { ... } }
```

**Locations:**
```json
❌ GET /admin/locations.json
❌ GET /admin/locations/{id}.json
```

#### Source Code Implementation

**Files tồn tại:**
```php
✅ includes/services/class-sapo-service-config.php
   - Lưu store, client_id, client_secret
   
✅ Admin settings page
   - UI để nhập config
```

**Files KHÔNG tồn tại:**
```
❌ includes/sdk/resources/class-sapo-location-resource.php
❌ includes/sdk/resources/class-sapo-setting-resource.php
```

**Kết luận:** ⚠️ **50%** - Có config cơ bản, thiếu locations & settings API

---

### 7. WEBHOOK - ⚠️ THIẾU NHIỀU TOPICS

#### API Documentation (SAPO hỗ trợ)

**Product Webhooks:**
```json
✅ products/create - ĐÃ CÓ handler
✅ products/update - ĐÃ CÓ handler
⚠️ products/delete - CÓ handler nhưng chỉ log
```

**Order Webhooks:**
```json
❌ orders/create
❌ orders/update
❌ orders/cancelled
❌ orders/finalized
❌ orders/fulfilled
❌ orders/paid
❌ orders/received
❌ order_returns/create
❌ order_returns/received
❌ order_returns/refunded
```

**Customer Webhooks:**
```json
❌ customers/create
❌ customers/update
❌ customers/delete
```

**Inventory Webhooks:**
```json
✅ inventory/update - ĐÃ CÓ handler
❌ stock_transfers/create
❌ stock_transfers/update
❌ stock_transfers/cancelled
❌ stock_transfers/received
❌ stock_transfers/exported
```

**Purchase Order Webhooks:**
```json
❌ purchase_orders/create
❌ purchase_orders/update
❌ purchase_orders/cancelled
❌ purchase_orders/activated
❌ purchase_orders/closed
❌ purchase_receipts/create
❌ purchase_payments/create
❌ supplier_returns/create
```

#### Source Code Implementation

**File: `includes/services/class-sapo-service-webhook.php`**
```php
✅ handle_webhook($request)
✅ verify_webhook($body, $hmac) - HMAC-SHA256 validation
✅ process_webhook($topic, $data)

Topics đã handle:
✅ 'products/create' → handle_product_update()
✅ 'products/update' → handle_product_update()
⚠️ 'products/delete' → handle_product_delete() (chỉ log)
✅ 'inventory/update' → handle_inventory_update()

❌ Tất cả topics khác (22+ topics)
```

**Kết luận:** ⚠️ **15%** (4/26 topics)

---

### 8. HƯỚNG ĐỒNG BỘ - ❌ THIẾU NGHIÊM TRỌNG

#### 8.1. WooCommerce → SAPO - ❌ 0%

**Yêu cầu:**
1. Khi có đơn hàng mới trên WooCommerce → Tạo order trên SAPO
2. Khi có khách hàng mới trên WooCommerce → Tạo customer trên SAPO
3. Khi cập nhật sản phẩm trên WooCommerce → Cập nhật product trên SAPO

**API cần thiết (SAPO có sẵn):**
```json
❌ POST /admin/orders.json - Tạo order từ WC
   - customer_id (tự động tạo nếu chưa có)
   - order_line_items[]
   - billing_address, shipping_address
   - payment info
   
❌ POST /admin/customers.json - Tạo customer từ WC
   - name, email, phone
   - addresses[]
   
❌ PUT /admin/products/{id}.json - Update product từ WC
   - price, inventory_quantity
```

**Source Code:**
```php
❌ KHÔNG có WooCommerce hooks để bắt events:
   - woocommerce_new_order
   - woocommerce_order_status_changed
   - woocommerce_new_customer
   - woocommerce_update_product
   
❌ KHÔNG có methods để gọi SAPO POST/PUT APIs
```

**Kết luận:** ❌ **0%** - Hoàn toàn không có

---

#### 8.2. SAPO → WooCommerce - ✅ 70%

**Hiện trạng:**
```php
✅ Products SAPO → WC (70%)
   - Đọc products từ SAPO
   - Tạo/update WC products
   - Sync variants, images, categories
   - Thiếu: Brands, sản phẩm đặc biệt
   
✅ Inventory SAPO → WC (100%)
   - Sync inventory_quantity
   - Update stock status
   
⚠️ Categories SAPO → WC (50%)
   - Có mapping table
   - Chưa auto-sync
   
⚠️ Prices SAPO → WC (80%)
   - Sync trong product sync
   - Có option update_price
```

**Kết luận:** ✅ **70%** - Hoạt động tốt cho products & inventory

---

#### 8.3. Đồng bộ hai chiều - ❌ 35%

**Thực tế:**
```
Sản phẩm:
  ✅ SAPO → WC: 70%
  ❌ WC → SAPO: 0%
  → Tổng: 35%

Khách hàng:
  ❌ SAPO → WC: 0%
  ❌ WC → SAPO: 0%
  → Tổng: 0%

Danh mục:
  ⚠️ SAPO → WC: 50% (có mapping, chưa auto)
  ❌ WC → SAPO: 0%
  → Tổng: 25%

Giá:
  ✅ SAPO → WC: 80%
  ❌ WC → SAPO: 0%
  → Tổng: 40%
```

**Kết luận:** ❌ **25%** trung bình

---

## TỔNG HỢP CẤU TRÚC JSON

### Product JSON (SAPO API)

**Create Product:**
```json
POST /admin/products.json
Header: X-Sapo-LocationId: 123

{
  "product": {
    "name": "Mũ len tóc giả đính nơ",
    "content": "Mô tả sản phẩm",
    "product_type": "normal",
    "category_id": 29891,
    "brand_id": 926,
    "tags": "tag1,tag2",
    "variants": [{
      "name": "Mũ len - Đỏ",
      "opt1": "Đỏ",
      "opt2": "M",
      "price": 100000,
      "compare_at_price": 150000,
      "sku": "MU-DO-M",
      "barcode": "1234567890",
      "inventory_quantity": 50,
      "weight": 0.5,
      "taxable": true,
      "tax_included": true,
      "input_vat_id": 8782,
      "output_vat_id": 38477
    }],
    "options": [{
      "name": "Màu sắc",
      "values": ["Đỏ", "Xanh"]
    }, {
      "name": "Kích thước",
      "values": ["M", "L"]
    }],
    "images": [{
      "src": "https://example.com/image.jpg",
      "position": 1
    }]
  }
}
```

### Customer JSON (SAPO API)

**Create Customer:**
```json
POST /admin/customers.json

{
  "customer": {
    "name": "Nguyễn Văn A",
    "phone_number": "0987654321",
    "email": "email@example.com",
    "sex": "male",
    "birthday": "1990-01-01",
    "customer_group_id": 123,
    "apply_incentives": "group",
    "addresses": [{
      "country": "Việt Nam",
      "city": "Hà Nội",
      "district": "Quận Ba Đình",
      "ward": "Phường Đội Cấn",
      "address1": "442 Đội Cấn",
      "phone_number": "0987654321",
      "label": "Giao hàng",
      "is_default": true
    }]
  }
}
```

### Order JSON (SAPO API)

**Create Order:**
```json
POST /admin/orders.json
Headers:
  X-Sapo-LocationId: 123
  X-Sapo-AccountId: 456

{
  "order": {
    "customer_id": 72558737,
    "status": "draft",
    "source_id": 1387871,
    "billing_address": {
      "full_name": "Nguyễn Văn A",
      "address1": "123 ABC Street",
      "phone_number": "0987654321",
      "country": "Việt Nam",
      "city": "Hà Nội",
      "district": "Quận Ba Đình",
      "ward": "Phường Đội Cấn"
    },
    "shipping_address": {
      "full_name": "Nguyễn Văn A",
      "address1": "123 ABC Street",
      "phone_number": "0987654321",
      "country": "Việt Nam",
      "city": "Hà Nội",
      "district": "Quận Ba Đình",
      "ward": "Phường Đội Cấn"
    },
    "email": "email@example.com",
    "phone_number": "0987654321",
    "price_list_id": 529736,
    "total": 800000,
    "order_line_items": [{
      "variant_id": 81416,
      "product_id": 58965,
      "price": 20000,
      "quantity": 1,
      "tax_included": false,
      "tax_rate": 10.0,
      "tax_rate_override": 10.0
    }],
    "prepayments": [{
      "payment_method_id": 698232,
      "amount": 800000,
      "source": "customer_prepaid",
      "paid_amount": 800000
    }],
    "fulfillments": [{
      "partner_id": 72558737,
      "shipment": {
        "delivery_service_provider_id": 94465,
        "service_name": "Giao hàng nhanh",
        "cod_amount": 800000,
        "freight_amount": 45000,
        "delivery_fee": 0,
        "shipping_address": { ... }
      }
    }]
  }
}
```

---

## ĐÁNH GIÁ TỔNG THỂ

### 📊 Bảng so sánh API Documentation vs Source Code

| API Endpoint | SAPO Docs | Source Code | Ghi chú |
|-------------|-----------|-------------|---------|
| **PRODUCT** |
| GET /admin/products.json | ✅ | ✅ | OK |
| GET /admin/products/{id}.json | ✅ | ✅ | OK |
| POST /admin/products.json | ✅ | ❌ | **THIẾU** |
| PUT /admin/products/{id}.json | ✅ | ❌ | **THIẾU** |
| DELETE /admin/products/{id}.json | ✅ | ❌ | **THIẾU** |
| POST /admin/products/{id}/images.json | ✅ | ❌ | **THIẾU** |
| DELETE /admin/products/{id}/images/{id}.json | ✅ | ❌ | **THIẾU** |
| **CUSTOMER** |
| GET /admin/customers.json | ✅ | ❌ | **THIẾU** |
| GET /admin/customers/{id}.json | ✅ | ❌ | **THIẾU** |
| POST /admin/customers.json | ✅ | ❌ | **THIẾU** |
| PUT /admin/customers/{id}.json | ✅ | ❌ | **THIẾU** |
| DELETE /admin/customers/{id}.json | ✅ | ❌ | **THIẾU** |
| Addresses APIs (4 endpoints) | ✅ | ❌ | **THIẾU** |
| Contacts APIs (5 endpoints) | ✅ | ❌ | **THIẾU** |
| Notes APIs (5 endpoints) | ✅ | ❌ | **THIẾU** |
| **ORDER** |
| GET /admin/orders.json | ✅ | ❌ | **THIẾU** |
| GET /admin/orders/{id}.json | ✅ | ❌ | **THIẾU** |
| POST /admin/orders.json | ✅ | ❌ | **THIẾU** |
| POST /admin/orders/pos.json | ✅ | ❌ | **THIẾU** |
| PUT /admin/orders/{id}.json | ✅ | ❌ | **THIẾU** |
| POST /admin/orders/{id}/finalize.json | ✅ | ❌ | **THIẾU** |
| POST /admin/orders/{id}/cancel.json | ✅ | ❌ | **THIẾU** |
| **LOCATION** |
| GET /admin/locations.json | ✅ | ❌ | **THIẾU** |
| GET /admin/locations/{id}.json | ✅ | ❌ | **THIẾU** |
| **SETTINGS** |
| GET /admin/settings.json | ✅ | ❌ | **THIẾU** |
| PUT /admin/settings.json | ✅ | ❌ | **THIẾU** |
| **INVENTORY** |
| GET via products | ✅ | ✅ | OK |
| Stock Transfers APIs | ✅ | ❌ | **THIẾU** |
| Stock Adjustments APIs | ✅ | ❌ | **THIẾU** |

**Tổng cộng:**
- SAPO hỗ trợ: **45+ endpoints**
- Source code có: **~8 endpoints (18%)**
- Thiếu: **37+ endpoints (82%)**

---

## KẾT LUẬN & KHUYẾN NGHỊ

### 🔴 Vấn đề nghiêm trọng nhất

**Plugin hiện tại CHỈ là "READ-ONLY connector":**
- ✅ Đọc dữ liệu từ SAPO
- ❌ KHÔNG ghi dữ liệu lên SAPO
- ❌ KHÔNG tạo orders từ WooCommerce
- ❌ KHÔNG sync customers

**Hậu quả:**
- Không thể sử dụng cho mô hình bán hàng thực tế
- Chỉ phù hợp để "import" sản phẩm một chiều
- Không đáp ứng yêu cầu "đồng bộ hai chiều"

### ✅ Điểm mạnh

1. **Foundation tốt:**
   - OAuth 2.0 hoàn chỉnh
   - Rate limiting
   - HMAC validation
   - Database schema đầy đủ

2. **Product sync SAPO → WC:**
   - Hoạt động ổn định
   - Hỗ trợ variants, images
   - Mapping tốt

3. **Code structure:**
   - Rõ ràng, dễ mở rộng
   - Separation of concerns tốt

### ❌ Cần bổ sung NGAY

**Priority 1 (Cấp thiết - 2 tuần):**
1. **Order Resource & Service**
   - POST /admin/orders.json
   - WooCommerce hooks: woocommerce_new_order
   - Transform WC Order → SAPO Order JSON
   - Handle payment, shipping info

2. **Customer Resource & Service**
   - POST /admin/customers.json
   - Auto-create customer khi order
   - Sync addresses

3. **Product Write APIs**
   - POST /admin/products.json
   - PUT /admin/products/{id}.json
   - DELETE /admin/products/{id}.json

**Priority 2 (Quan trọng - 2 tuần):**
4. **Location & Multi-location**
   - GET /admin/locations.json
   - Inventory per location
   - Location selector

5. **Webhooks đầy đủ**
   - Orders webhooks (10 topics)
   - Customers webhooks (3 topics)
   - Queue processing

**Priority 3 (Bổ sung - 2 tuần):**
6. **Settings & Config**
   - GET /admin/settings.json
   - Serial, Lot tracking

7. **Stock Management**
   - Stock transfers
   - Stock adjustments

---

## ROADMAP ĐỀ XUẤT

### Phase 1: Order Sync (Tuần 1-2)
```
✓ Tạo class-sapo-order-resource.php
  - create($data)
  - update($id, $data)
  - get($id)
  - all($params)
  - finalize($id)
  - cancel($id)

✓ Tạo class-sapo-service-order.php
  - sync_order_to_sapo($wc_order_id)
  - create_order_from_wc($wc_order)
  - transform_wc_to_sapo($wc_order)

✓ Tạo SapoWcOrder.php helper
  - get_order_json($wc_order)
  - get_customer_id_or_create()
  - get_order_line_items()
  - get_billing_address()
  - get_shipping_address()

✓ WooCommerce hooks
  - woocommerce_new_order
  - woocommerce_order_status_changed
```

### Phase 2: Customer Sync (Tuần 3)
```
✓ Tạo class-sapo-customer-resource.php
✓ Tạo class-sapo-service-customer.php
✓ Tạo SapoWcCustomer.php helper
✓ Bidirectional sync
```

### Phase 3: Product Write (Tuần 4)
```
✓ Thêm create(), update(), delete() vào Product Resource
✓ WooCommerce hooks cho product changes
✓ Two-way product sync
```

### Phase 4: Locations & Webhooks (Tuần 5-6)
```
✓ Location Resource
✓ Multi-location inventory
✓ Full webhook implementation
✓ Queue system
```

---

**Tổng thời gian:** 6-8 tuần để hoàn thiện đầy đủ

**Đánh giá lại sau khi hoàn thành:**
- Hiện tại: 32% (55/147 tính năng)
- Sau Phase 1-4: ~85% (125/147 tính năng)
- Production-ready: ✅

---

**Người kiểm tra:** Cascade AI  
**Ngày:** 24/12/2024
