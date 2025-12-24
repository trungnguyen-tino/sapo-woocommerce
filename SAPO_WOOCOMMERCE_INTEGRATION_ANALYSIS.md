# PHÂN TÍCH TÍCH HỢP SAPO API VÀ WOOCOMMERCE

## 1. TỔNG QUAN

### 1.1. Giới thiệu SAPO API
SAPO là hệ thống quản lý bán hàng toàn diện của Việt Nam, cung cấp REST API để tích hợp với các hệ thống bên ngoài. API sử dụng OAuth 2.0 cho xác thực và hỗ trợ đầy đủ các thao tác CRUD cho các tài nguyên chính.

**URL API Base:** `https://{sub_domain}.mysapogo.com/admin`

**Tài liệu:** https://sapogo.dev/docs/

### 1.2. WooCommerce
WooCommerce là nền tảng thương mại điện tử mã nguồn mở phổ biến nhất cho WordPress, cung cấp REST API đầy đủ để quản lý sản phẩm, đơn hàng, khách hàng và các tài nguyên khác.

---

## 2. XÁC THỰC (AUTHENTICATION)

### 2.1. SAPO OAuth 2.0 Flow

**Bước 1: Lấy Credentials**
- Liên hệ SAPO để nhận: `API Key`, `Secret Key`, `Redirect URI`

**Bước 2: Xin cấp quyền**
```
https://{sub_domain}.mysapogo.com/admin/oauth/authorize?client_id={api_key}&scope={scopes}&redirect_uri={redirect_uri}
```

**Bước 3: Lấy Access Token**
```bash
POST https://{sub_domain}.mysapogo.com/admin/oauth/access_token
Content-Type: application/x-www-form-urlencoded

client_id={api_key}
client_secret={secret_key}
code={authorization_code}
```

**Response:**
```json
{
  "access_token": "f85632530bf277ec9ac6f649fc327f17"
}
```

**Bước 4: Sử dụng Access Token**
```
X-Sapo-Access-Token: {access_token}
```

### 2.2. Scopes (Quyền truy cập)

| Scope | Mô tả |
|-------|-------|
| `read_products`, `write_products` | Đọc/ghi sản phẩm |
| `read_customers`, `write_customers` | Đọc/ghi khách hàng |
| `read_suppliers`, `write_suppliers` | Đọc/ghi nhà cung cấp |
| `read_orders`, `write_orders` | Đọc/ghi đơn hàng |
| `read_fulfillments`, `write_fulfillments` | Đọc/ghi đơn giao hàng |
| `read_order_returns`, `write_order_returns` | Đọc/ghi đơn trả hàng |
| `read_stock_adjustments`, `write_stock_adjustments` | Đọc/ghi điều chỉnh kho |
| `read_stock_transfers`, `write_stock_transfers` | Đọc/ghi chuyển kho |
| `read_purchase_orders`, `write_purchase_orders` | Đọc/ghi đơn nhập hàng |

### 2.3. WooCommerce Authentication
WooCommerce hỗ trợ nhiều phương thức xác thực:
- **REST API Keys** (Consumer Key & Secret)
- **OAuth 1.0a** (cho ứng dụng bên ngoài)
- **JWT** (JSON Web Tokens)

---

## 3. CÁC TÍNH NĂNG CHÍNH CỦA SAPO API

### 3.1. QUẢN LÝ SẢN PHẨM (Products)

#### 3.1.1. Product API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/products.json` | Lấy danh sách sản phẩm |
| GET | `/admin/products/{id}.json` | Lấy chi tiết sản phẩm |
| POST | `/admin/products.json` | Tạo sản phẩm mới |
| PUT | `/admin/products/{id}.json` | Cập nhật sản phẩm |
| DELETE | `/admin/products/{id}.json` | Xóa sản phẩm |
| GET | `/admin/products/search.json` | Tìm kiếm sản phẩm |
| GET | `/admin/products/suggest_tags.json` | Gợi ý tags |
| GET | `/admin/products/tags.json` | Danh sách tags |
| POST | `/admin/products/{productId}/images.json` | Thêm ảnh |
| DELETE | `/admin/products/{productId}/images/{id}.json` | Xóa ảnh |

#### 3.1.2. Đặc điểm Product
- **Variants (Phiên bản)**: Hỗ trợ sản phẩm có nhiều phiên bản (size, màu sắc...)
- **Images**: Nhiều ảnh cho sản phẩm và variant
- **Options**: Thuộc tính và giá trị (size, color, etc.)
- **Inventories**: Quản lý tồn kho theo từng chi nhánh
- **Prices**: Giá bán theo price list
- **Composite Items**: Sản phẩm combo/gói
- **Product Medicines**: Sản phẩm thuốc với thông tin đặc biệt
- **Categories**: Phân loại sản phẩm
- **Brands**: Nhãn hiệu

#### 3.1.3. Filter Parameters
- `created_on_min`, `created_on_max`: Lọc theo thời gian tạo
- `category_ids`: Lọc theo loại sản phẩm
- `medicine`: Lọc sản phẩm thuốc
- `product_type`: Lọc theo loại (composite, normal...)
- `query`: Tìm kiếm text
- `sort_by`, `sort_direction`: Sắp xếp

### 3.2. QUẢN LÝ DANH MỤC

