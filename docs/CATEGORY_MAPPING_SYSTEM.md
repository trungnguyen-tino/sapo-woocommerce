# Hệ Thống Mapping Categories

## Khái Niệm

**SAPO Collections** ≠ **WooCommerce Categories**

- **SAPO:** Dùng **Collections** để nhóm sản phẩm (1 product có thể thuộc nhiều collections)
- **WooCommerce:** Dùng **Product Categories** để phân loại

Hệ thống mapping giúp tự động gán WC categories khi sync products từ SAPO.

## Cách Hoạt Động

### 1. SAPO API Structure

**Collections:**
```json
{
  "collections": [
    {
      "id": 841564295,
      "name": "Áo Nam",
      "description": "..."
    }
  ]
}
```

**Collects (liên kết product ↔ collection):**
```json
{
  "collects": [
    {
      "id": 395646240,
      "collection_id": 841564295,
      "product_id": 632910392,
      "position": 1
    }
  ]
}
```

### 2. Database Table

**wp_sapo_category_mappings:**
```sql
CREATE TABLE wp_sapo_category_mappings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    sapo_collection_id BIGINT(20) NOT NULL,     -- SAPO Collection ID
    sapo_collection_name VARCHAR(255),          -- Tên collection
    wc_category_id BIGINT(20) NOT NULL,         -- WC Category ID
    wc_category_name VARCHAR(255),              -- Tên category
    auto_create TINYINT(1) DEFAULT 0,           -- Tự động tạo nếu chưa map
    enabled TINYINT(1) DEFAULT 1,
    UNIQUE KEY unique_collection (sapo_collection_id)
);
```

### 3. Sync Flow

```
1. Sync Product từ SAPO
   ↓
2. Lấy Collects theo product_id
   GET /admin/collects.json?product_id=632910392
   ↓
3. Với mỗi collection_id trong collects:
   ├─ Check mapping trong database
   ├─ Nếu có mapping → Gán WC category
   ├─ Nếu không có mapping + auto_create = true:
   │  ├─ Lấy thông tin collection từ SAPO
   │  ├─ Tạo WC category mới
   │  └─ Lưu mapping vào database
   └─ Nếu không có mapping + auto_create = false → Skip
   ↓
4. Set categories cho WC product
   wp_set_object_terms($product_id, $category_ids, 'product_cat')
```

## Ví Dụ

### Scenario 1: Có Mapping

**Admin đã config:**
| SAPO Collection | WC Category | Auto Create |
|----------------|-------------|-------------|
| Áo Nam (12345) | Nam (67)    | ✗           |
| Áo Thể Thao (67890) | Thể Thao (89) | ✗ |

**SAPO Product:**
```json
{
  "id": 100,
  "name": "Áo Polo Nam"
}
```

**SAPO Collects:**
```json
{
  "collects": [
    {"collection_id": 12345},  // Áo Nam
    {"collection_id": 67890}   // Áo Thể Thao
  ]
}
```

**WooCommerce Result:**
- Product "Áo Polo Nam" được gán vào 2 categories:
  - Nam (ID: 67)
  - Thể Thao (ID: 89)

### Scenario 2: Auto Create

**Admin config:**
| SAPO Collection | WC Category | Auto Create |
|----------------|-------------|-------------|
| (chưa map)     | -           | ✓ Enabled   |

**SAPO Product thuộc Collection:**
```json
{
  "collection": {
    "id": 99999,
    "name": "Hàng Mới Về"
  }
}
```

**Kết quả:**
1. Plugin lấy collection info từ SAPO
2. Tạo WC category mới: "Hàng Mới Về"
3. Lưu mapping: 99999 → Category ID mới
4. Gán product vào category

### Scenario 3: Không Mapping + Không Auto

**Admin config:** Không có mapping cho collection 88888

**SAPO Product thuộc Collection 88888**

**Kết quả:** Product không được gán category nào

## Admin UI

### Tab 📂 Categories

**Features:**
1. **Danh sách Mappings:**
   - Hiển thị tất cả mappings hiện có
   - Xóa mapping

2. **Form Thêm Mapping:**
   - Dropdown SAPO Collections (load từ API)
   - Dropdown WC Categories (load từ database)
   - Checkbox Auto Create
   - Button Lưu

3. **Refresh:**
   - Load lại collections từ SAPO
   - Load lại categories từ WC
   - Load lại mappings

## API Endpoints

### GET /wp-json/sapo/v1/categories/mappings
Lấy tất cả mappings

**Response:**
```json
{
  "success": true,
  "mappings": [
    {
      "id": 1,
      "sapo_collection_id": 12345,
      "sapo_collection_name": "Áo Nam",
      "wc_category_id": 67,
      "wc_category_name": "Nam",
      "auto_create": 0
    }
  ]
}
```

### POST /wp-json/sapo/v1/categories/mappings
Tạo/cập nhật mapping

**Request:**
```json
{
  "sapo_collection_id": 12345,
  "sapo_collection_name": "Áo Nam",
  "wc_category_id": 67,
  "wc_category_name": "Nam",
  "auto_create": false
}
```

