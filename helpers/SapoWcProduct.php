<?php

if (!defined('ABSPATH')) {
    exit;
}

class SapoWcProduct {
    
    public function create_simple_product($sapo_product) {
        if (empty($sapo_product['name'])) {
            throw new Exception('Product name is required');
        }
        
        $product = new WC_Product_Simple();
        
        $product->set_name(sanitize_text_field($sapo_product['name']));
        
        if (!empty($sapo_product['content'])) {
            $product->set_description(sapo_sanitize_html($sapo_product['content']));
        } elseif (!empty($sapo_product['body_html'])) {
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
        if (empty($sapo_product['name'])) {
            throw new Exception('Product name is required');
        }
        
        $product = new WC_Product_Variable();
        
        $product->set_name(sanitize_text_field($sapo_product['name']));
        
        if (!empty($sapo_product['content'])) {
            $product->set_description(sapo_sanitize_html($sapo_product['content']));
        } elseif (!empty($sapo_product['body_html'])) {
            $product->set_description(sapo_sanitize_html($sapo_product['body_html']));
        }
        
        $product->set_status('publish');
        
        $product_id = $product->save();
        
        if (!empty($sapo_product['images'])) {
            $this->set_product_images($product_id, $sapo_product['images']);
        }
        
        if (!empty($sapo_product['id'])) {
            $this->set_product_categories($product_id, $sapo_product['id']);
        }
        
        $this->create_attributes_from_variants($product_id, $sapo_product);
        
        return $product_id;
    }
    
    public function create_variation($product_id, $variant, $sapo_product = null) {
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
        
        foreach (['option1', 'option2', 'option3'] as $option_key) {
            if (empty($variant[$option_key])) {
                continue;
            }
            
            $mapping = Sapo_DB::get_attribute_mapping($option_key);
            
            if ($mapping && !empty($mapping->wc_attribute_slug)) {
                $attribute_slug = $mapping->wc_attribute_slug;
            } else {
                if (!empty($sapo_product['options'])) {
                    $position = intval(str_replace('option', '', $option_key));
                    foreach ($sapo_product['options'] as $option) {
                        if (($option['position'] ?? 0) == $position) {
                            $attribute_name = $option['name'] ?? ucfirst($option_key);
                            $attribute_slug = $this->sanitize_attribute_slug($attribute_name);
                            break;
                        }
                    }
                    if (!isset($attribute_slug)) {
                        $attribute_slug = $option_key;
                    }
                } else {
                    $attribute_slug = $option_key;
                }
            }
            
            $attributes['attribute_pa_' . $attribute_slug] = sanitize_title($variant[$option_key]);
        }
        
        $variation->set_attributes($attributes);
        $variation->set_status('publish');
        
        $variation_id = $variation->save();
        
        if (!empty($variant['image_id']) && !empty($sapo_product['images'])) {
            $variant_image = $this->find_variant_image($sapo_product['images'], $variant['image_id']);
            
            if ($variant_image && !empty($variant_image['src'])) {
                $attachment_id = sapo_download_image($variant_image['src']);
                if ($attachment_id) {
                    set_post_thumbnail($variation_id, $attachment_id);
                }
            }
        }
        
        return $variation_id;
    }
    
    private function create_attributes_from_variants($product_id, $sapo_product) {
        $variants = $sapo_product['variants'] ?? [];
        
        $options_data = [
            'option1' => [],
            'option2' => [],
            'option3' => []
        ];
        
        foreach ($variants as $variant) {
            if (!empty($variant['option1'])) {
                $options_data['option1'][] = $variant['option1'];
            }
            if (!empty($variant['option2'])) {
                $options_data['option2'][] = $variant['option2'];
            }
            if (!empty($variant['option3'])) {
                $options_data['option3'][] = $variant['option3'];
            }
        }
        
        $attributes = [];
        $position = 0;
        
        foreach ($options_data as $option_key => $values) {
            if (empty($values)) {
                continue;
            }
            
            $values = array_unique($values);
            
            $mapping = Sapo_DB::get_attribute_mapping($option_key);
            
            if ($mapping && !empty($mapping->wc_attribute_slug)) {
                $attribute_name = $mapping->wc_attribute_name;
                $attribute_slug = $mapping->wc_attribute_slug;
            } else {
                $sapo_options = $sapo_product['options'] ?? [];
                $attribute_name = $this->get_option_name_from_sapo($sapo_options, $option_key);
                $attribute_slug = $this->sanitize_attribute_slug($attribute_name);
            }
            
            $taxonomy = 'pa_' . $attribute_slug;
            
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($taxonomy);
            $attribute->set_options($values);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $attribute->set_position($position++);
            
            $attributes[] = $attribute;
            
            $this->register_attribute_taxonomy($taxonomy, $attribute_name);
            
            foreach ($values as $value) {
                wp_set_object_terms($product_id, $value, $taxonomy, true);
            }
        }
        
        $product = wc_get_product($product_id);
        $product->set_attributes($attributes);
        $product->save();
    }
    
    private function get_option_name_from_sapo($sapo_options, $option_key) {
        $position = intval(str_replace('option', '', $option_key));
        
        foreach ($sapo_options as $option) {
            if (($option['position'] ?? 0) == $position) {
                return $option['name'] ?? ucfirst($option_key);
            }
        }
        
        return ucfirst($option_key);
    }
    
    private function sanitize_attribute_slug($name) {
        $slug = sanitize_title($name);
        $slug = remove_accents($slug);
        $slug = str_replace(' ', '-', $slug);
        $slug = preg_replace('/[^a-z0-9-_]/', '', strtolower($slug));
        
        return $slug;
    }
    
    private function register_attribute_taxonomy($taxonomy, $label) {
        if (!taxonomy_exists($taxonomy)) {
            $args = [
                'labels' => [
                    'name' => $label,
                    'singular_name' => $label,
                    'menu_name' => $label
                ],
                'hierarchical' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'query_var' => true,
                'rewrite' => ['slug' => str_replace('pa_', '', $taxonomy)],
                'public' => true,
                'show_in_nav_menus' => false
            ];
            
            register_taxonomy($taxonomy, ['product', 'product_variation'], $args);
            
            delete_transient('wc_attribute_taxonomies');
            delete_option('_transient_wc_attribute_taxonomies');
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
        if (empty($images) || !is_array($images)) {
            return;
        }
        
        $product_images = [];
        $variant_images = [];
        
        foreach ($images as $image) {
            if (empty($image['src'])) {
                continue;
            }
            
            if (empty($image['variant_ids']) || count($image['variant_ids']) === 0) {
                $product_images[] = $image;
            } else {
                $variant_images[] = $image;
            }
        }
        
        usort($product_images, function($a, $b) {
            return ($a['position'] ?? 999) - ($b['position'] ?? 999);
        });
        
        $thumbnail_set = false;
        $gallery_ids = [];
        $processed_urls = [];
        
        foreach ($product_images as $image) {
            if (in_array($image['src'], $processed_urls)) {
                continue;
            }
            
            $attachment_id = sapo_download_image($image['src']);
            
            if ($attachment_id) {
                $processed_urls[] = $image['src'];
                
                if (!$thumbnail_set) {
                    set_post_thumbnail($product_id, $attachment_id);
                    $thumbnail_set = true;
                } else {
                    $gallery_ids[] = $attachment_id;
                }
            }
        }
        
        foreach ($variant_images as $image) {
            if (in_array($image['src'], $processed_urls)) {
                continue;
            }
            
            $attachment_id = sapo_download_image($image['src']);
            
            if ($attachment_id) {
                $processed_urls[] = $image['src'];
                $gallery_ids[] = $attachment_id;
            }
        }
        
        if (!empty($gallery_ids)) {
            $gallery_ids = array_unique($gallery_ids);
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
        }
    }
    
    private function find_variant_image($images, $image_id) {
        if (empty($images) || empty($image_id)) {
            return null;
        }
        
        foreach ($images as $image) {
            if ($image['id'] == $image_id) {
                return $image;
            }
        }
        
        return null;
    }
    
    private function set_product_categories($product_id, $sapo_product_id) {
        try {
            $client = new Sapo_Client();
            $collects = $client->collects()->get_by_product($sapo_product_id);
            
            if (empty($collects['collects'])) {
                return;
            }
            
            $category_ids = [];
            
            foreach ($collects['collects'] as $collect) {
                $collection_id = $collect['collection_id'] ?? 0;
                
                if (empty($collection_id)) {
                    continue;
                }
                
                $mapping = Sapo_DB::get_category_mapping($collection_id);
                
                if ($mapping && !empty($mapping->wc_category_id)) {
                    $category_ids[] = $mapping->wc_category_id;
                } elseif ($mapping && $mapping->auto_create) {
                    $collection = $client->collections()->get($collection_id);
                    
                    if (!empty($collection['collection']['name'])) {
                        $helper = new SapoWcCategory();
                        $cat_id = $helper->get_or_create_category($collection['collection']['name']);
                        
                        if ($cat_id) {
                            $category_ids[] = $cat_id;
                            
                            Sapo_DB::save_category_mapping(
                                $collection_id,
                                $collection['collection']['name'],
                                $cat_id,
                                $collection['collection']['name'],
                                true
                            );
                        }
                    }
                }
            }
            
            if (!empty($category_ids)) {
                wp_set_object_terms($product_id, $category_ids, 'product_cat');
            }
            
        } catch (Exception $e) {
            Sapo_Service_Log::log(
                'product',
                $sapo_product_id,
                $product_id,
                'set_categories',
                'error',
                $e->getMessage()
            );
        }
    }
}