#### 3.2.1. Categories API

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/categories.json` | Danh sách loại sản phẩm |
| GET | `/admin/categories/{id}.json` | Chi tiết loại sản phẩm |
| POST | `/admin/categories.json` | Tạo loại sản phẩm |
| PUT | `/admin/categories/{id}.json` | Cập nhật loại sản phẩm |
| DELETE | `/admin/categories/{id}.json` | Xóa loại sản phẩm |

#### 3.2.2. Brands API

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/brands.json` | Danh sách nhãn hiệu |
| GET | `/admin/brands/{id}.json` | Chi tiết nhãn hiệu |
| POST | `/admin/brands.json` | Tạo nhãn hiệu |
| PUT | `/admin/brands/{id}.json` | Cập nhật nhãn hiệu |

### 3.3. QUẢN LÝ KHÁCH HÀNG (Customers)

#### 3.3.1. Customer API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/customers.json` | Danh sách khách hàng |
| GET | `/admin/customers/{id}.json` | Chi tiết khách hàng |
| POST | `/admin/customers.json` | Tạo khách hàng |
| PUT | `/admin/customers/{id}.json` | Cập nhật khách hàng |
| DELETE | `/admin/customers/{id}.json` | Xóa khách hàng |

#### 3.3.2. Addresses API

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/customers/{customerId}/addresses.json` | Danh sách địa chỉ |
| POST | `/admin/customers/{customerId}/addresses.json` | Tạo địa chỉ |
| GET | `/admin/customers/{customerId}/addresses/{id}.json` | Chi tiết địa chỉ |
| PUT | `/admin/customers/{customerId}/addresses/{id}.json` | Cập nhật địa chỉ |
| DELETE | `/admin/customers/{customerId}/addresses/{id}.json` | Xóa địa chỉ |

#### 3.3.3. Contacts & Notes API

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET/POST/PUT/DELETE | `/admin/customers/{customerId}/contacts.json` | Quản lý liên hệ |
| GET/POST/PUT/DELETE | `/admin/customers/{customerId}/notes.json` | Quản lý ghi chú |

#### 3.3.4. Đặc điểm Customer
- Hỗ trợ nhiều địa chỉ (nhà riêng, công ty...)
- Thông tin liên hệ chi tiết
- Ghi chú nội bộ
- Phân nhóm khách hàng
- Địa chỉ Việt Nam chi tiết (city, district, ward)

### 3.4. QUẢN LÝ ĐỐN HÀNG (Orders)

#### 3.4.1. Order API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/orders.json` | Danh sách đơn hàng |
| GET | `/admin/orders/{id}.json` | Chi tiết đơn hàng |
| POST | `/admin/orders.json` | Tạo đơn hàng |
| POST | `/admin/orders/pos.json` | Tạo đơn POS |
| PUT | `/admin/orders/{id}.json` | Cập nhật đơn hàng |
| POST | `/admin/orders/{id}/finalize.json` | Duyệt đơn |
| POST | `/admin/orders/{id}/cancel.json` | Hủy đơn |

#### 3.4.2. Đặc điểm Order
- **Statuses**: `draft`, `finalized`, `cancelled`, `fulfilled`, `paid`, `received`
- **Order Line Items**: Chi tiết sản phẩm trong đơn
- **Billing & Shipping Address**: Địa chỉ thanh toán và giao hàng
- **Prepayments**: Thanh toán trước
- **Price List**: Bảng giá áp dụng
- **Source**: Nguồn đơn hàng
- **Location**: Chi nhánh xử lý đơn
- **Tax**: Thuế chi tiết

#### 3.4.3. Headers Required
```
X-Sapo-LocationId: {location_id}
X-Sapo-AccountId: {account_id}
```

### 3.5. QUẢN LÝ NHÀ CUNG CẤP (Suppliers)

#### 3.5.1. Supplier API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/suppliers.json` | Danh sách nhà cung cấp |
| GET | `/admin/suppliers/{id}.json` | Chi tiết nhà cung cấp |
| POST | `/admin/suppliers.json` | Tạo nhà cung cấp |
| PUT | `/admin/suppliers/{id}.json` | Cập nhật nhà cung cấp |
| DELETE | `/admin/suppliers/{id}.json` | Xóa nhà cung cấp |
| GET/POST/PUT/DELETE | `/admin/suppliers/{supplierId}/addresses.json` | Quản lý địa chỉ |
| GET/POST/PUT/DELETE | `/admin/suppliers/{supplierId}/contacts.json` | Quản lý liên hệ |
| GET/POST/PUT/DELETE | `/admin/suppliers/{supplierId}/notes.json` | Quản lý ghi chú |

### 3.6. QUẢN LÝ NHẬP HÀNG (Purchase Orders)

#### 3.6.1. Purchase Order API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/purchase_orders.json` | Danh sách đơn nhập hàng |
| GET | `/admin/purchase_orders/{id}.json` | Chi tiết đơn nhập |
| POST | `/admin/purchase_orders.json` | Tạo đơn nhập |
| PUT | `/admin/purchase_orders/{id}.json` | Cập nhật đơn nhập |
| PUT | `/admin/purchase_orders/{id}/cancel.json` | Hủy đơn nhập |
| PUT | `/admin/purchase_orders/{id}/close.json` | Kết thúc đơn nhập |
| GET | `/admin/purchase_orders/suggest_tags.json` | Gợi ý tags |

