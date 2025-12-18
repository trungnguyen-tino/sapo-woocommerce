# Hướng Dẫn Đồng Bộ Ảnh Từ SAPO

## Cấu trúc Images trong SAPO API

Theo API SAPO, mỗi product có mảng `images` với cấu trúc:

```json
{
  "images": [
    {
      "id": 850703190,
      "product_id": 632910392,
      "position": 1,
      "src": "https://bizweb.dktcdn.net/.../products/ipod-nano.jpg",
      "variant_ids": []
    },
    {
      "id": 562641783,
      "product_id": 632910392,
      "position": 2,
      "src": "https://bizweb.dktcdn.net/.../products/ipod-nano-808950810.jpg",
      "variant_ids": [108950810]
    }
  ]
}
```

## Phân loại ảnh

### 1. Ảnh Product (variant_ids rỗng)
- `variant_ids: []` hoặc không có variant_ids
- Là ảnh chung của sản phẩm
- Được sử dụng làm thumbnail và gallery của product chính

### 2. Ảnh Variant (có variant_ids)
- `variant_ids: [108950810, ...]`
- Là ảnh riêng cho các variant cụ thể
- Được thêm vào gallery của product
- Được set làm thumbnail cho variant tương ứng

## Logic Đồng Bộ

### Simple Product
1. Lấy ảnh đầu tiên (position thấp nhất) làm **Featured Image**
2. Các ảnh còn lại vào **Product Gallery**

### Variable Product

#### Product chính:
1. Phân loại ảnh:
   - `variant_ids = []` → Ảnh của product
   - `variant_ids ≠ []` → Ảnh của variant
2. Ảnh product đầu tiên (theo position) → **Featured Image**
3. Các ảnh product còn lại + tất cả ảnh variant → **Product Gallery**

#### Mỗi Variation:
1. Kiểm tra `variant['image_id']`
2. Tìm ảnh tương ứng trong mảng images
3. Set làm **Featured Image** của variation

## Ví dụ

### Dữ liệu từ SAPO:
```json
{
  "id": 632910392,
  "name": "iPod Nano - 8GB",
  "variants": [
    {
      "id": 108950810,
      "title": "Pink",
      "image_id": 562641783
    },
    {
      "id": 99148385,
      "title": "Red",
      "image_id": null
    }
  ],
  "images": [
    {
      "id": 850703190,
      "position": 1,
      "src": "https://.../ipod-nano.jpg",
      "variant_ids": []
    },
    {
      "id": 562641783,
      "position": 2,
      "src": "https://.../ipod-nano-pink.jpg",
      "variant_ids": [108950810]
    }
  ]
}
```

### Kết quả trong WooCommerce:

**Product chính (Variable Product):**
- Featured Image: `ipod-nano.jpg` (position 1, variant_ids rỗng)
- Gallery: `ipod-nano-pink.jpg`

**Variation "Pink":**
- Featured Image: `ipod-nano-pink.jpg` (từ image_id 562641783)

**Variation "Red":**
- Không có Featured Image riêng (dùng ảnh của product chính)

## Cấu hình

Để bật đồng bộ ảnh, vào **SAPO Sync → Settings**:

1. ✅ Bật "Cập nhật hình ảnh" trong phần Tùy chọn đồng bộ
2. Đảm bảo server cho phép `allow_url_fopen` hoặc có cURL

## Lưu ý

- Ảnh được download và lưu vào WordPress Media Library
- Mỗi ảnh chỉ được download 1 lần (WordPress kiểm tra duplicate)
- Nếu download thất bại, sản phẩm vẫn được tạo nhưng không có ảnh
- Ảnh variant có thể được dùng chung cho nhiều variant (qua variant_ids)

## Troubleshooting

### Ảnh không được tải xuống
- Kiểm tra `allow_url_fopen` trong php.ini
- Kiểm tra cURL extension
- Kiểm tra quyền ghi vào thư mục `wp-content/uploads`

### Ảnh bị lỗi 404
- Kiểm tra URL từ SAPO API
- Kiểm tra kết nối internet của server

### Variation không có ảnh
- Kiểm tra variant có `image_id` không
- Kiểm tra `image_id` có tồn tại trong mảng `images` không
