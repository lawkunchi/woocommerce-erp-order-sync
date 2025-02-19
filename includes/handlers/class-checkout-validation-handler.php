<?php
if (!defined('ABSPATH')) exit;

class Checkout_Validation_Handler {

    private const API_ENDPOINT = "http://asc.xact.co.za:50007/gas3/ws/r/xact/asc-master-ws-3?GetAcctMaster&acct_code=";
    
    private $api_endpoint;
    public function __construct() {
        $plugin_options = get_option('woocommerce_erp_order_sync_options', [
            'handler_api_endpoint' => self::API_ENDPOINT,
        ]);
        $this->api_endpoint = $plugin_options['handler_api_endpoint'] ?? self::API_ENDPOINT;
        add_action('woocommerce_after_checkout_validation', [$this, 'deny_if_user_on_acc_hold']);
    }

    public function deny_if_user_on_acc_hold($posted) {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $logged_current_user = $current_user->user_login;

            // Get account status from API
            $responseAcc = $this->fetch_account_status($logged_current_user);
            
            if (!$responseAcc) {
                wc_add_notice('Unable to verify account status. Please try again later.', 'error');
                return;
            }

            $xmlobjAccCheck = new SimpleXMLElement($responseAcc);

            $valid_code = (string) $xmlobjAccCheck->acct_code->attributes()->valid_code;
            $acct_info = (string) $xmlobjAccCheck->acct_code->attributes()->acct_info;

            if ($acct_info == "Account ON HOLD!") {
                $notice = 'Account ' . esc_html($logged_current_user) . ' is currently On-Hold. Please contact ASC at Tel: (031) 581-8400';
                wc_add_notice($notice, 'error');
            }
        }
    }

    private function fetch_account_status($username) {
        $url = $this->api_endpoint . urlencode($username);

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/xml'
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('Account Status Check Failed: ' . $response->get_error_message());
            return false;
        }

        return wp_remote_retrieve_body($response);
    }
}

new Checkout_Validation_Handler();