#### 3.6.2. Đặc điểm Purchase Order
- **Landed Cost**: Chi phí nhập hàng
- **Line Items**: Sản phẩm nhập kho
- **Receipts**: Phiếu nhập kho
- **Transactions**: Thanh toán
- **Supplier Address**: Địa chỉ nhà cung cấp
- **Lots/Date**: Quản lý lô hàng và hạn sử dụng

### 3.7. CÀI ĐẶT VÀ VỊ TRÍ

#### 3.7.1. Tenant Settings API

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/settings.json` | Lấy cấu hình cửa hàng |
| PUT | `/admin/settings.json` | Cập nhật cấu hình |

**Cấu hình bao gồm:**
- `allow_product_serial`: Quản lý serial
- `allow_product_lots`: Quản lý lô - hạn sử dụng
- `allow_warranty`: Quản lý bảo hành

#### 3.7.2. Locations API

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/locations.json` | Danh sách chi nhánh |
| GET | `/admin/locations/{id}.json` | Chi tiết chi nhánh |

### 3.8. WEBHOOKS (Real-time Events)

#### 3.8.1. Webhook API Endpoints

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/admin/webhooks.json` | Danh sách webhooks |
| GET | `/admin/webhooks/{id}.json` | Chi tiết webhook |
| POST | `/admin/webhooks.json` | Đăng ký webhook |
| PUT | `/admin/webhooks/{id}.json` | Cập nhật webhook |
| DELETE | `/admin/webhooks/{id}.json` | Xóa webhook |

#### 3.8.2. Webhook Topics Hỗ trợ

**Products:**
- `products/create`
- `products/update`
- `products/delete`

**Orders:**
- `orders/create`
- `orders/update`
- `orders/cancelled`
- `orders/finalized`
- `orders/fulfilled`
- `orders/paid`
- `orders/received`
- `orders/create_return`
- `orders/receive_return`
- `orders/create_refund`

**Customers:**
- `customers/create`
- `customers/update`
- `customers/delete`

**Stock Transfers:**
- `stock_transfers/create`
- `stock_transfers/update`
- `stock_transfers/cancel`
- `stock_transfers/receive`
- `stock_transfers/ship`

**Purchase Orders:**
- `purchase_orders/create`
- `purchase_orders/update`
- `purchase_orders/cancelled`
- `purchase_orders/active`
- `purchase_orders/close`
- `purchase_orders/receipts`
- `purchase_orders/transactions`
- `purchase_orders/refunds`

**Vouchers:**
- `payment_vouchers/create`, `payment_vouchers/update`, `payment_vouchers/cancel`
- `receipt_vouchers/create`, `receipt_vouchers/update`, `receipt_vouchers/cancel`

**App:**
- `app/uninstalled`

#### 3.8.3. Webhook Security
- **Timeout**: 5 giây
- **Retry**: 19 lần trong 48 giờ
- **HMAC Validation**: Sử dụng Secret Key
- **Headers**:
  - `X-Sapo-Topic`: Tên topic
  - `X-Sapo-Hmac-Sha256`: HMAC signature
  - `X-Sapo-Tenant-Domain`: Domain của tenant

---

## 4. MAPPING GIỮA SAPO VÀ WOOCOMMERCE

### 4.1. Products Mapping

| SAPO | WooCommerce | Ghi chú |
|------|-------------|---------|
| Product | Product | Sản phẩm chính |
| Variant | Product Variation | Biến thể sản phẩm |
| Category | Product Category | Danh mục |
| Brand | Product Attribute/Meta | Nhãn hiệu (custom field) |
| Images | Product Images | Hình ảnh |
| Options | Product Attributes | Thuộc tính (size, color) |
| Inventories | Stock Management | Tồn kho |
| Tags | Product Tags | Thẻ tag |
| SKU | SKU | Mã sản phẩm |

### 4.2. Customers Mapping

| SAPO | WooCommerce | Ghi chú |
|------|-------------|---------|
| Customer | Customer | Khách hàng |
| Addresses | Billing/Shipping Address | Địa chỉ |
| Phone Number | Billing/Shipping Phone | Số điện thoại |
| Email | Email | Email |
| Notes | Customer Notes | Ghi chú |
| Customer Group | Customer Role/Meta | Nhóm khách hàng |

### 4.3. Orders Mapping

| SAPO | WooCommerce | Ghi chú |
|------|-------------|---------|
| Order | Order | Đơn hàng |
| Status (draft) | Pending Payment | Chờ xử lý |
| Status (finalized) | Processing | Đang xử lý |
| Status (cancelled) | Cancelled | Đã hủy |
| Status (fulfilled) | Completed | Hoàn thành |
| Order Line Items | Order Items | Sản phẩm trong đơn |
| Billing Address | Billing Address | Địa chỉ thanh toán |
| Shipping Address | Shipping Address | Địa chỉ giao hàng |
| Prepayments | Payments | Thanh toán |
| Total | Total | Tổng tiền |

### 4.4. Inventory Mapping

| SAPO | WooCommerce | Ghi chú |
|------|-------------|---------|
| Inventories | Stock Quantity | Tồn kho |
| Location | Warehouse/Location | Chi nhánh/kho |
| Stock Adjustments | Stock Management | Điều chỉnh kho |
| Stock Transfers | Stock Movement | Chuyển kho |

---

## 5. CÁC KỊCH BẢN TÍCH HỢP (USE CASES)

### 5.1. Đồng bộ Sản phẩm: WooCommerce → SAPO

**Mục đích:** Khi tạo/cập nhật sản phẩm trên WooCommerce, tự động đồng bộ sang SAPO

**Luồng xử lý:**
1. **Trigger**: WooCommerce webhook `product.created`, `product.updated`
2. **Mapping Data**: 
   - Product → SAPO Product
   - Variations → SAPO Variants
   - Categories → SAPO Categories
   - Images → SAPO Images
3. **API Call**: `POST/PUT /admin/products.json`
4. **Handle Response**: Lưu mapping ID (WooCommerce ID ↔ SAPO ID)

**Lợi ích:**
- Quản lý tồn kho tập trung trên SAPO
- Bán hàng đa kênh (online + offline)
- Báo cáo thống kê tổng hợp

### 5.2. Đồng bộ Tồn kho: SAPO → WooCommerce

**Mục đích:** Cập nhật tồn kho realtime từ SAPO sang WooCommerce

**Luồng xử lý:**
1. **Trigger**: SAPO webhook `products/update`
2. **Extract Data**: Lấy thông tin inventory từ webhook payload
3. **Update WooCommerce**: Cập nhật stock quantity qua WooCommerce REST API
4. **Logging**: Ghi log đồng bộ

**Lợi ích:**
- Tránh overselling
- Cập nhật tồn kho tức thời
- Đồng bộ giữa các chi nhánh

### 5.3. Đồng bộ Đơn hàng: WooCommerce → SAPO

**Mục đích:** Khi có đơn hàng mới trên WooCommerce, tạo đơn tương ứng trên SAPO

**Luồng xử lý:**
1. **Trigger**: WooCommerce webhook `order.created`
2. **Customer Sync**: 
   - Kiểm tra customer có tồn tại trên SAPO chưa
   - Nếu chưa: tạo customer mới
   - Nếu có: lấy customer_id
3. **Order Mapping**:
   - Order items → SAPO line items
   - Billing/Shipping address → SAPO addresses
   - Payment → SAPO prepayments
4. **API Call**: `POST /admin/orders.json`
5. **Update WooCommerce**: Lưu SAPO order_id vào WooCommerce meta

**Lợi ích:**
- Quản lý đơn hàng tập trung
- Xử lý đơn trên SAPO POS
- Báo cáo doanh thu tổng hợp

### 5.4. Cập nhật Trạng thái Đơn: SAPO → WooCommerce

**Mục đích:** Đồng bộ trạng thái đơn hàng từ SAPO về WooCommerce

**Luồng xử lý:**
1. **Trigger**: SAPO webhooks
   - `orders/finalized` → Processing
   - `orders/fulfilled` → Completed
   - `orders/cancelled` → Cancelled
2. **Status Mapping**: Map trạng thái SAPO → WooCommerce
3. **Update Order**: Cập nhật trạng thái qua WooCommerce API
4. **Email Notification**: WooCommerce tự động gửi email cho khách

**Lợi ích:**
- Khách hàng được thông báo kịp thời
- Trạng thái đồng bộ 2 chiều
- Giảm thao tác thủ công

### 5.5. Đồng bộ Khách hàng: Hai chiều

**Mục đích:** Đồng bộ thông tin khách hàng giữa 2 hệ thống

**Luồng xử lý:**
1. **WooCommerce → SAPO**:
   - Webhook `customer.created`, `customer.updated`
   - API: `POST/PUT /admin/customers.json`
2. **SAPO → WooCommerce**:
   - Webhook `customers/create`, `customers/update`
   - API: WooCommerce Customer API

**Lợi ích:**
- Dữ liệu khách hàng thống nhất
- Lịch sử mua hàng đầy đủ
- CRM tốt hơn

### 5.6. Quản lý Nhập hàng

**Mục đích:** Sử dụng SAPO để quản lý nhập hàng, tự động cập nhật tồn kho WooCommerce

**Luồng xử lý:**
1. **Tạo Purchase Order**: Trên SAPO POS/Admin
2. **Trigger**: Webhook `purchase_orders/receipts`
3. **Update Inventory**: Cập nhật tồn kho trên WooCommerce
4. **Cost Tracking**: Ghi nhận giá nhập (WooCommerce meta)

**Lợi ích:**
- Quản lý nhà cung cấp chuyên nghiệp
- Tính giá vốn chính xác
- Báo cáo lợi nhuận

### 5.7. Báo cáo & Analytics

**Mục đích:** Tổng hợp dữ liệu từ cả SAPO và WooCommerce

**Luồng xử lý:**
1. **Sync Schedule**: Chạy định kỳ (hourly/daily)
2. **Data Collection**: 
   - Orders từ SAPO
   - Orders từ WooCommerce
   - Inventory data
   - Customer data
3. **Warehouse**: Lưu vào database tổng hợp
4. **Reporting**: Dashboard tổng quan

**Lợi ích:**
- Báo cáo doanh thu tổng hợp
- Phân tích xu hướng
- Tối ưu kinh doanh

---

## 6. KIẾN TRÚC TÍCH HỢP ĐỀ XUẤT

### 6.1. Kiến trúc Tổng quan

```
┌─────────────────┐         ┌─────────────────┐
│   WooCommerce   │◄────────┤  Integration    │
│   (WordPress)   │────────►│   Middleware    │
└─────────────────┘         │   (Plugin/API)  │
                            └─────────────────┘
                                    ▲│
                                    │▼
                            ┌─────────────────┐
                            │   SAPO API      │
                            │   (mysapogo.com)│
                            └─────────────────┘
