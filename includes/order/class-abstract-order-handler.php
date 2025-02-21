<?php
if (!defined('ABSPATH')) exit;

abstract class Abstract_Order_Handler
{
    protected $api_endpoint;
    protected $emails;
    protected $api_type; 

    public function __construct()
    {
        $default_options = [
            'api_endpoint' => "http://asc.xact.co.za:50007/gas/ws/r/ws/demo-salesdoc-ws",
            'emails' => ['therushin.chetty@gmail.com', 'tom@mailmen.co.za'],
            'api_type' => 'soap' 
        ];

        $plugin_options = get_option('woocommerce_erp_order_sync_options', []);

        // Merge saved options with defaults
        $plugin_options = wp_parse_args($plugin_options, $default_options);

        // Save defaults if missing
        update_option('woocommerce_erp_order_sync_options', $plugin_options);

        $this->api_endpoint = $plugin_options['api_endpoint'];
        $this->emails = $plugin_options['emails'];
        $this->api_type = $plugin_options['api_type'];
    }

    /** Common method to format order data as JSON **/
    protected function format_order_to_json($order)
    {
        $asc_order_id = $order->get_id();
        $asc_user = $order->get_user();
        $asc_username = $asc_user->user_login;
        $asc_email = $asc_user->user_email;

        $asc_add_line_1 = $order->get_billing_address_1();
        $asc_city = $order->get_billing_city();
        $asc_province = $order->get_billing_state();
        $asc_post_code = $order->get_billing_postcode();

        $asc_ship_add_line_1 = $order->get_shipping_address_1() ?: 'N/A';
        $asc_ship_city = $order->get_shipping_city() ?: 'N/A';
        $asc_ship_province = $order->get_shipping_state() ?: 'N/A';
        $asc_ship_post_code = $order->get_shipping_postcode() ?: 'N/A';

        $asc_date = $order->get_date_created()->format('Y-m-d');
        $asc_time = $order->get_date_created()->format("H:i:s");
        $asc_order_total = number_format($order->get_total(), 2, '.', '');
        $asc_cust_ref = get_post_meta($order->get_id(), '_purchase_order_number', true);

        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $sku = $product->get_sku();
            $desc_1 = $item->get_name();
            $quantity = $item->get_quantity();
            $unit_price = number_format(($item->get_total() / max(1, $quantity)) * 1.15, 2, '.', '');

            $items[] = [
                "prod_code" => $sku,
                "desc_1" => $desc_1,
                "desc_2" => "",
                "doc_qty" => $quantity,
                "net_unit_price_incl" => $unit_price,
                "unit_kgs" => "",
                "special_price_incl" => ""
            ];
        }

