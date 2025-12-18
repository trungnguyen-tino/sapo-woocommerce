<?php

if (!defined('ABSPATH')) {
    exit;
}

class SapoWcProduct {
    
    public function create_simple_product($sapo_product) {
        $product = new WC_Product_Simple();
        
        $product->set_name($sapo_product['name']);
        
        if (!empty($sapo_product['body_html'])) {
            $product->set_description(sapo_sanitize_html($sapo_product['body_html']));
        }
        
        if (isset($sapo_product['variants'][0])) {
            $variant = $sapo_product['variants'][0];
            
            if (isset($variant['price'])) {
                $product->set_regular_price($variant['price']);
            }
            
            if (isset($variant['sku'])) {
                $product->set_sku($variant['sku']);
            }
            
            if (isset($variant['inventory_quantity'])) {
                $product->set_stock_quantity($variant['inventory_quantity']);
                $product->set_manage_stock(true);
                
                if ($variant['inventory_quantity'] > 0) {
                    $product->set_stock_status('instock');
                } else {
                    $product->set_stock_status('outofstock');
                }
            }
            
            if (isset($variant['weight'])) {
                $product->set_weight($variant['weight']);
            }
            
            if (isset($variant['barcode'])) {
                $product->update_meta_data('_barcode', $variant['barcode']);
            }
        }
        
        $product->set_status('publish');
        
        $product_id = $product->save();
        
        if (!empty($sapo_product['images'])) {
            $this->set_product_images($product_id, $sapo_product['images']);
        }
        
        if (!empty($sapo_product['product_type'])) {
            $this->set_product_category($product_id, $sapo_product['product_type']);
        }
        
        return $product_id;
    }
    
    public function create_variable_product($sapo_product) {
        $product = new WC_Product_Variable();
        
        $product->set_name($sapo_product['name']);
        
        if (!empty($sapo_product['body_html'])) {
            $product->set_description(sapo_sanitize_html($sapo_product['body_html']));
        }
        
        $product->set_status('publish');
        
        $product_id = $product->save();
        
        if (!empty($sapo_product['images'])) {
            $this->set_product_images($product_id, $sapo_product['images']);
        }
        
        if (!empty($sapo_product['product_type'])) {
            $this->set_product_category($product_id, $sapo_product['product_type']);
        }
        
        $this->create_attributes_from_variants($product_id, $sapo_product['variants']);
        
        return $product_id;
    }
    
    public function create_variation($product_id, $variant) {
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        
        if (isset($variant['price'])) {
            $variation->set_regular_price($variant['price']);
        }
        
        if (isset($variant['sku'])) {
            $variation->set_sku($variant['sku']);
        }
        
        if (isset($variant['inventory_quantity'])) {
            $variation->set_stock_quantity($variant['inventory_quantity']);
            $variation->set_manage_stock(true);
            
            if ($variant['inventory_quantity'] > 0) {
                $variation->set_stock_status('instock');
            } else {
                $variation->set_stock_status('outofstock');
            }
        }
        
        if (isset($variant['weight'])) {
            $variation->set_weight($variant['weight']);
        }
        
        if (isset($variant['barcode'])) {
            $variation->update_meta_data('_barcode', $variant['barcode']);
        }
        
        $attributes = [];
        
        if (!empty($variant['option1'])) {
            $attributes['attribute_pa_option1'] = sanitize_title($variant['option1']);
        }
        
        if (!empty($variant['option2'])) {
            $attributes['attribute_pa_option2'] = sanitize_title($variant['option2']);
        }
        
        if (!empty($variant['option3'])) {
            $attributes['attribute_pa_option3'] = sanitize_title($variant['option3']);
        }
        
        $variation->set_attributes($attributes);
        $variation->set_status('publish');
        
        $variation_id = $variation->save();
        
        if (!empty($variant['image_id']) && !empty($sapo_product['images'])) {
            foreach ($sapo_product['images'] as $image) {
                if ($image['id'] == $variant['image_id']) {
                    $attachment_id = sapo_download_image($image['src']);
                    if ($attachment_id) {
                        set_post_thumbnail($variation_id, $attachment_id);
                    }
                    break;
                }
            }
        }
        
        return $variation_id;
    }
    