```

### 6.2. Components

#### 6.2.1. WordPress Plugin (WooCommerce Extension)
**Chức năng:**
- Cài đặt OAuth credentials cho SAPO
- Mapping giữa WooCommerce và SAPO resources
- Xử lý webhooks từ cả 2 hệ thống
- Queue management cho sync tasks
- Admin UI để cấu hình và monitoring

**Công nghệ:**
- PHP 7.4+
- WordPress Plugin API
- WooCommerce Hooks & Filters
- WordPress REST API
- WP-Cron hoặc Action Scheduler

#### 6.2.2. Sync Engine
**Chức năng:**
- Bidirectional sync logic
- Conflict resolution
- Error handling & retry
- Rate limiting
- Data transformation

**Patterns:**
- Queue-based processing
- Event-driven architecture
- Idempotent operations
- Eventual consistency

#### 6.2.3. Webhook Handler
**Chức năng:**
- Nhận webhooks từ SAPO
- Nhận webhooks từ WooCommerce
- Validate HMAC signatures
- Queue events for processing

**Security:**
- HMAC-SHA256 validation (SAPO)
- WooCommerce webhook secret validation
- IP whitelist (optional)
- Rate limiting

#### 6.2.4. Mapping Database
**Chức năng:**
- Lưu mapping ID giữa 2 hệ thống
- Cache data để giảm API calls
- Sync history & logs

**Schema:**
```sql
CREATE TABLE sapo_woo_mappings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50), -- product, customer, order
    woo_id BIGINT,
    sapo_id BIGINT,
    last_synced_at DATETIME,
    sync_direction VARCHAR(20), -- woo_to_sapo, sapo_to_woo, bidirectional
    metadata JSON,
    created_at DATETIME,
    updated_at DATETIME,
    INDEX idx_entity_woo (entity_type, woo_id),
    INDEX idx_entity_sapo (entity_type, sapo_id)
);

