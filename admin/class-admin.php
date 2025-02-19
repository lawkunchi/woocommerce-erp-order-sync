<?php

if (!defined('ABSPATH')) {
    exit;
}

class ERP_WooCommerce_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_page']);
    }

    public function add_admin_page() {
        add_menu_page(
            'ERP WooCommerce Sync',
            'ERP Sync',
            'manage_options',
            'erp-sync',
            [$this, 'render_admin_page'],
            'dashicons-update',
            30
        );
    }

    public function render_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>ERP WooCommerce Sync</h1>';
        echo '<p>Manually run sync jobs and check failed imports.</p>';
        
        echo '<h2>Run Imports</h2>';
        echo '<form method="post">';
        echo '<input type="hidden" name="erp_sync_action" value="run_sync">';
        echo '<select name="sync_type">
                <option value="sync-qty">Stock Quantity</option>
                <option value="sync-prices">Prices</option>
                <option value="sync-descriptions">Descriptions</option>
                <option value="sync-images">Images</option>
              </select>';
        submit_button('Run Sync');
        echo '</form>';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['erp_sync_action']) && $_POST['erp_sync_action'] === 'run_sync') {
            $sync_type = sanitize_text_field($_POST['sync_type']);
            $result = $this->trigger_sync($sync_type);
            echo '<p><strong>' . esc_html($result) . '</strong></p>';
        }

        echo '<h2>Failed Imports</h2>';
        echo '<pre>' . ERP_Logger::get_errors() . '</pre>';

        echo '</div>';
    }

    private function trigger_sync($endpoint) {
        $url = site_url("/wp-json/erp-sync/v1/" . $endpoint);
        $response = wp_remote_post($url, array(
            'method'    => 'POST',
            'timeout'   => 30,
            'blocking'  => true,
            'headers'   => array('Content-Type' => 'application/json'),
        ));

        if (is_wp_error($response)) {
            return "Error: " . $response->get_error_message();
        }

        return "Sync Triggered for " . $endpoint;
    }
}

new ERP_WooCommerce_Admin();
