<?php
if (!defined('ABSPATH')) exit;

class Order_Resend_Handler extends Abstract_Order_Handler {
    public function __construct() {
        parent::__construct();
        add_action('woocommerce_order_actions', [$this, 'add_order_action']);
        add_action('woocommerce_order_action_asc_resend_xact_order_action', [$this, 'process_resend_order']);
    }

    public function add_order_action($actions) {
        $actions['asc_resend_xact_order_action'] = __('Resend Order To Xact', 'woocommerce-rest-order-sync');
        return $actions;
    }

    public function process_resend_order($order) {
        
        if (!$order) return;

        $this->send_to_api($order);
    }
}