CREATE TABLE sapo_sync_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50),
    entity_id BIGINT,
    action VARCHAR(50), -- sync_create, sync_update, sync_delete
    status VARCHAR(20), -- pending, success, failed
    error_message TEXT,
    request_data JSON,
    response_data JSON,
    created_at DATETIME,
    INDEX idx_status (status, created_at)
);
```

### 6.3. Data Flow

#### 6.3.1. Product Sync: WooCommerce → SAPO
```
1. User creates product in WooCommerce
2. WooCommerce action hook triggered
3. Queue sync job
4. Worker picks up job
5. Transform WooCommerce product → SAPO format
6. Call SAPO API: POST /admin/products.json
7. Store mapping: WooCommerce ID ↔ SAPO ID
8. Update sync status
9. (If failed) Schedule retry
```

#### 6.3.2. Inventory Sync: SAPO → WooCommerce
```
1. SAPO sends webhook: products/update
2. Webhook handler validates HMAC
3. Queue sync job
4. Worker picks up job
5. Extract inventory data from payload
6. Lookup WooCommerce product ID from mapping
7. Update WooCommerce stock via REST API
8. Log sync status
```

#### 6.3.3. Order Sync: WooCommerce → SAPO
```
1. Customer places order on WooCommerce
2. Order status changes to "processing"
3. Webhook triggered
4. Queue order sync job
5. Check if customer exists in SAPO
   - If not: create customer first