### DELETE /wp-json/sapo/v1/categories/mappings/{collection_id}
Xóa mapping

### GET /wp-json/sapo/v1/categories/sapo-collections
Lấy tất cả collections từ SAPO

**Response:**
```json
{
  "success": true,
  "collections": [
    {
      "id": 12345,
      "name": "Áo Nam",
      "description": "..."
    }
  ]
}
```

### GET /wp-json/sapo/v1/categories/wc-categories
Lấy tất cả WC categories

**Response:**
```json
{
  "success": true,
  "categories": [
    {
      "id": 67,
      "name": "Nam",
      "slug": "nam",
      "count": 45
    }
  ]
}
```

## Code Reference

### SDK Resources
- `/includes/sdk/resources/class-sapo-collect-resource.php`
- `/includes/sdk/resources/class-sapo-collection-resource.php`

### Database
- `/includes/database/class-sapo-db.php`
  - `get_category_mapping($collection_id)`
  - `save_category_mapping(...)`
  - `delete_category_mapping($collection_id)`

### Product Helper
- `/helpers/SapoWcProduct.php`
  - `set_product_categories($product_id, $sapo_product_id)`

### API Controller
- `/includes/api/class-sapo-api-categories.php`

### Admin Component
- `/admin/assets/js/components/CategoryMappingView.jsx`

## Lưu Ý Quan Trọng

### 1. Multiple Categories
- 1 SAPO product có thể thuộc nhiều collections
- WC product sẽ được gán vào tất cả mapped categories
- Không giới hạn số lượng categories

### 2. Auto Create Behavior
- **Enabled:** Tạo category mới nếu chưa mapping
- **Disabled:** Bỏ qua collection chưa mapping
- Auto create chỉ chạy 1 lần, sau đó lưu mapping

### 3. Sync Performance
- Mỗi product cần 1-2 API calls để lấy collects
- Collections info được cache trong mappings table
- Nên mapping trước khi sync hàng loạt

### 4. Hierarchical Categories
- WC categories có thể có parent/child
- SAPO collections không có hierarchy
- Mapping là flat (1-1), không preserve hierarchy

## Best Practices

### 1. Setup Trước Khi Sync
```
1. Vào tab 📂 Categories
2. Click "Làm mới" để load SAPO collections
3. Tạo mappings cho các collections chính
4. Enable auto_create cho collections phụ
5. Sau đó mới sync products
```

### 2. Quản Lý Mappings
- Nên mapping các collections hay dùng
- Auto create cho collections ít sản phẩm
- Review định kỳ các categories được tạo

### 3. Naming Convention
- WC category names nên giữ tiếng Việt
- Slugs tự động sanitize
- Nhất quán với SAPO collection names

## Troubleshooting

### Categories không được gán

**Kiểm tra:**
1. Product có thuộc collection nào không?
   ```php
   $collects = $client->collects()->get_by_product($product_id);
   ```

2. Collection đã được mapping chưa?
   ```sql
   SELECT * FROM wp_sapo_category_mappings 
   WHERE sapo_collection_id = 12345;
   ```

3. Auto create có enabled không?

### Duplicate Categories

**Nguyên nhân:** Auto create tạo category trùng tên

**Giải pháp:**
1. Xóa category duplicate
2. Tạo mapping cho collection
3. Re-sync products

### API Rate Limit

**Nguyên nhân:** Quá nhiều API calls khi sync hàng loạt

**Giải pháp:**
- Mapping trước để giảm API calls
- Sync nhỏ batch, không sync tất cả cùng lúc
- Monitor rate limit trong logs

## Migration

### Import Existing Mappings

Nếu đã có sản phẩm với categories:

```sql
-- Tìm mapping tự nhiên
SELECT 
    p.sapo_product_id,
    c.sapo_collection_id,
    t.term_id as wc_category_id
FROM wp_sapo_sync_products p
JOIN wp_sapo_collects c ON p.sapo_product_id = c.product_id
JOIN wp_term_relationships r ON p.wc_product_id = r.object_id
JOIN wp_term_taxonomy t ON r.term_taxonomy_id = t.term_taxonomy_id
WHERE t.taxonomy = 'product_cat'
GROUP BY c.sapo_collection_id, t.term_id;
```

## Testing

### Test Case 1: Single Collection

**Setup:**
- Mapping: SAPO Collection 123 → WC Category "Test"

**Product:**
```json
{"id": 100, "collects": [{"collection_id": 123}]}
```

**Expected:** Product vào category "Test"

### Test Case 2: Multiple Collections

**Setup:**
- Mapping: 123 → "Nam"
- Mapping: 456 → "Thể Thao"

**Product:**
```json
{"id": 200, "collects": [{"collection_id": 123}, {"collection_id": 456}]}
```

**Expected:** Product vào cả "Nam" và "Thể Thao"

### Test Case 3: Auto Create

**Setup:**
- No mapping for collection 789
- Auto create enabled

**Product:**
```json
{
  "id": 300,
  "collects": [{"collection_id": 789}]
}
```

**Expected:**
1. Tạo category mới từ SAPO collection name
2. Lưu mapping
3. Gán product vào category mới
