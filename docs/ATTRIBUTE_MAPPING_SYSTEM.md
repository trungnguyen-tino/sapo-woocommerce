# Hệ Thống Mapping Product Attributes

## Vấn Đề

SAPO API **chỉ trả về** các options cố định:
- `option1`, `option2`, `option3`

WooCommerce cho phép **tùy chỉnh** tên attributes:
- `pa_mau-sac`, `pa_kich-thuoc`, `pa_chat-lieu`, ...

**Cần một hệ thống mapping** để linh hoạt ánh xạ giữa SAPO options và WC attributes.

## Giải Pháp

### 1. Database Table

Bảng `wp_sapo_attribute_mappings`:

```sql
CREATE TABLE wp_sapo_attribute_mappings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    sapo_option VARCHAR(20) NOT NULL,        -- option1, option2, option3
    wc_attribute_name VARCHAR(255) NOT NULL, -- Màu sắc, Kích thước
    wc_attribute_slug VARCHAR(255) NOT NULL, -- mau-sac, kich-thuoc
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY unique_option (sapo_option)
);
```

### 2. Admin UI

Tab mới trong admin: **🏷️ Attributes**

Cho phép admin config:

| SAPO Option | WC Attribute Name | WC Attribute Slug | Actions |
|-------------|-------------------|-------------------|---------|
| option1     | Màu sắc          | mau-sac           | ✎ Sửa / 🗑 |
| option2     | Kích thước       | kich-thuoc        | ✎ Sửa / 🗑 |
| option3     | -                | -                 | + Thêm  |

### 3. Logic Flow

#### Khi Tạo Product

```php
// 1. Đọc mapping từ database
$mapping = Sapo_DB::get_attribute_mapping('option1');

// 2. Nếu có mapping
if ($mapping) {
    $attribute_name = $mapping->wc_attribute_name; // "Màu sắc"
    $attribute_slug = $mapping->wc_attribute_slug; // "mau-sac"
}
// 3. Nếu không có mapping, fallback
else {
    // Lấy từ SAPO options nếu có
    $attribute_name = $sapo_product['options'][0]['name']; // "Color"
    $attribute_slug = sanitize_title($attribute_name);     // "color"
}

// 4. Tạo WC attribute
$taxonomy = 'pa_' . $attribute_slug; // "pa_mau-sac"
```

#### Khi Tạo Variation

```php
foreach (['option1', 'option2', 'option3'] as $option_key) {
    // Lấy mapping
    $mapping = Sapo_DB::get_attribute_mapping($option_key);
    
    if ($mapping) {
        $slug = $mapping->wc_attribute_slug;
    } else {
        // Fallback
        $slug = $option_key;
    }
    
    // Set variation attributes
    $attributes['attribute_pa_' . $slug] = $variant[$option_key];
}
```

## Ví Dụ Cụ Thể

### Scenario 1: Có Mapping

**Admin Config:**
- option1 → Màu sắc (mau-sac)
- option2 → Kích thước (kich-thuoc)

**SAPO Response:**
```json
{
  "variants": [
    {"option1": "Đỏ", "option2": "M", "option3": null}
  ]
}
```

**WooCommerce Tạo:**
- Variable Product với 2 attributes:
  - `pa_mau-sac`: Đỏ
  - `pa_kich-thuoc`: M

### Scenario 2: Không Có Mapping

**Admin Config:** (không config gì)

**SAPO Response:**
```json
{
  "options": [
    {"position": 1, "name": "Color"},
    {"position": 2, "name": "Size"}
  ],
  "variants": [
    {"option1": "Red", "option2": "M"}
  ]
}
```

**WooCommerce Tạo:**
- Fallback sang SAPO options:
  - `pa_color`: Red
  - `pa_size`: M

### Scenario 3: Mix

**Admin Config:**
- option1 → Màu sắc (mau-sac)
- option2 → (không config)

**SAPO Response:**
```json
{
  "options": [
    {"position": 1, "name": "Color"},
    {"position": 2, "name": "Kích thước"}
  ],
  "variants": [
    {"option1": "Đỏ", "option2": "L"}
  ]
}
```

**WooCommerce Tạo:**
- option1 dùng mapping: `pa_mau-sac`: Đỏ
- option2 fallback SAPO: `pa_kich-thuoc`: L