6. Transform order data → SAPO format
7. Call SAPO API: POST /admin/orders.json
8. Store order mapping
9. Update WooCommerce order meta with SAPO order ID
```

### 6.4. Error Handling

#### 6.4.1. Retry Strategy
```php
- Immediate retry: 0s
- Retry 1: 30s
- Retry 2: 1m
- Retry 3: 5m
- Retry 4: 15m
- Retry 5: 30m
- Max retries: 5
- After max: Mark as failed, send admin notification
```

#### 6.4.2. Conflict Resolution
**Scenario:** Sản phẩm được cập nhật đồng thời trên cả 2 hệ thống

**Solution:**
- Sử dụng timestamp để xác định version mới nhất
- Priority rule: Cấu hình hệ thống nào là master
- Manual review: Admin dashboard để resolve conflicts

### 6.5. Performance Optimization

#### 6.5.1. Caching
- Cache SAPO access token (1 hour)
- Cache mapping data (Redis/Memcached)
- Cache product/customer data để giảm API calls

#### 6.5.2. Batch Processing
- Group multiple updates thành batch
- Process 50-100 items per batch
- Rate limiting: Respect API limits

#### 6.5.3. Background Processing
- Sử dụng Action Scheduler (WooCommerce)
- Queue-based: Redis Queue hoặc Database Queue
- Async processing: Không block user requests

---

## 7. TÍNH NĂNG PLUGIN WOOCOMMERCE

### 7.1. Cài đặt & Cấu hình

#### 7.1.1. SAPO Connection Settings
- **Sub-domain**: Tên miền SAPO (xyz.mysapogo.com)
- **API Key**: Client ID
- **Secret Key**: Client Secret
- **Redirect URI**: Callback URL sau OAuth
- **OAuth Flow**: Button "Connect to SAPO"
- **Test Connection**: Kiểm tra kết nối

#### 7.1.2. Sync Settings
**Products:**
- ☑ Enable product sync
- Direction: [WooCommerce → SAPO] [SAPO → WooCommerce] [Bidirectional]
- Sync frequency: [Real-time] [Every 15 mins] [Hourly] [Daily]
- ☑ Sync categories
- ☑ Sync images
- ☑ Sync inventory
- Default location ID: [Select location]

**Customers:**
- ☑ Enable customer sync
- Direction: [Bidirectional]
- ☑ Sync addresses
- ☑ Sync on order creation

**Orders:**
- ☑ Enable order sync
- Direction: [WooCommerce → SAPO]
- ☑ Auto finalize orders on SAPO
- Default payment method ID: [Select]
- Default source ID: [Select]

**Inventory:**
- ☑ Sync inventory from SAPO
- Update frequency: [Real-time via webhook] [Every 5 mins]
- ☑ Prevent overselling

#### 7.1.3. Field Mapping
**Product Fields:**
| WooCommerce | SAPO | Map? |
|-------------|------|------|
| Title | Name | ☑ |
| Description | Description | ☑ |
| Short Description | Summary | ☑ |
| SKU | SKU | ☑ |
| Regular Price | Price | ☑ |
| Categories | Categories | ☑ |
| Tags | Tags | ☑ |
| Brand (attribute) | Brand | ☑ |

**Custom Field Mapping:**
- Add custom mappings cho meta fields

### 7.2. Dashboard & Monitoring

#### 7.2.1. Overview Dashboard
- **Connection Status**: Connected/Disconnected
- **Last Sync**: 5 minutes ago
- **Synced Today**: 
  - Products: 150 items
  - Orders: 25 orders
  - Customers: 30 customers
- **Pending Queue**: 5 jobs
- **Failed Syncs**: 2 (view details)

#### 7.2.2. Sync Logs
**Table View:**
| Time | Entity | Type | Direction | Status | Details |
|------|--------|------|-----------|--------|---------|
| 10:30:15 | Product #123 | Update | WC→SAPO | Success | View |
| 10:29:45 | Order #456 | Create | WC→SAPO | Success | View |
| 10:28:30 | Product #789 | Update | SAPO→WC | Failed | View Error |

**Filters:**
- Date range
- Entity type
- Status
- Direction

#### 7.2.3. Mapping Manager
**View Mappings:**
| WooCommerce ID | SAPO ID | Entity Type | Last Synced | Actions |
|----------------|---------|-------------|-------------|---------|
| 123 | 456789 | Product | 2024-12-24 10:30 | Re-sync / Unlink |
| 456 | 789012 | Order | 2024-12-24 10:29 | View |

**Actions:**
- Manual sync
- Unlink mapping
- Force re-sync all

#### 7.2.4. Error Management
**Failed Jobs:**
- View error details
- Retry button
- Retry all failed
- Delete failed jobs
- Export error log

### 7.3. Admin UI Features

#### 7.3.1. Product Edit Screen
**Meta Box: "SAPO Sync"**
- ☑ Enable sync for this product
- SAPO Product ID: 123456 (View on SAPO)
- Last synced: 5 mins ago
- Status: ✓ Synced
- [Sync Now] button

#### 7.3.2. Order Edit Screen
**Meta Box: "SAPO Order"**
- SAPO Order ID: 789012 (View on SAPO)
- SAPO Status: finalized
- Location: Chi nhánh Hà Nội
- Last synced: 10 mins ago
- [Sync to SAPO] button (if not synced)

#### 7.3.3. Bulk Actions
**Products List:**
- Bulk Action: "Sync to SAPO"
- Bulk Action: "Re-sync from SAPO"

**Orders List:**
- Bulk Action: "Sync to SAPO"

### 7.4. CLI Commands (WP-CLI)

```bash
# Sync all products
wp sapo sync products --direction=to-sapo

# Sync specific product
wp sapo sync product 123 --direction=to-sapo

# Sync all orders
wp sapo sync orders --date-from=2024-01-01

# Clear sync queue
wp sapo queue clear

# Retry failed jobs
wp sapo queue retry-failed

# Test connection
wp sapo test-connection