    private function create_attributes_from_variants($product_id, $variants) {
        $options = [
            'option1' => [],
            'option2' => [],
            'option3' => []
        ];
        
        foreach ($variants as $variant) {
            if (!empty($variant['option1'])) {
                $options['option1'][] = $variant['option1'];
            }
            if (!empty($variant['option2'])) {
                $options['option2'][] = $variant['option2'];
            }
            if (!empty($variant['option3'])) {
                $options['option3'][] = $variant['option3'];
            }
        }
        
        $attributes = [];
        $position = 0;
        
        foreach ($options as $option_key => $values) {
            if (empty($values)) {
                continue;
            }
            
            $values = array_unique($values);
            
            $attribute = new WC_Product_Attribute();
            $attribute->set_name('pa_' . $option_key);
            $attribute->set_options($values);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $attribute->set_position($position++);
            
            $attributes[] = $attribute;
            
            $this->register_attribute_taxonomy('pa_' . $option_key, ucfirst($option_key));
            
            foreach ($values as $value) {
                wp_set_object_terms($product_id, $value, 'pa_' . $option_key, true);
            }
        }
        
        $product = wc_get_product($product_id);
        $product->set_attributes($attributes);
        $product->save();
    }
    
    private function register_attribute_taxonomy($taxonomy, $label) {
        if (!taxonomy_exists($taxonomy)) {
            $args = [
                'labels' => [
                    'name' => $label
                ],
                'hierarchical' => false,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => ['slug' => $taxonomy],
            ];
            
            register_taxonomy($taxonomy, 'product', $args);
        }
    }
    
    public function update_product($product_id, $sapo_product, $options = []) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        if ($options['update_price'] ?? false) {
            $this->update_price($product_id, $sapo_product);
        }
        
        if ($options['update_stock'] ?? false) {
            $this->update_stock($product_id, $sapo_product);
        }
        
        if ($options['update_images'] ?? false) {
            if (!empty($sapo_product['images'])) {
                $this->set_product_images($product_id, $sapo_product['images']);
            }
        }
        
        return true;
    }
    
    public function update_stock($product_id, $sapo_product) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        if ($product->is_type('variable')) {
            if (!empty($sapo_product['variants'])) {
                foreach ($sapo_product['variants'] as $variant) {
                    $mapping = Sapo_DB::get_product_mapping($sapo_product['id'], $variant['id']);
                    
                    if ($mapping) {
                        $variation = wc_get_product($mapping->wc_product_id);
                        
                        if ($variation && isset($variant['inventory_quantity'])) {
                            $variation->set_stock_quantity($variant['inventory_quantity']);
                            $variation->set_manage_stock(true);
                            
                            if ($variant['inventory_quantity'] > 0) {
                                $variation->set_stock_status('instock');
                            } else {
                                $variation->set_stock_status('outofstock');
                            }
                            
                            $variation->save();
                        }
                    }
                }
            }
        } else {
            if (!empty($sapo_product['variants'][0])) {
                $variant = $sapo_product['variants'][0];
                
                if (isset($variant['inventory_quantity'])) {
                    $product->set_stock_quantity($variant['inventory_quantity']);
                    $product->set_manage_stock(true);
                    
                    if ($variant['inventory_quantity'] > 0) {
                        $product->set_stock_status('instock');
                    } else {
                        $product->set_stock_status('outofstock');
                    }
                    
                    $product->save();
                }
            }
        }
        
        return true;
    }
    
    public function update_price($product_id, $sapo_product) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return false;
        }
        
        if ($product->is_type('variable')) {
            if (!empty($sapo_product['variants'])) {
                foreach ($sapo_product['variants'] as $variant) {
                    $mapping = Sapo_DB::get_product_mapping($sapo_product['id'], $variant['id']);
                    
                    if ($mapping) {
                        $variation = wc_get_product($mapping->wc_product_id);
                        
                        if ($variation && isset($variant['price'])) {
                            $variation->set_regular_price($variant['price']);
                            $variation->save();
                        }
                    }
                }
            }
        } else {
            if (!empty($sapo_product['variants'][0])) {
                $variant = $sapo_product['variants'][0];
                
                if (isset($variant['price'])) {
                    $product->set_regular_price($variant['price']);
                    $product->save();
                }
            }
        }
        
        return true;
    }
    
    private function set_product_images($product_id, $images) {
        if (empty($images)) {
            return;
        }
        
        $gallery_ids = [];
        
        foreach ($images as $index => $image) {
            if (empty($image['src'])) {
                continue;
            }
            
            $attachment_id = sapo_download_image($image['src']);
            
            if ($attachment_id) {
                if ($index === 0) {
                    set_post_thumbnail($product_id, $attachment_id);
                } else {
                    $gallery_ids[] = $attachment_id;
                }
            }
        }
        
        if (!empty($gallery_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        }
    }
    
    private function set_product_category($product_id, $category_name) {
        $helper = new SapoWcCategory();
        $category_id = $helper->get_or_create_category($category_name);
        
        if ($category_id) {
            wp_set_object_terms($product_id, $category_id, 'product_cat');
        }
    }
}
