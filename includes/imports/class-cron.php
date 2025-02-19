<?php

if (!defined('ABSPATH')) {
    exit;
}

class ERP_WooCommerce_Cron {

    public function __construct() {
        add_action('erp_sync_qty_cron', [$this, 'sync_qty']);
        add_action('erp_sync_prices_cron', [$this, 'sync_prices']);
        add_action('erp_sync_descriptions_cron', [$this, 'sync_descriptions']);
        add_action('erp_sync_images_cron', [$this, 'sync_images_cron']);
    }

    public function sync_qty() {
        $importer = new ERP_Importer();
        $result = $importer->sync_qty();
        if (is_wp_error($result)) {
            ERP_Logger::log_error("Stock quantity sync failed: " . $result->get_error_message());
        }
    }

    public function sync_prices() {
        $importer = new ERP_Importer();
        $result = $importer->sync_prices();
        if (is_wp_error($result)) {
            ERP_Logger::log_error("Price sync failed: " . $result->get_error_message());
        }
    }

    public function sync_descriptions() {
        $importer = new ERP_Importer();
        $result = $importer->sync_descriptions();
        if (is_wp_error($result)) {
            ERP_Logger::log_error("Description sync failed: " . $result->get_error_message());
        }
    }

    public function sync_images() {
        $importer = new ERP_Importer();
        $result = $importer->sync_images();
        if (is_wp_error($result)) {
            ERP_Logger::log_error("Image sync failed: " . $result->get_error_message());
        }
    }

    public function sync_images_cron() {
        $importer = new ERP_Importer();
        $result = $importer->sync_images_cron();
        if (is_wp_error($result)) {
            ERP_Logger::log_error("Image sync failed: " . $result->get_error_message());
        }
    }
    
}

new ERP_WooCommerce_Cron();