# Export mappings
wp sapo export mappings --file=mappings.csv
```

---

## 8. API RATE LIMITING & BEST PRACTICES

### 8.1. SAPO API Limits
- **Rate Limit**: Cần xác nhận với SAPO (thường ~40 requests/minute)
- **Webhook Timeout**: 5 giây
- **Webhook Retry**: 19 lần trong 48 giờ

### 8.2. Best Practices

#### 8.2.1. API Calls
- Sử dụng batch operations khi có thể
- Cache access tokens
- Implement exponential backoff
- Monitor API usage

#### 8.2.2. Webhooks
- Respond 200 OK ngay lập tức
- Process async trong background
- Validate HMAC signatures
- Handle duplicate webhooks (idempotency)

#### 8.2.3. Data Consistency
- Use transaction IDs để track changes
- Implement conflict resolution
- Regular full sync (nightly)
- Audit logs

---

## 9. SECURITY CONSIDERATIONS

### 9.1. Authentication
- Store API credentials encrypted trong WordPress options
- Use WordPress salts cho encryption
- Refresh tokens theo schedule
- Secure OAuth redirect URI

### 9.2. Webhooks
- Validate HMAC signatures (SAPO)
- Validate WooCommerce webhook signatures
- Use HTTPS only
- IP whitelist (optional)

### 9.3. Data Protection
- Sanitize all inputs
- Escape all outputs
- SQL injection prevention (use $wpdb prepared statements)
- XSS prevention
- CSRF protection

### 9.4. Compliance
- GDPR: Customer data handling
- PCI DSS: Payment information (không lưu card data)
- Data retention policies
- Privacy policy updates

---

## 10. LỘ TRÌNH TRIỂN KHAI

### Phase 1: Foundation (Tuần 1-2)
- [ ] Setup project structure
- [ ] Implement OAuth 2.0 flow
- [ ] Admin settings page
- [ ] Connection testing
- [ ] Basic logging system

### Phase 2: Product Sync (Tuần 3-4)
- [ ] WooCommerce → SAPO product sync
- [ ] Simple product support
- [ ] Variable product → variants mapping
- [ ] Category sync
- [ ] Image sync
- [ ] Manual sync triggers

### Phase 3: Inventory Sync (Tuần 5)
- [ ] SAPO → WooCommerce inventory sync
- [ ] Webhook handler for inventory updates
- [ ] Multi-location support
- [ ] Stock status mapping

### Phase 4: Customer Sync (Tuần 6)
- [ ] Bidirectional customer sync
- [ ] Address mapping
- [ ] Customer creation on order
- [ ] Customer group handling

### Phase 5: Order Sync (Tuần 7-8)
- [ ] WooCommerce → SAPO order sync
- [ ] Order status mapping
- [ ] Payment mapping
- [ ] Shipping address handling
- [ ] Line items transformation

### Phase 6: Webhook Integration (Tuần 9)
- [ ] SAPO webhook receiver
- [ ] HMAC validation
- [ ] Event routing
- [ ] Queue system
- [ ] Webhook management UI

### Phase 7: Advanced Features (Tuần 10-11)
- [ ] Bidirectional product sync
- [ ] Order status updates: SAPO → WooCommerce
- [ ] Bulk sync operations
- [ ] Conflict resolution
- [ ] Advanced mapping UI

### Phase 8: Monitoring & Admin (Tuần 12)
- [ ] Dashboard overview
- [ ] Sync logs viewer
- [ ] Mapping manager
- [ ] Error handling UI
- [ ] Performance monitoring

### Phase 9: Testing & QA (Tuần 13-14)
- [ ] Unit tests
- [ ] Integration tests
- [ ] Performance testing
- [ ] Security audit
- [ ] Documentation

### Phase 10: Launch & Support (Tuần 15-16)
- [ ] Beta release
- [ ] User feedback
- [ ] Bug fixes
- [ ] Final documentation
- [ ] Official release

---

## 11. TESTING STRATEGY

### 11.1. Unit Tests
- OAuth flow components
- Data transformation functions
- Mapping logic
- Error handlers

### 11.2. Integration Tests
- Full product sync flow
- Full order sync flow
- Webhook processing
- API error scenarios

### 11.3. Manual Testing Checklist

#### Products
- [ ] Create simple product → syncs to SAPO
- [ ] Update product → updates on SAPO
- [ ] Delete product → deletes on SAPO (or marks deleted)
- [ ] Create variable product → creates with variants
- [ ] Update variant → updates on SAPO
- [ ] Inventory update on SAPO → updates WooCommerce

#### Orders
- [ ] Place order → creates on SAPO
- [ ] Order with new customer → creates customer first
- [ ] Order with existing customer → uses existing
- [ ] Multiple line items → all items synced
- [ ] Shipping/Billing addresses → properly formatted
- [ ] Payment recorded → prepayments created

#### Customers
- [ ] Create customer → syncs to SAPO
- [ ] Update customer → updates on SAPO
- [ ] Multiple addresses → all synced

#### Error Scenarios
- [ ] API timeout → retry works
- [ ] Invalid credentials → error message
- [ ] Duplicate webhook → handled gracefully
- [ ] Network error → queues for retry
- [ ] Rate limit hit → backoff works

---

## 12. DOCUMENTATION REQUIREMENTS

### 12.1. User Documentation
- Installation guide
- Configuration guide
- How to connect SAPO account
- Field mapping guide
- Troubleshooting guide
- FAQ

### 12.2. Developer Documentation
- Architecture overview
- API reference
- Hooks & filters
- Code examples
- Contributing guide

### 12.3. Video Tutorials
- Installation & setup
- Connecting to SAPO
- Syncing products
- Managing orders
- Troubleshooting

---

## 13. SUPPORT & MAINTENANCE

### 13.1. Support Channels
- Documentation site
- Email support
- Support forum
- GitHub issues (if open source)

### 13.2. Maintenance Plan
- Regular updates for WordPress/WooCommerce compatibility
- Security patches
- Bug fixes
- Feature requests evaluation
- Performance monitoring

### 13.3. Backup & Recovery
- Export/import mapping data
- Backup sync logs
- Recovery procedures
- Disaster recovery plan

---

## 14. PRICING & LICENSING (Nếu thương mại hóa)

### 14.1. Licensing Models
- **Free Version**: Basic sync features
- **Pro Version**: Advanced features, webhooks, priority support
- **Enterprise**: Custom integration, dedicated support

### 14.2. Feature Comparison

| Feature | Free | Pro | Enterprise |
|---------|------|-----|------------|
| Product Sync | ✓ | ✓ | ✓ |
| Basic Inventory Sync | ✓ | ✓ | ✓ |
| Order Sync | Limited | ✓ | ✓ |
| Customer Sync | - | ✓ | ✓ |
| Real-time Webhooks | - | ✓ | ✓ |
| Bulk Operations | - | ✓ | ✓ |
| Advanced Mapping | - | ✓ | ✓ |
| Priority Support | - | ✓ | ✓ |
| Custom Development | - | - | ✓ |

---

## 15. KẾT LUẬN

### 15.1. Tóm tắt

Tích hợp SAPO API với WooCommerce mang lại giải pháp quản lý đa kênh toàn diện cho các doanh nghiệp Việt Nam. Với khả năng đồng bộ sản phẩm, khách hàng, đơn hàng và tồn kho, plugin giúp:

✅ **Quản lý tập trung**: Dữ liệu đồng bộ giữa online và offline
✅ **Tối ưu vận hành**: Giảm thao tác thủ công, tăng hiệu suất
✅ **Chính xác**: Tồn kho realtime, tránh overselling
✅ **Mở rộng**: Dễ dàng scale khi kinh doanh phát triển

### 15.2. Lợi ích Kinh doanh

- **Tăng doanh thu**: Bán đa kênh hiệu quả
- **Giảm chi phí**: Tự động hóa quy trình
- **Trải nghiệm tốt**: Khách hàng hài lòng hơn
- **Dữ liệu tốt hơn**: Báo cáo và phân tích chính xác

### 15.3. Technical Highlights

- **RESTful API**: Cả SAPO và WooCommerce đều có REST API mạnh mẽ
- **Webhooks**: Đồng bộ realtime với hiệu suất cao
- **WordPress Ecosystem**: Tận dụng WordPress/WooCommerce infrastructure
- **Scalable**: Kiến trúc queue-based, xử lý async
- **Secure**: OAuth 2.0, HMAC validation, encryption

### 15.4. Next Steps

1. **Khởi động dự án**: Setup development environment
2. **POC (Proof of Concept)**: Implement basic product sync
3. **Feedback**: Thu thập feedback từ beta users
4. **Iterate**: Cải thiện dựa trên feedback
5. **Launch**: Official release

---

## 16. PHỤ LỤC

### 16.1. Glossary

- **OAuth**: Open Authorization - Giao thức xác thực
- **REST API**: Representational State Transfer API
- **Webhook**: HTTP callback khi có event xảy ra
- **HMAC**: Hash-based Message Authentication Code
- **SKU**: Stock Keeping Unit - Mã quản lý hàng hóa
- **Variant**: Biến thể sản phẩm
- **Fulfillment**: Giao hàng
- **POS**: Point of Sale - Điểm bán hàng

### 16.2. References

- **SAPO API Docs**: https://sapogo.dev/docs/
- **WooCommerce REST API**: https://woocommerce.github.io/woocommerce-rest-api-docs/
- **WordPress Plugin Handbook**: https://developer.wordpress.org/plugins/
- **OAuth 2.0 Spec**: https://oauth.net/2/

### 16.3. Sample Code Snippets

#### PHP: SAPO OAuth Flow
```php
<?php
class Sapo_OAuth {
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $subdomain;
    
