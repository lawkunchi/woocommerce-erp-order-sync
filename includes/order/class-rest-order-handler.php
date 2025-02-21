<?php
if (!defined('ABSPATH')) exit;

class REST_Order_Handler extends Abstract_Order_Handler {
    public function __construct() {
        parent::__construct();
        add_action('woocommerce_thankyou', [$this, 'send_order_to_api'], 10, 1);
    }

    public function send_order_to_api($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $this->send_to_api($order);
    }
}