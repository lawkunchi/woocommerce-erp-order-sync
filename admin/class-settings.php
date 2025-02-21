<?php
if (!defined('ABSPATH')) exit;

class ERP_WooCommerce_Settings {
    private static $option_name = 'woocommerce_erp_order_sync_options';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /** Add the main menu & submenu **/
    public function add_admin_menu() {
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

    /** Render the admin page with tabbed navigation **/
    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'order_sync';

        echo '<div class="wrap">';
        echo '<h1>ERP WooCommerce Sync</h1>';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=erp-sync&tab=order_sync" class="nav-tab ' . ($active_tab === 'order_sync' ? 'nav-tab-active' : '') . '">Order Sync</a>';
        echo '<a href="?page=erp-sync&tab=imports" class="nav-tab ' . ($active_tab === 'imports' ? 'nav-tab-active' : '') . '">Imports</a>';
        echo '<a href="?page=erp-sync&tab=settings" class="nav-tab ' . ($active_tab === 'settings' ? 'nav-tab-active' : '') . '">Settings</a>';
        echo '</h2>';

        switch ($active_tab) {
            case 'imports':
                $this->render_imports_tab();
                break;
            case 'settings':
                $this->render_settings_tab();
                break;
            case 'order_sync':
            default:
                $this->render_order_sync_tab();
                break;
        }

        echo '</div>';
    }

    /** Order Sync Tab **/
    private function render_order_sync_tab() {
        echo '<h2>Order Sync</h2>';
        echo '<p>View and manage order synchronization with ERP.</p>';
        echo '<pre>' . ERP_Logger::get_errors() . '</pre>';
    }

    /** Imports Tab **/
    private function render_imports_tab() {
        echo '<h2>Run Imports</h2>';
        echo '<form method="post">';

        wp_nonce_field('erp_sync_action_nonce');
        
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
            check_admin_referer('erp_sync_action_nonce');
            $sync_type = sanitize_text_field($_POST['sync_type']);
            $result = $this->trigger_sync($sync_type);
            echo '<p><strong>' . esc_html($result) . '</strong></p>';
        }
        
    }

    /** Settings Tab **/
    private function render_settings_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields(self::$option_name);
            do_settings_sections(self::$option_name);
            submit_button();
            ?>
        </form>
        <?php
    }

    /** Register settings **/
    public function register_settings() {
        register_setting(self::$option_name, self::$option_name);

        add_settings_section('general_settings', 'General Settings', null, self::$option_name);

        add_settings_field(
            'api_type',
            'API Type',
            [$this, 'api_type_callback'],
            self::$option_name,
            'general_settings'
        );

        add_settings_field(
            'api_endpoint',
            'API Endpoint',
            [$this, 'api_endpoint_callback'],
            self::$option_name,
            'general_settings'
        );

        add_settings_field(
            'handler_api_endpoint',
            'Handler API Endpoint',
            [$this, 'handler_api_endpoint_callback'],
            self::$option_name,
            'general_settings'
        );

        add_settings_field(
            'emails',
            'Notification Emails (comma-separated)',
            [$this, 'emails_callback'],
            self::$option_name,
            'general_settings'
        );
    }

/** API Type Field **/
    public function api_type_callback() {
        $options = get_option(self::$option_name);
        $api_type = isset($options['api_type']) ? $options['api_type'] : 'soap';
        ?>
        <select name="<?php echo self::$option_name; ?>[api_type]">
            <option value="soap" <?php selected($api_type, 'soap'); ?>>SOAP</option>
            <option value="rest" <?php selected($api_type, 'rest'); ?>>REST</option>
        </select>
        <p class="description">Choose whether to use SOAP or REST API for order sync.</p>
        <?php
    }


    /** API Endpoint Field **/
    public function api_endpoint_callback() {
        $options = get_option(self::$option_name);
        echo '<input type="text" name="' . self::$option_name . '[api_endpoint]" value="' . esc_attr($options['api_endpoint'] ?? '') . '" class="regular-text">';
    }

    /** Handler API Endpoint Field **/
    public function handler_api_endpoint_callback() {
        $options = get_option(self::$option_name);
        echo '<input type="text" name="' . self::$option_name . '[handler_api_endpoint]" value="' . esc_attr($options['handler_api_endpoint'] ?? '') . '" class="regular-text">';
    }

    /** Emails Field **/
    public function emails_callback() {
        $options = get_option(self::$option_name);
        $emails = isset($options['emails']) ? implode(',', (array)$options['emails']) : '';
        echo '<input type="text" name="' . self::$option_name . '[emails]" value="' . esc_attr($emails) . '" class="regular-text">';
        echo '<p class="description">Enter emails separated by commas.</p>';
    }

    /** Trigger Sync via REST API **/
    private function trigger_sync($endpoint) {
        $url = site_url("/wp-json/erp-sync/v1/" . $endpoint);
        $response = wp_remote_post($url, [
            'method'    => 'POST',
            'timeout'   => 30,
            'blocking'  => true,
            'headers'   => ['Content-Type' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            return "Error: " . $response->get_error_message();
        }

        return "Sync Triggered for " . $endpoint;
    }
}

new ERP_WooCommerce_Settings();
