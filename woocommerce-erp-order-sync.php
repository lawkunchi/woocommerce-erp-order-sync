<?php

/**
 * Plugin Name: Woocommerce ERP Order Sync
 * Description: Sends WooCommerce order data to an external erp system.
 * Version: 1.0.0
 * Author: Lawrence Chibondo
 * Author URI: https://linkedin.com/in/lawrence-chibondo
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-erp-order-sync
 * Support Emails:
 * - Primary: lawkunchi@yahoo.com
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

// Include required files
require_once plugin_dir_path(__FILE__) . 'admin/class-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-rest-order-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-order-resend-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/handlers/class-checkout-validation-handler.php';

// Initialize the plugin classes
function woocommerce_rest_order_sync_init()
{
    new REST_Order_Handler();
    new Order_Resend_Handler();
}
add_action('plugins_loaded', 'woocommerce_rest_order_sync_init');
