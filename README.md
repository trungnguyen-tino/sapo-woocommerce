# SAPO WooCommerce Sync

Plugin đồng bộ sản phẩm và tồn kho tự động từ SAPO.VN về WooCommerce.

## Tính năng

- ✅ **OAuth 2.0 Authentication** - Kết nối an toàn với SAPO
- ✅ **Product Sync** - Đồng bộ sản phẩm từ SAPO về WooCommerce
- ✅ **Auto Inventory Sync** - Tự động cập nhật tồn kho theo thời gian
- ✅ **Variable Product Support** - Hỗ trợ sản phẩm có biến thể
- ✅ **Rate Limiting** - Giới hạn 40 requests/phút, 80,000 requests/ngày
- ✅ **ReactJS Admin Dashboard** - Giao diện quản lý hiện đại (không cần build)
- ✅ **REST API** - API đầy đủ cho các tác vụ đồng bộ
- ✅ **Webhook Support** - Nhận cập nhật real-time từ SAPO
- ✅ **Logging System** - Ghi nhật ký đầy đủ các hoạt động

## Yêu cầu

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 6.0+
- cURL enabled
- allow_url_fopen enabled (optional, cho download ảnh)

## Cài đặt

1. Upload plugin folder vào `/wp-content/plugins/`
2. Kích hoạt plugin trong WordPress Admin
3. Truy cập **SAPO Sync** trong menu admin
4. Nhập thông tin kết nối SAPO (Store, Client ID, Client Secret)
5. Click "Kết nối SAPO" để xác thực

## Cấu hình

### 1. Kết nối SAPO

- **Store Name**: `tenant.mysapo.net`
- **Client ID**: Lấy từ SAPO App
- **Client Secret**: Lấy từ SAPO App
- **Scopes**: `read_products`, `read_inventory`

### 2. Đồng bộ tự động

- Bật/tắt đồng bộ tồn kho tự động
- Chọn tần suất đồng bộ (5, 15, 30, 60 phút)
- Cấu hình cập nhật: Giá, Tồn kho, Hình ảnh

### 3. Webhook (Optional)

- Nhận cập nhật real-time từ SAPO
- Webhook URL: `https://yourdomain.com/wp-json/sapo/v1/webhook`

## Sử dụng

### Đồng bộ sản phẩm

1. Vào tab **Sản phẩm SAPO**
2. Chọn sản phẩm cần đồng bộ
3. Click "Đồng bộ"

### Đồng bộ thủ công

1. Vào **Dashboard**
2. Click "Đồng bộ ngay"

### Xem nhật ký

1. Vào tab **Nhật ký**
2. Xem lịch sử đồng bộ và lỗi

## REST API Endpoints

### Authentication
- `POST /wp-json/sapo/v1/auth/url` - Get OAuth URL
- `POST /wp-json/sapo/v1/auth/disconnect` - Disconnect

### Products
- `GET /wp-json/sapo/v1/products` - Get SAPO products
- `POST /wp-json/sapo/v1/products/sync` - Sync products
- `GET /wp-json/sapo/v1/products/synced` - Get synced products

### Sync
- `POST /wp-json/sapo/v1/sync/inventory` - Manual sync
- `GET /wp-json/sapo/v1/sync/status` - Get sync status

### Logs
- `GET /wp-json/sapo/v1/logs` - Get logs
- `POST /wp-json/sapo/v1/logs/clear` - Clear logs

## Database Tables

- `wp_sapo_sync_products` - Product mappings
- `wp_sapo_sync_config` - Configuration
- `wp_sapo_sync_logs` - Sync logs
- `wp_sapo_webhooks` - Webhook deliveries

## Troubleshooting

### Lỗi kết nối OAuth
- Kiểm tra Store name, Client ID, Secret
- Kiểm tra Redirect URI trong SAPO App

### Lỗi download ảnh
- Bật `allow_url_fopen` trong php.ini
- Hoặc cài đặt cURL extension

### Rate limit exceeded
- Giảm tần suất đồng bộ
- Chờ quota reset

## Hỗ trợ

- Documentation: [TECHNICAL_SPEC.md](TECHNICAL_SPEC.md)
- SAPO API Docs: https://support.sapo.vn

## License

GPL-2.0+

## Author

Trung Nguyen

## Version

1.0.0
