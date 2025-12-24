<?php

if (!defined('ABSPATH')) {
    exit;
}

class SapoWcCustomer {
    
    public function transform_to_sapo($wc_customer) {
        if (!$wc_customer) {
            throw new Exception('Invalid WooCommerce customer');
        }
        
        if (is_numeric($wc_customer)) {
            $wc_customer = new WC_Customer($wc_customer);
        }
        
        $customer_data = [
            'name' => $this->get_customer_name($wc_customer),
            'phone_number' => $this->clean_phone($wc_customer->get_billing_phone()),
            'email' => $wc_customer->get_email(),
        ];
        
        if ($wc_customer->get_first_name()) {
            $customer_data['first_name'] = $wc_customer->get_first_name();
        }
        
        if ($wc_customer->get_last_name()) {
            $customer_data['last_name'] = $wc_customer->get_last_name();
        }
        
        $addresses = $this->get_addresses($wc_customer);
        if (!empty($addresses)) {
            $customer_data['addresses'] = $addresses;
        }
        
        return $customer_data;
    }
    
    public function transform_from_order($wc_order) {
        if (!$wc_order || !is_a($wc_order, 'WC_Order')) {
            throw new Exception('Invalid WooCommerce order');
        }
        
        $first_name = $wc_order->get_billing_first_name();
        $last_name = $wc_order->get_billing_last_name();
        $full_name = trim($first_name . ' ' . $last_name);
        
        if (empty($full_name)) {
            $full_name = $wc_order->get_billing_company() ?: 'Guest Customer';
        }
        
        $customer_data = [
            'name' => $full_name,
            'phone_number' => $this->clean_phone($wc_order->get_billing_phone()),
            'email' => $wc_order->get_billing_email(),
        ];
        
        if ($first_name) {
            $customer_data['first_name'] = $first_name;
        }
        
        if ($last_name) {
            $customer_data['last_name'] = $last_name;
        }
        
        $billing_address = $this->get_billing_address_from_order($wc_order);
        if ($billing_address) {
            $customer_data['addresses'] = [$billing_address];
        }
        
        return $customer_data;
    }
    
    private function get_customer_name($wc_customer) {
        $name = trim($wc_customer->get_first_name() . ' ' . $wc_customer->get_last_name());
        
        if (empty($name)) {
            $name = $wc_customer->get_billing_company();
        }
        
        if (empty($name)) {
            $name = $wc_customer->get_email();
        }
        
        if (empty($name)) {
            $name = 'Customer #' . $wc_customer->get_id();
        }
        
        return $name;
    }
    
    private function get_addresses($wc_customer) {
        $addresses = [];
        
        if ($wc_customer->get_billing_address_1()) {
            $addresses[] = [
                'country' => $this->get_country_name($wc_customer->get_billing_country()),
                'city' => $wc_customer->get_billing_city(),
                'district' => $wc_customer->get_billing_state(),
                'address1' => $wc_customer->get_billing_address_1(),
                'address2' => $wc_customer->get_billing_address_2(),
                'phone_number' => $this->clean_phone($wc_customer->get_billing_phone()),
                'label' => 'Billing',
                'is_default' => true,
            ];
        }
        
        if ($wc_customer->get_shipping_address_1() && 
            $wc_customer->get_shipping_address_1() !== $wc_customer->get_billing_address_1()) {
            $addresses[] = [
                'country' => $this->get_country_name($wc_customer->get_shipping_country()),
                'city' => $wc_customer->get_shipping_city(),
                'district' => $wc_customer->get_shipping_state(),
                'address1' => $wc_customer->get_shipping_address_1(),
                'address2' => $wc_customer->get_shipping_address_2(),
                'phone_number' => $this->clean_phone($wc_customer->get_billing_phone()),
                'label' => 'Shipping',
            ];
        }
        
        return $addresses;
    }
    
    private function get_billing_address_from_order($wc_order) {
        $address1 = $wc_order->get_billing_address_1();
        
        if (empty($address1)) {
            return null;
        }
        
        return [
            'country' => $this->get_country_name($wc_order->get_billing_country()),
            'city' => $wc_order->get_billing_city(),
            'district' => $wc_order->get_billing_state(),
            'address1' => $address1,
            'address2' => $wc_order->get_billing_address_2(),
            'phone_number' => $this->clean_phone($wc_order->get_billing_phone()),
            'label' => 'Giao hàng',
            'is_default' => true,
        ];
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
    
    public function create_wc_customer($sapo_customer) {
        $customer = new WC_Customer();
        
        if (!empty($sapo_customer['email'])) {
            $customer->set_email($sapo_customer['email']);
        }
        
        if (!empty($sapo_customer['first_name'])) {
            $customer->set_first_name($sapo_customer['first_name']);
        }
        
        if (!empty($sapo_customer['last_name'])) {
            $customer->set_last_name($sapo_customer['last_name']);
        }
        
        if (!empty($sapo_customer['phone_number'])) {
            $customer->set_billing_phone($sapo_customer['phone_number']);
        }
        
        if (!empty($sapo_customer['addresses'][0])) {
            $address = $sapo_customer['addresses'][0];
            
            if (!empty($address['address1'])) {
                $customer->set_billing_address_1($address['address1']);
            }
            
            if (!empty($address['city'])) {
                $customer->set_billing_city($address['city']);
            }
            
            if (!empty($address['district'])) {
                $customer->set_billing_state($address['district']);
            }
        }
        
        $customer_id = $customer->save();
        
        if ($customer_id) {
            Sapo_DB::create_customer_mapping([
                'sapo_customer_id' => $sapo_customer['id'],
                'wc_customer_id' => $customer_id
            ]);
        }
        
        return $customer_id;
    }
}
