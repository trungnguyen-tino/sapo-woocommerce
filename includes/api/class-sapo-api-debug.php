<?php

if (!defined('ABSPATH')) {
    exit;
}

class Sapo_API_Debug {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    public function register_routes() {
        register_rest_route('sapo/v1', '/debug/test-product/(?P<product_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_product_sync'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/debug/test-variant/(?P<product_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_variant_sync'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/debug/test-images/(?P<product_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'test_image_sync'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('sapo/v1', '/debug/api-raw/(?P<product_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_raw_api_data'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    public function check_permission() {
        return current_user_can('manage_woocommerce');
    }
    
    public function test_product_sync($request) {
        try {
            $product_id = $request->get_param('product_id');
            
            $client = new Sapo_Client();
            $sapo_product = $client->products()->get($product_id);
            
            if (empty($sapo_product['product'])) {
                throw new Exception('Product not found in SAPO');
            }
            
            $product_data = $sapo_product['product'];
            
            $helper = new SapoWcProduct();
            
            $is_variable = !empty($product_data['variants']) && count($product_data['variants']) > 1;
            
            if ($is_variable) {
                $wc_product_id = $helper->create_variable_product($product_data);
            } else {
                $wc_product_id = $helper->create_simple_product($product_data);
            }
            
            $wc_product = wc_get_product($wc_product_id);
            
            $field_mapping = $this->analyze_field_mapping($product_data, $wc_product);
            
            return rest_ensure_response([
                'success' => true,
                'sapo_raw_data' => $product_data,
                'wc_product_id' => $wc_product_id,
                'wc_product_data' => [
                    'id' => $wc_product->get_id(),
                    'name' => $wc_product->get_name(),
                    'type' => $wc_product->get_type(),
                    'sku' => $wc_product->get_sku(),
                    'price' => $wc_product->get_price(),
                    'stock_quantity' => $wc_product->get_stock_quantity(),
                    'description' => $wc_product->get_description(),
                    'short_description' => $wc_product->get_short_description(),
                    'categories' => wp_get_post_terms($wc_product_id, 'product_cat', ['fields' => 'names']),
                    'attributes' => $this->get_product_attributes($wc_product),
                    'images' => $this->get_product_images($wc_product_id),
                ],
                'field_mapping' => $field_mapping,
                'unmapped_fields' => $this->find_unmapped_fields($product_data)
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('test_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function test_variant_sync($request) {
        try {
            $product_id = $request->get_param('product_id');
            
            $client = new Sapo_Client();
            $sapo_product = $client->products()->get($product_id);
            
            if (empty($sapo_product['product'])) {
                throw new Exception('Product not found in SAPO');
            }
            
            $product_data = $sapo_product['product'];
            
            if (empty($product_data['variants']) || count($product_data['variants']) <= 1) {
                throw new Exception('Product has no variants');
            }
            
            $helper = new SapoWcProduct();
            $wc_product_id = $helper->create_variable_product($product_data);
            
            $variations_data = [];
            foreach ($product_data['variants'] as $variant) {
                $variation_id = $helper->create_variation($wc_product_id, $variant, $product_data);
                $variation = wc_get_product($variation_id);
                
                $variations_data[] = [
                    'sapo_variant' => $variant,
                    'wc_variation_id' => $variation_id,
                    'wc_variation_data' => [
                        'id' => $variation->get_id(),
                        'sku' => $variation->get_sku(),
                        'price' => $variation->get_price(),
                        'stock' => $variation->get_stock_quantity(),
                        'attributes' => $variation->get_attributes(),
                        'image_id' => $variation->get_image_id(),
                    ]
                ];
            }
            
            return rest_ensure_response([
                'success' => true,
                'wc_product_id' => $wc_product_id,
                'total_variants' => count($variations_data),
                'variants' => $variations_data,
                'attribute_mapping' => $this->analyze_attribute_mapping($product_data)
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('test_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function test_image_sync($request) {
        try {
            $product_id = $request->get_param('product_id');
            
            $client = new Sapo_Client();
            $sapo_product = $client->products()->get($product_id);
            
            if (empty($sapo_product['product'])) {
                throw new Exception('Product not found in SAPO');
            }
            
            $product_data = $sapo_product['product'];
            $images = $product_data['images'] ?? [];
            
            $image_results = [];
            foreach ($images as $image) {
                $image_results[] = [
                    'sapo_image' => $image,
                    'src' => $image['src'],
                    'variant_ids' => $image['variant_ids'] ?? [],
                    'position' => $image['position'] ?? 0,
                    'is_product_image' => empty($image['variant_ids']) || count($image['variant_ids']) === 0,
                ];
            }
            
            return rest_ensure_response([
                'success' => true,
                'total_images' => count($images),
                'images' => $image_results,
                'product_images' => array_filter($image_results, fn($img) => $img['is_product_image']),
                'variant_images' => array_filter($image_results, fn($img) => !$img['is_product_image']),
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('test_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    public function get_raw_api_data($request) {
        try {
            $product_id = $request->get_param('product_id');
            
            $client = new Sapo_Client();
            $sapo_product = $client->products()->get($product_id);
            
            $collects = [];
            try {
                $collects_response = $client->collects()->get_by_product($product_id);
                $collects = $collects_response['collects'] ?? [];
            } catch (Exception $e) {
            }
            
            return rest_ensure_response([
                'success' => true,
                'product' => $sapo_product,
                'collects' => $collects,
                'api_structure' => $this->get_api_structure($sapo_product['product'] ?? [])
            ]);
            
        } catch (Exception $e) {
            return new WP_Error('test_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    private function analyze_field_mapping($sapo_data, $wc_product) {
        return [
            'name' => [
                'sapo' => $sapo_data['name'] ?? null,
                'wc' => $wc_product->get_name(),
                'mapped' => true
            ],
            'sku' => [
                'sapo' => $sapo_data['variants'][0]['sku'] ?? null,
                'wc' => $wc_product->get_sku(),
                'mapped' => !empty($wc_product->get_sku())
            ],
            'price' => [
                'sapo' => $sapo_data['variants'][0]['price'] ?? null,
                'wc' => $wc_product->get_price(),
                'mapped' => !empty($wc_product->get_price())
            ],
            'description' => [
                'sapo' => !empty($sapo_data['content']) ? 'content field' : (!empty($sapo_data['body_html']) ? 'body_html field' : null),
                'wc' => $wc_product->get_description() ? 'has description' : 'empty',
                'mapped' => !empty($wc_product->get_description())
            ],
            'inventory' => [
                'sapo' => $sapo_data['variants'][0]['inventory_quantity'] ?? null,
                'wc' => $wc_product->get_stock_quantity(),
                'mapped' => $wc_product->get_stock_quantity() !== null
            ]
        ];
    }
    
    private function analyze_attribute_mapping($sapo_data) {
        $analysis = [
            'sapo_options' => $sapo_data['options'] ?? [],
            'sapo_option_names' => [],
            'wc_attributes_created' => [],
            'mappings' => []
        ];
        
        foreach ($sapo_data['options'] ?? [] as $option) {
            $analysis['sapo_option_names'][] = [
                'position' => $option['position'] ?? 0,
                'name' => $option['name'] ?? '',
                'values' => $option['values'] ?? []
            ];
        }
        
        foreach (['option1', 'option2', 'option3'] as $option_key) {
            $mapping = Sapo_DB::get_attribute_mapping($option_key);
            if ($mapping) {
                $analysis['mappings'][$option_key] = [
                    'wc_attribute_name' => $mapping->wc_attribute_name,
                    'wc_attribute_slug' => $mapping->wc_attribute_slug,
                    'has_mapping' => true
                ];
            } else {
                $analysis['mappings'][$option_key] = [
                    'has_mapping' => false,
                    'will_use_default' => true
                ];
            }
        }
        
        return $analysis;
    }
    
    private function find_unmapped_fields($sapo_data) {
        $all_fields = array_keys($sapo_data);
        $mapped_fields = ['id', 'name', 'sku', 'price', 'content', 'body_html', 'variants', 'images', 'options', 'product_type'];
        
        $unmapped = array_diff($all_fields, $mapped_fields);
        
        $result = [];
        foreach ($unmapped as $field) {
            $result[] = [
                'field' => $field,
                'value' => $sapo_data[$field],
                'type' => gettype($sapo_data[$field])
            ];
        }
        
        return $result;
    }
    
    private function get_api_structure($data, $prefix = '') {
        $structure = [];
        
        foreach ($data as $key => $value) {
            $full_key = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                if (isset($value[0])) {
                    $structure[$full_key] = 'array[' . count($value) . ']';
                    if (!empty($value[0]) && is_array($value[0])) {
                        $structure = array_merge($structure, $this->get_api_structure($value[0], $full_key . '[0]'));
                    }
                } else {
                    $structure = array_merge($structure, $this->get_api_structure($value, $full_key));
                }
            } else {
                $structure[$full_key] = gettype($value) . ': ' . (is_string($value) ? substr($value, 0, 50) : $value);
            }
        }
        
        return $structure;
    }
    
    private function get_product_attributes($product) {
        $attributes = [];
        foreach ($product->get_attributes() as $attribute) {
            $attributes[] = [
                'name' => $attribute->get_name(),
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
            ];
        }
        return $attributes;
    }
    
    private function get_product_images($product_id) {
        $product = wc_get_product($product_id);
        $images = [];
        
        if ($product->get_image_id()) {
            $images['thumbnail'] = wp_get_attachment_url($product->get_image_id());
        }
        
        $gallery_ids = $product->get_gallery_image_ids();
        if (!empty($gallery_ids)) {
            $images['gallery'] = array_map('wp_get_attachment_url', $gallery_ids);
        }
        
        return $images;
    }
}
