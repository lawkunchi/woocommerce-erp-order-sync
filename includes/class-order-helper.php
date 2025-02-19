<?php
if (!defined('ABSPATH')) exit;

class Order_Helper {

    public static function format_order_data($order) {
        return [
            'order_id' => $order->get_id(),
            'user' => [
                'username' => $order->get_user()->user_login,
                'email' => $order->get_user()->user_email,
            ],
            'billing' => [
                'first_name' => $order->get_billing_first_name(),
                'address' => substr(preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_billing_address_1()), 0, 30),
                'city' => preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_billing_city()),
                'province' => $order->get_billing_state(),
                'postcode' => preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_billing_postcode()),
            ],
            'shipping' => [
                'address' => substr(preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_shipping_address_1() ?: 'N/A'), 0, 30),
                'city' => preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_shipping_city() ?: 'N/A'),
                'province' => $order->get_shipping_state() ?: 'N/A',
                'postcode' => preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_shipping_postcode() ?: 'N/A'),
            ],
            'items' => self::format_order_items($order),
            'total' => number_format($order->get_total(), 2, '.', ''),
            'created_at' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'customer_ref' => preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', get_post_meta($order->get_order_number(), '_purchase_order_number', true)),
        ];
    }

    private static function format_order_items($order) {
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'sku' => $product->get_sku(),
                'name' => preg_replace('/&(?!#?[a-z0-9]+;)/', 'AND', $item->get_name()),
                'quantity' => $item->get_quantity(),
                'price' => number_format(($item->get_total() / max(1, $item->get_quantity())) * 1.15, 2, '.', ''),
            ];
        }
        return $items;
    }
}
