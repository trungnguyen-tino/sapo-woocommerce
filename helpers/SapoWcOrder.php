<?php

if (!defined('ABSPATH')) {
    exit;
}

class SapoWcOrder {
    
    public function transform_to_sapo($wc_order) {
        if (!$wc_order || !is_a($wc_order, 'WC_Order')) {
            throw new Exception('Invalid WooCommerce order');
        }
        
        $order_data = [
            'status' => 'draft',
            'email' => $wc_order->get_billing_email(),
            'phone_number' => $this->clean_phone($wc_order->get_billing_phone()),
            'total' => floatval($wc_order->get_total()),
            'source_id' => $this->get_source_id(),
            'price_list_id' => $this->get_price_list_id(),
        ];
        
        $billing_address = $this->get_billing_address($wc_order);
        if ($billing_address) {
            $order_data['billing_address'] = $billing_address;
        }
        
        $shipping_address = $this->get_shipping_address($wc_order);
        if ($shipping_address) {
            $order_data['shipping_address'] = $shipping_address;
        }
        
        $order_line_items = $this->get_order_line_items($wc_order);
        if (!empty($order_line_items)) {
            $order_data['order_line_items'] = $order_line_items;
        }
        
        $prepayments = $this->get_prepayments($wc_order);
        if (!empty($prepayments)) {
            $order_data['prepayments'] = $prepayments;
        }
        
        $order_data = apply_filters('sapo_wc_order_data', $order_data, $wc_order);
        
        return $order_data;
    }
    
    private function get_billing_address($wc_order) {
        $first_name = $wc_order->get_billing_first_name();
        $last_name = $wc_order->get_billing_last_name();
        $full_name = trim($first_name . ' ' . $last_name);
        
        if (empty($full_name)) {
            $full_name = $wc_order->get_billing_company();
        }
        
        if (empty($full_name)) {
            return null;
        }
        
        return [
            'full_name' => $full_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address1' => $wc_order->get_billing_address_1(),
            'address2' => $wc_order->get_billing_address_2(),
            'phone_number' => $this->clean_phone($wc_order->get_billing_phone()),
            'email' => $wc_order->get_billing_email(),
            'country' => $this->get_country_name($wc_order->get_billing_country()),
            'city' => $wc_order->get_billing_city(),
            'district' => $wc_order->get_billing_state(),
            'ward' => $wc_order->get_meta('_billing_ward'),
            'zip' => $wc_order->get_billing_postcode(),
        ];
    }
    
    private function get_shipping_address($wc_order) {
        $first_name = $wc_order->get_shipping_first_name();
        $last_name = $wc_order->get_shipping_last_name();
        $full_name = trim($first_name . ' ' . $last_name);
        
        if (empty($full_name)) {
            return $this->get_billing_address($wc_order);
        }
        
        return [
            'full_name' => $full_name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'address1' => $wc_order->get_shipping_address_1(),
            'address2' => $wc_order->get_shipping_address_2(),
            'phone_number' => $this->clean_phone($wc_order->get_shipping_phone() ?: $wc_order->get_billing_phone()),
            'country' => $this->get_country_name($wc_order->get_shipping_country()),
            'city' => $wc_order->get_shipping_city(),
            'district' => $wc_order->get_shipping_state(),
            'ward' => $wc_order->get_meta('_shipping_ward'),
            'zip' => $wc_order->get_shipping_postcode(),
        ];
    }
    
    private function get_order_line_items($wc_order) {
        $line_items = [];
        
        foreach ($wc_order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            $wc_product_id = $product->get_id();
            
            $mapping = Sapo_DB::get_wc_product_mapping($wc_product_id);
            
            if (!$mapping) {
                Sapo_Service_Log::log(
                    'order',
                    0,
                    $wc_order->get_id(),
                    'get_line_items',
                    'warning',
                    "Product #{$wc_product_id} not synced with SAPO"
                );
                continue;
            }
            
            $variant_id = $mapping->sapo_variant_id ?: null;
            $product_id = $mapping->sapo_product_id;
            
            $line_item = [
                'product_id' => intval($product_id),
                'quantity' => intval($item->get_quantity()),
                'price' => floatval($item->get_subtotal() / $item->get_quantity()),
                'tax_rate' => 0,
                'tax_included' => false,
            ];
            
            if ($variant_id) {
                $line_item['variant_id'] = intval($variant_id);
            }
            
            if ($item->get_subtotal_tax() > 0) {
                $tax_rate = ($item->get_subtotal_tax() / $item->get_subtotal()) * 100;
                $line_item['tax_rate'] = round($tax_rate, 2);
                $line_item['tax_included'] = wc_prices_include_tax();
            }
            
            $line_items[] = $line_item;
        }
        
        return $line_items;
    }
    
    private function get_prepayments($wc_order) {
        $prepayments = [];
        
        if ($wc_order->get_status() === 'processing' || $wc_order->get_status() === 'completed') {
            $payment_method_id = $this->get_payment_method_id($wc_order->get_payment_method());
            
            if ($payment_method_id) {
                $prepayments[] = [
                    'payment_method_id' => $payment_method_id,
                    'amount' => floatval($wc_order->get_total()),
                    'source' => 'customer_prepaid',
                    'paid_amount' => floatval($wc_order->get_total()),
                ];
            }
        }
        
        return $prepayments;
    }
    
    private function clean_phone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (strpos($phone, '84') === 0) {
            $phone = '+' . $phone;
        } elseif (strpos($phone, '0') === 0) {
            $phone = '+84' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    private function get_country_name($country_code) {
        if (empty($country_code)) {
            return 'Việt Nam';
        }
        
        $countries = WC()->countries->get_countries();
        
        return isset($countries[$country_code]) ? $countries[$country_code] : $country_code;
    }
    
    private function get_source_id() {
        $source_id = get_option('sapo_sync_source_id');
        
        if (!$source_id) {
            $source_id = 1387871;
        }
        
        return intval($source_id);
    }
    
    private function get_price_list_id() {
        $price_list_id = get_option('sapo_sync_price_list_id');
        
        if (!$price_list_id) {
            $price_list_id = 529736;
        }
        
        return intval($price_list_id);
    }
    
    private function get_payment_method_id($wc_payment_method) {
        $mapping = get_option('sapo_payment_method_mapping', []);
        
        if (isset($mapping[$wc_payment_method])) {
            return intval($mapping[$wc_payment_method]);
        }
        
        $default_payment_method = get_option('sapo_default_payment_method_id');
        
        if ($default_payment_method) {
            return intval($default_payment_method);
        }
        
        return 698232;
    }
    
    public function update_wc_order_status($wc_order_id, $sapo_status) {
        $wc_order = wc_get_order($wc_order_id);
        
        if (!$wc_order) {
            return false;
        }
        
        $status_mapping = [
            'draft' => 'pending',
            'finalized' => 'processing',
            'fulfilled' => 'completed',
            'cancelled' => 'cancelled',
        ];
        
        $wc_status = isset($status_mapping[$sapo_status]) ? $status_mapping[$sapo_status] : null;
        
        if ($wc_status && $wc_order->get_status() !== $wc_status) {
            $wc_order->update_status($wc_status, __('Updated from SAPO', 'sapo-sync'));
            return true;
        }
        
        return false;
    }
}