    public function get_authorization_url($scopes) {
        $params = [
            'client_id' => $this->client_id,
            'scope' => implode(',', $scopes),
            'redirect_uri' => $this->redirect_uri
        ];
        return "https://{$this->subdomain}.mysapogo.com/admin/oauth/authorize?" . http_build_query($params);
    }
    
    public function get_access_token($code) {
        $response = wp_remote_post("https://{$this->subdomain}.mysapogo.com/admin/oauth/access_token", [
            'body' => [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'code' => $code
            ]
        ]);
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['access_token'] ?? null;
    }
}
```

#### PHP: SAPO API Client
```php
<?php
class Sapo_API_Client {
    private $access_token;
    private $subdomain;
    private $base_url;
    
    public function __construct($subdomain, $access_token) {
        $this->subdomain = $subdomain;
        $this->access_token = $access_token;
        $this->base_url = "https://{$subdomain}.mysapogo.com/admin";
    }
    
    public function request($method, $endpoint, $data = []) {
        $url = $this->base_url . $endpoint;
        
        $args = [
            'method' => $method,
            'headers' => [
                'X-Sapo-Access-Token' => $this->access_token,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];
        
        if (!empty($data)) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code >= 400) {
            throw new Exception("API Error: " . ($body['error'] ?? 'Unknown error'));
        }
        
        return $body;
    }
    
    public function get_products($params = []) {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/products.json{$query}");
    }
    
    public function create_product($product_data) {
        return $this->request('POST', '/products.json', ['product' => $product_data]);
    }
    
    public function update_product($product_id, $product_data) {
        return $this->request('PUT', "/products/{$product_id}.json", ['product' => $product_data]);
    }
}
```

#### PHP: Webhook HMAC Validation
```php
<?php
function validate_sapo_webhook($secret_key) {
    $hmac_header = $_SERVER['HTTP_X_SAPO_HMAC_SHA256'] ?? '';
    $body = file_get_contents('php://input');
    
    $calculated_hmac = base64_encode(hash_hmac('sha256', $body, $secret_key, true));
    
    return hash_equals($calculated_hmac, $hmac_header);
}
```

---

**Phiên bản:** 1.0  
**Ngày tạo:** 24/12/2024  
**Tác giả:** Sapo-WooCommerce Integration Team  
**Liên hệ:** support@example.com