        return json_encode([
            "sa22" => [
                "web_doc_no" => "ASC-{$asc_order_id}",
                "doc_type" => "S",
                "loc" => "WEB",
                "whs" => "WEB",
                "del_loc" => "DBN",
                "del_whs" => "DBN",
                "dl_code" => $asc_username,
                "dl_name" => "Dealer {$asc_username}",
                "vat_no" => "",
                "cust_ref" => $asc_cust_ref,
                "tel_no" => "",
                "email" => $asc_email,
                "post_add_1" => $asc_add_line_1,
                "post_add_2" => $asc_city,
                "post_add_3" => $asc_province,
                "post_add_4" => $asc_post_code,
                "del_add_1" => $asc_ship_add_line_1,
                "del_add_2" => $asc_ship_city,
                "del_add_3" => $asc_ship_province,
                "del_add_4" => $asc_ship_post_code,
                "doc_date" => $asc_date,
                "doc_time" => $asc_time,
                "tot_incl_vat" => $asc_order_total,
                "tot_kgs" => ""
            ],
            "sa23" => $items
        ]);
    }

    /** Handle API call based on selected API type **/
    protected function send_to_api($order)
    {
        if ($this->api_type === 'soap') {
            $xml_data = $this->format_order_to_xml($order);
            return $this->send_to_soap($xml_data, $order);
        } else {
            $json_data = $this->format_order_to_json($order);
            return $this->send_to_rest($json_data, $order);
        }
    }

    /** Send data to REST API **/
    protected function send_to_rest($json_data, $order)
    {
        $response = wp_remote_post($this->api_endpoint, [
            'method'    => 'POST',
            'body'      => $json_data,
            'headers'   => ['Content-Type' => 'application/json'],
        ]);

        $this->handle_response_errors($response, $order);
    }


    private function send_to_soap($xml_data, $order)
    {
        $response = wp_remote_post($this->api_endpoint, [
            'method'    => 'POST',
            'body'      => $xml_data,
            'headers'   => ['Content-Type' => 'application/soap+xml'],
        ]);

        if (is_wp_error($response)) {
            error_log('Order Sync Failed: ' . $response->get_error_message());
            return;
        }

        $httpCode = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $xml = simplexml_load_string(str_ireplace(['SOAP-ENV:', 'SOAP:', 'fjs1:'], '', $response_body));

        if ($httpCode == 200 && !empty($xml->Body->QT_SO_ImportResponse->qt_so_no)) {
            $order->add_order_note('Xact Doc No: ' . $xml->Body->QT_SO_ImportResponse->qt_so_no, 0, true);
        } else {
            $this->handle_response_errors($xml, $order);
        }
    }


    /** Format Order Data as XML for SOAP Requests **/
    private function format_order_to_xml($order)
    {
        $asc_order_id = $order->get_id();
        $asc_user = $order->get_user();
        $asc_username = $asc_user->user_login;
        $asc_email = $asc_user->user_email;
        $asc_name = $order->get_billing_first_name();

        $asc_add_line_1 = substr(preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_billing_address_1()), 0, 30);
        $asc_city = preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_billing_city());
        $asc_province = $order->get_billing_state();
        $asc_post_code = preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_billing_postcode());

        $asc_ship_add_line_1 = $order->get_shipping_address_1() ?: 'N/A';
        $asc_ship_city = preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_shipping_city() ?: 'N/A');
        $asc_ship_province = $order->get_shipping_state() ?: 'N/A';
        $asc_ship_post_code = preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', $order->get_shipping_postcode() ?: 'N/A');

        $asc_date = $order->get_date_created()->format('Y-m-d');
        $asc_time = $order->get_date_created()->format("H:i:s");
        $asc_order_total = number_format($order->get_total(), 2, '.', '');
        $asc_cust_ref = preg_replace('/&(?!#?[a-z0-9]+;)/', 'and', get_post_meta($order->get_id(), '_purchase_order_number', true));

        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n
        <soapenv:Envelope xmlns:soapenv=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:xact=\"http://asc.xact.co.za:50007/gas3/ws/r/xact\">\n
            <soapenv:Header/>\n
            <soapenv:Body>\n
                <xact:QT_SO_Import>\n
                    <sa22>\n
                        <web_doc_no>ASC-{$asc_order_id}</web_doc_no>\n
                        <doc_type>S</doc_type>\n
                        <loc>DBN</loc>\n
                        <whs>DBN</whs>\n
                        <dl_code>{$asc_username}</dl_code>\n
                        <dl_name>Dealer {$asc_username}</dl_name>\n
                        <vat_no></vat_no>\n
                        <cust_ref>{$asc_cust_ref}</cust_ref>\n
                        <tel_no></tel_no>\n
                        <email>{$asc_email}</email>\n
                        <post_add_1>{$asc_add_line_1}</post_add_1>\n
                        <post_add_2>{$asc_city}</post_add_2>\n
                        <post_add_3>{$asc_province}</post_add_3>\n
                        <post_add_4>{$asc_post_code}</post_add_4>\n
                        <del_add_1>{$asc_ship_add_line_1}</del_add_1>\n
                        <del_add_2>{$asc_ship_city}</del_add_2>\n
                        <del_add_3>{$asc_ship_province}</del_add_3>\n
                        <del_add_4>{$asc_ship_post_code}</del_add_4>\n
                        <doc_date>{$asc_date}</doc_date>\n
                        <doc_time>{$asc_time}</doc_time>\n
                        <tot_incl_vat>{$asc_order_total}</tot_incl_vat>\n
                        <tot_kgs></tot_kgs>\n
                    </sa22>\n
                    <sa23>\n";

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $sku = $product->get_sku();
            $desc_1 = preg_replace('/&(?!#?[a-z0-9]+;)/', 'AND', $item->get_name());
            $quantity = $item->get_quantity();
            $unit_price = number_format(($item->get_total() / max(1, $quantity)) * 1.15, 2, '.', '');

            $xml .= "<element>\n
                        <prod_code>{$sku}</prod_code>\n
                        <desc_1>{$desc_1}</desc_1>\n
                        <desc_2></desc_2>\n
                        <doc_qty>{$quantity}</doc_qty>\n
                        <net_unit_price_incl>{$unit_price}</net_unit_price_incl>\n
                        <unit_kgs></unit_kgs>\n
                        <special_price_incl></special_price_incl>\n
                    </element>\n";
        }

        $xml .= "</sa23>\n
                </xact:QT_SO_Import>\n
            </soapenv:Body>\n
        </soapenv:Envelope>";

        return $xml;
    }

    /** Handle API response errors **/
    protected function handle_response_errors($response, $order)
    {
        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode != 200) {
            $order->add_order_note('Failed to send order to Xact.', 0, true);
            error_log('Failed to send order to Xact.');

            $response_body = wp_remote_retrieve_body($response);
            $subject = 'Failed Sending Order #' . $order->get_id() . ' to Xact';
            $message = "<p>Error sending Order #{$order->get_id()} to Xact.</p>
                        <p>Response: {$response_body}</p>
                        <p><b>Order Total: R" . number_format($order->get_total(), 2, '.', '') . "</b></p>";

            wp_mail($this->emails, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
        }
    }
}