## API Endpoints

### GET /wp-json/sapo/v1/attributes/mappings
Lấy tất cả mappings

**Response:**
```json
{
  "success": true,
  "mappings": [
    {
      "id": 1,
      "sapo_option": "option1",
      "wc_attribute_name": "Màu sắc",
      "wc_attribute_slug": "mau-sac",
      "enabled": 1
    }
  ]
}
```

### POST /wp-json/sapo/v1/attributes/mappings
Tạo/cập nhật mapping

**Request:**
```json
{
  "sapo_option": "option1",
  "wc_attribute_name": "Màu sắc",
  "wc_attribute_slug": "mau-sac"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Mapping saved successfully"
}
```

### DELETE /wp-json/sapo/v1/attributes/mappings/{option}
Xóa mapping

**Response:**
```json
{
  "success": true,
  "message": "Mapping deleted successfully"
}
```

## Code Reference

### Database Functions
`/includes/database/class-sapo-db.php`
- `get_attribute_mapping($sapo_option)`
- `get_all_attribute_mappings()`
- `save_attribute_mapping($sapo_option, $name, $slug)`
- `delete_attribute_mapping($sapo_option)`

### Product Helper
`/helpers/SapoWcProduct.php`
- `create_attributes_from_variants()` - Dòng 184
- `create_variation()` - Dòng 135

### Admin UI
`/admin/assets/js/components/AttributeMappingView.jsx`

### API Controller
`/includes/api/class-sapo-api-attributes.php`

## Best Practices

### 1. Quy Tắc Đặt Slug
- Lowercase: `mau-sac` ✅, `Mau-Sac` ❌
- Dấu gạch ngang: `kich-thuoc` ✅, `kich_thuoc` ❌
- Không dấu: `mau-sac` ✅, `màu-sắc` ❌
- Chỉ chữ cái số: `size-2xl` ✅, `size@2xl` ❌

### 2. Consistency
Một khi đã mapping, **nên giữ nguyên**:
- Đổi slug sẽ tạo attribute mới
- Sản phẩm cũ không tự động update

### 3. Testing
Trước khi sync hàng loạt:
1. Tạo mapping
2. Test với 1 sản phẩm
3. Kiểm tra attributes trong WC
4. Sau đó mới sync nhiều

### 4. Backup
Database backup trước khi:
- Thay đổi mapping
- Xóa mapping đang dùng
- Sync hàng loạt sản phẩm

## Troubleshooting

### Attributes bị trùng
**Nguyên nhân:** Thay đổi slug sau khi đã tạo sản phẩm

**Giải pháp:**
1. Xóa sản phẩm test cũ
2. Flush rewrite rules: `wp rewrite flush`
3. Sync lại

### Variations không match
**Nguyên nhân:** Slug không khớp giữa product và variations

**Kiểm tra:**
```sql
SELECT * FROM wp_sapo_attribute_mappings;
```

**Đảm bảo:** Cùng slug cho tất cả products/variations

### Fallback không hoạt động
**Nguyên nhân:** SAPO không trả `options` array

**Giải pháp:** Sẽ dùng `option1`, `option2`, `option3` mặc định

## Migration

### Từ Auto-Generate Sang Mapping

Nếu đã có sản phẩm được tạo với auto-generate:

1. **Xác định attributes đang dùng:**
```sql
SELECT DISTINCT wc_attribute_slug 
FROM wp_sapo_sync_products 
JOIN wp_term_taxonomy ON ...;
```

2. **Tạo mappings tương ứng:**
```php
Sapo_DB::save_attribute_mapping('option1', 'Màu sắc', 'mau-sac');
Sapo_DB::save_attribute_mapping('option2', 'Size', 'size');
```

3. **Sản phẩm mới sẽ dùng mapping**
4. **Sản phẩm cũ giữ nguyên**

## Performance

- Mappings được cache trong query
- Chỉ 3 rows tối đa (option1, 2, 3)
- Indexed UNIQUE trên `sapo_option`
- Fast lookup O(1)

## Security

- ✅ Permission check: `manage_woocommerce`
- ✅ Nonce verification
- ✅ Sanitization: `sanitize_text_field()`, `sanitize_title()`
- ✅ Validation: Chỉ accept option1/2/3
- ✅ SQL Injection prevention: `$wpdb->prepare()`
