<?php
if (!defined('ABSPATH')) exit;

class Woocommerce_ERP_Order_Sync_Settings {
    private static $option_name = 'woocommerce_erp_order_sync_options';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_settings_page() {
        add_options_page(
            'ERP Order Sync Settings',
            'ERP Order Sync',
            'manage_options',
            'woocommerce-erp-order-sync',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting(self::$option_name, self::$option_name);

        add_settings_section('general_settings', 'General Settings', null, self::$option_name);

        add_settings_field(
            'api_endpoint',
            'API Endpoint',
            [__CLASS__, 'api_endpoint_callback'],
            self::$option_name,
            'general_settings'
        );

        add_settings_field(
            'handler_api_endpoint',
            'Handler API Endpoint',
            [__CLASS__, 'handler_api_endpoint_callback'],
            self::$option_name,
            'general_settings'
        );

        add_settings_field(
            'emails',
            'Notification Emails (comma-separated)',
            [__CLASS__, 'emails_callback'],
            self::$option_name,
            'general_settings'
        );
    }

    public static function api_endpoint_callback() {
        $options = get_option(self::$option_name);
        echo '<input type="text" name="' . self::$option_name . '[api_endpoint]" value="' . esc_attr($options['api_endpoint'] ?? '') . '" class="regular-text">';
    }

    public static function handler_api_endpoint_callback() {
        $options = get_option(self::$option_name);
        echo '<input type="text" name="' . self::$option_name . '[handler_api_endpoint]" value="' . esc_attr($options['handler_api_endpoint'] ?? '') . '" class="regular-text">';
    }

    public static function emails_callback() {
        $options = get_option(self::$option_name);
        $emails = isset($options['emails']) ? implode(',', $options['emails']['wordpress'] ?? []) : '';
        echo '<input type="text" name="' . self::$option_name . '[emails]" value="' . esc_attr($emails) . '" class="regular-text">';
        echo '<p class="description">Enter emails separated by commas.</p>';
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ERP Order Sync Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::$option_name);
                do_settings_sections(self::$option_name);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

Woocommerce_ERP_Order_Sync_Settings::init();
