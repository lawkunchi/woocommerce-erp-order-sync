<?php

/**
 * Plugin Name: ERP Woocommerce Sync
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

require_once plugin_dir_path(__FILE__) . 'includes/imports/class-importer.php';
require_once plugin_dir_path(__FILE__) . 'includes/imports/class-rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/imports/class-cron.php';
require_once plugin_dir_path(__FILE__) . 'includes/imports/class-logger.php';

// Initialize the plugin classes
function woocommerce_rest_order_sync_init()
{
    new REST_Order_Handler();
    new Order_Resend_Handler();
}
add_action('plugins_loaded', 'woocommerce_rest_order_sync_init');



// Register Custom Cron Intervals
add_filter('cron_schedules', function ($schedules) {
    $schedules['every_30_minutes'] = ['interval' => 1800, 'display' => 'Every 30 Minutes'];
    $schedules['every_hour'] = ['interval' => 3600, 'display' => 'Every Hour'];
    $schedules['every_2_hours'] = ['interval' => 7200, 'display' => 'Every 2 Hours'];
    $schedules['every_3_hours'] = ['interval' => 10800, 'display' => 'Every 3 Hours'];
    return $schedules;
});

// Activate Plugin - Schedule Cron Jobs
function erp_woocommerce_sync_activate() {
    if (!wp_next_scheduled('erp_sync_qty_cron')) {
        wp_schedule_event(time(), 'every_30_minutes', 'erp_sync_qty_cron');
    }
    if (!wp_next_scheduled('erp_sync_prices_cron')) {
        wp_schedule_event(time(), 'every_hour', 'erp_sync_prices_cron');
    }
    if (!wp_next_scheduled('erp_sync_descriptions_cron')) {
        wp_schedule_event(time(), 'every_2_hours', 'erp_sync_descriptions_cron');
    }
    if (!wp_next_scheduled('erp_sync_images_cron')) {
        wp_schedule_event(time(), 'every_3_hours', 'erp_sync_images_cron');
    }
}
register_activation_hook(__FILE__, 'erp_woocommerce_sync_activate');

// Deactivate Plugin - Remove Cron Jobs
function erp_woocommerce_sync_deactivate() {
    wp_clear_scheduled_hook('erp_sync_qty_cron');
    wp_clear_scheduled_hook('erp_sync_prices_cron');
    wp_clear_scheduled_hook('erp_sync_descriptions_cron');
    wp_clear_scheduled_hook('erp_sync_images_cron');
}
register_deactivation_hook(__FILE__, 'erp_woocommerce_sync_deactivate');

// Register REST API
add_action('rest_api_init', function () {
    (new ERP_WooCommerce_REST_API())->register_routes();
});