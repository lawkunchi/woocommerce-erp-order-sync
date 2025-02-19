<?php

if (!defined('ABSPATH')) {
    exit;
}

class ERP_WooCommerce_REST_API {
    
    public function register_routes() {
        register_rest_route('erp-sync/v1', '/sync-qty/', array(
            'methods'  => 'POST',
            'callback' => array($this, 'sync_qty'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('erp-sync/v1', '/sync-prices/', array(
            'methods'  => 'POST',
            'callback' => array($this, 'sync_prices'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('erp-sync/v1', '/sync-descriptions/', array(
            'methods'  => 'POST',
            'callback' => array($this, 'sync_descriptions'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('erp-sync/v1', '/sync-images/', array(
            'methods'  => 'POST',
            'callback' => array($this, 'sync_images'),
            'permission_callback' => '__return_true'
        ));
        

        register_rest_route('erp-sync/v1', '/sync-all/', array(
            'methods'  => 'POST',
            'callback' => array($this, 'sync_all'),
            'permission_callback' => '__return_true'
        ));
    }

    public function sync_qty() {
        $importer = new ERP_Importer();
        return $importer->sync_qty();
    }

    public function sync_prices() {
        $importer = new ERP_Importer();
        return $importer->sync_prices();
    }

    public function sync_descriptions() {
        $importer = new ERP_Importer();
        return $importer->sync_descriptions();
    }

    public function sync_images() {
        $importer = new ERP_Importer();
        return $importer->sync_images();
    }

    public function sync_all() {
        $importer = new ERP_Importer();
        
        $result_qty = $importer->sync_qty();
        $result_prices = $importer->sync_prices();
        $result_desc = $importer->sync_descriptions();
        $result_images = $importer->sync_images();

        return new WP_REST_Response([
            'message' => 'All sync jobs triggered.',
            'status' => [
                'stock' => is_wp_error($result_qty) ? $result_qty->get_error_message() : 'Success',
                'prices' => is_wp_error($result_prices) ? $result_prices->get_error_message() : 'Success',
                'descriptions' => is_wp_error($result_desc) ? $result_desc->get_error_message() : 'Success',
                'images' => is_wp_error($result_images) ? $result_images->get_error_message() : 'Success',
            ]
        ], 200);
    }
}
