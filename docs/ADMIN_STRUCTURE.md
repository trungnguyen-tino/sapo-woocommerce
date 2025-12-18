# Cấu Trúc Admin Dashboard

## Tổng Quan

Admin dashboard được xây dựng bằng ReactJS với kiến trúc component-based, không cần build process (sử dụng Babel Standalone).

## Cấu Trúc Files

```
admin/
├── class-sapo-admin.php          # Admin controller chính
├── assets/
│   ├── css/
│   │   └── admin.css             # Styles cho admin
│   └── js/
│       ├── app.jsx               # Main React app
│       └── components/           # React components
│           ├── Toast.jsx         # Toast notifications
│           ├── ConnectView.jsx   # OAuth connection view
│           ├── DashboardView.jsx # Dashboard với stats
│           ├── ProductsView.jsx  # Danh sách sản phẩm SAPO
│           ├── SyncedProductsView.jsx  # Sản phẩm đã sync
│           ├── SettingsView.jsx  # Trang cài đặt
│           └── LogsView.jsx      # Nhật ký đồng bộ
└── views/
    └── dashboard.php             # Template HTML gốc
```

## Components

### 1. Toast (Toast.jsx)
**Props:**
- `message` (string) - Nội dung thông báo
- `type` (string) - 'success' hoặc 'error'
- `onClose` (function) - Callback khi đóng

**Mô tả:** Hiển thị thông báo tự động ẩn sau 3 giây.

### 2. ConnectView (ConnectView.jsx)
**Props:**
- `config` (object) - Cấu hình hiện tại
- `onConnect` (function) - Callback khi kết nối
- `onSaveConfig` (function) - Callback khi lưu config

**Mô tả:** Form kết nối OAuth với SAPO (Store, Client ID, Secret).

### 3. DashboardView (DashboardView.jsx)
**Props:**
- `syncStatus` (object) - Trạng thái đồng bộ
- `onManualSync` (function) - Callback đồng bộ thủ công

**Mô tả:** Dashboard với 3 stats cards và nút đồng bộ.

### 4. ProductsView (ProductsView.jsx)
**Props:**
- `products` (array) - Danh sách sản phẩm SAPO
- `onSync` (function) - Callback đồng bộ
- `loading` (boolean) - Trạng thái loading

**Features:**
- Hiển thị table sản phẩm từ SAPO
- Checkbox chọn nhiều sản phẩm
- Bulk sync products
- Hiển thị thông tin: ID, tên, SKU, giá, tồn kho

### 5. SyncedProductsView (SyncedProductsView.jsx)
**Props:**
- `products` (array) - Sản phẩm đã sync
- `onRefresh` (function) - Làm mới danh sách
- `onResync` (function) - Đồng bộ lại
- `onDeleteMapping` (function) - Xóa mapping

**Features:**
- Hiển thị mapping SAPO ↔ WooCommerce
- Link đến WC product editor
- Re-sync và delete mapping

### 6. SettingsView (SettingsView.jsx)
**Props:**
- `config` (object) - Cấu hình
- `onSave` (function) - Lưu cài đặt
- `onDisconnect` (function) - Ngắt kết nối

**Features:**
- Hiển thị thông tin kết nối
- Cấu hình auto sync
- Tùy chọn đồng bộ (price, stock, images)
- Webhook và debug mode

### 7. LogsView (LogsView.jsx)
**Props:**
- `logs` (array) - Danh sách logs
- `onClearLogs` (function) - Xóa logs
- `onRefresh` (function) - Làm mới

**Features:**
- Filter theo log type và status
- Hiển thị thời gian, action, message
- Clear all logs

## Main App (app.jsx)

### State Management
```javascript
const [loading, setLoading] = useState(false);
const [view, setView] = useState('dashboard');
const [connected, setConnected] = useState(false);
const [config, setConfig] = useState({});
const [products, setProducts] = useState([]);
const [syncedProducts, setSyncedProducts] = useState([]);
const [logs, setLogs] = useState([]);
const [syncStatus, setSyncStatus] = useState({});
const [toast, setToast] = useState(null);
```

### API Helper
```javascript
const api = async (endpoint, options = {}) => {
    const response = await fetch(wpApiSettings.root + endpoint, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': wpApiSettings.nonce,
            ...options.headers
        }
    });
    
    if (!response.ok) {
        const data = await response.json();
        throw new Error(data.message || 'API Error');
    }
    
    return response.json();
};
```

### Navigation
5 tabs:
1. Dashboard - Overview & manual sync
2. Sản phẩm SAPO - Browse & sync SAPO products
3. Đã đồng bộ - View synced products
4. Cài đặt - Configuration
5. Nhật ký - Logs

## Thêm Component Mới

### Bước 1: Tạo file component
```bash
touch admin/assets/js/components/NewComponent.jsx
```

### Bước 2: Viết component
```jsx
function NewComponent({ propA, propB, onAction }) {
    const { useState } = React;
    
    return (
        <div>
            {/* Component UI */}
        </div>
    );
}
```

### Bước 3: Register trong admin controller
```php
// admin/class-sapo-admin.php
$components = array(
    // ... existing components
    'NewComponent'
);
```

### Bước 4: Sử dụng trong app.jsx
```jsx
{!loading && view === 'new-view' && (
    <NewComponent 
        propA={valueA}
        propB={valueB}
        onAction={handleAction}
    />
)}
```

## Best Practices

### 1. Component Design
- Mỗi component nên tập trung vào 1 chức năng duy nhất
- Props typing rõ ràng
- Xử lý loading và error states

### 2. State Management
- State ở app.jsx, props xuống components
- Callbacks để components giao tiếp với app
- Tránh prop drilling quá sâu

### 3. API Calls
- Luôn dùng helper `api()` từ app.jsx
- Xử lý errors với try/catch
- Hiển thị toast notifications

### 4. Styling
- Sử dụng CSS classes từ admin.css
- Inline styles cho overrides nhỏ
- Consistent spacing và colors

### 5. Performance
- Lazy load data khi switch view
- Debounce search/filter inputs
- Pagination cho large lists

## API Endpoints Sử dụng

```javascript
// Auth
GET  /wp-json/sapo/v1/auth/status
POST /wp-json/sapo/v1/auth/url
POST /wp-json/sapo/v1/auth/disconnect

// Config
GET  /wp-json/sapo/v1/config
POST /wp-json/sapo/v1/config

// Products
GET  /wp-json/sapo/v1/products
POST /wp-json/sapo/v1/products/sync
GET  /wp-json/sapo/v1/products/synced
POST /wp-json/sapo/v1/products/{id}/re-sync
DELETE /wp-json/sapo/v1/products/mapping/{id}

// Sync
POST /wp-json/sapo/v1/sync/inventory
GET  /wp-json/sapo/v1/sync/status

// Logs
GET  /wp-json/sapo/v1/logs
POST /wp-json/sapo/v1/logs/clear
```

## Debugging

### Console Logs
```javascript
// Bật debug mode trong settings
if (config.debug_mode) {
    console.log('Debug info:', data);
}
```

### React DevTools
- Install React DevTools extension
- Inspect component tree và props

### Network Tab
- Xem API requests/responses
- Check nonce và headers
