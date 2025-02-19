<?php

if (!defined('ABSPATH')) {
    exit;
}

class ERP_Importer {

    private $erp_api_base = "http://asc.xact.co.za:50007/gas3/ws/r/xact/asc-master-ws-3";

    public function fetch_data($endpoint) {
        $url = "{$this->erp_api_base}{$endpoint}";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        return simplexml_load_string($body);
    }

    public function sync_qty() {
        $stockData = $this->fetch_data("?GetStkBalance&loc=DBN&whs=DBN&stk_code&per=100");

        if (!$stockData) {
            return new WP_Error('error', 'Failed to fetch stock data.', ['status' => 500]);
        }

        foreach ($stockData->code as $code) {
            $sku = (string)$code->attributes()->stk_code;
            $stock = (int)$code->attributes()->phy_bal;

            $this->update_product_qty($sku, $stock);
        }

        return new WP_REST_Response(['message' => 'Stock quantities updated.'], 200);
    }

    public function sync_prices() {
        $priceData = $this->fetch_data("?GetStkPrices&loc=DBN&stk_code");

        if (!$priceData) {
            return new WP_Error('error', 'Failed to fetch price data.', ['status' => 500]);
        }

        foreach ($priceData->code as $code) {
            $sku = (string)$code->attributes()->stk_code;
            $price = (float)$code->attributes()->list_price_excl;

            $this->update_product_price($sku, $price);
        }

        return new WP_REST_Response(['message' => 'Prices updated.'], 200);
    }

    public function sync_descriptions() {
        $descData = $this->fetch_data("?GetStkMasterFile&loc=DBN&stk_code");

        if (!$descData) {
            return new WP_Error('error', 'Failed to fetch descriptions.', ['status' => 500]);
        }

        foreach ($descData->code as $code) {
            $sku = (string)$code->attributes()->stk_code;
            $description = (string)$code->attributes()->desc_1;

            $this->update_product_description($sku, $description);
        }

        return new WP_REST_Response(['message' => 'Descriptions updated.'], 200);
    }

    public function sync_images($request) {
        $skus = $request->get_param('skus');

        if (empty($skus)) {
            return new WP_Error('error', 'No SKUs provided.', ['status' => 400]);
        }

        $skuArray = explode(',', $skus);
        $successfulUpdates = [];
        $failedUpdates = [];

        foreach ($skuArray as $sku) {
            // Fetch image data from ERP API
            $imageData = $this->fetch_data("?GetStkImage&stk_code=$sku");

            if (!$imageData || empty($imageData->code)) {
                $failedUpdates[] = $sku;
                continue;
            }

            // Extract the base64-encoded image
            $encodedImage = (string) $imageData->code->attributes()->str_byte;
            if (empty($encodedImage)) {
                $failedUpdates[] = $sku;
                continue;
            }

            // Convert base64 to image URL (ERP-hosted image)
            $imageURL = "data:image/png;base64," . $encodedImage;

            // Update WooCommerce product with image URL
            if ($this->update_product_image($sku, $imageURL)) {
                $successfulUpdates[] = $sku;
            } else {
                $failedUpdates[] = $sku;
            }
        }

        return new WP_REST_Response([
            'message' => 'Product images updated.',
            'success_count' => count($successfulUpdates),
            'failed_count' => count($failedUpdates),
            'failed_skus' => $failedUpdates,
        ], 200);
    }

    /**
     * Detects missing product images in WooCommerce and syncs them automatically.
     */
    public function sync_images_cron() {
        global $wpdb;

        // Get products missing a featured image
        $query = "
            SELECT p.ID, pm.meta_value AS sku
            FROM {$wpdb->prefix}posts p
            LEFT JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
            LEFT JOIN {$wpdb->prefix}postmeta img ON p.ID = img.post_id AND img.meta_key = '_thumbnail_id'
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish'
            AND (img.meta_value IS NULL OR img.meta_value = '')
            AND pm.meta_value IS NOT NULL
        ";

        $missingImages = $wpdb->get_results($query);

        if (empty($missingImages)) {
            return "No missing images found.";
        }

        // Extract SKUs
        $skuArray = array_map(function ($item) {
            return $item->sku;
        }, $missingImages);

        $skus = implode(',', $skuArray);

        // Call sync_images REST API automatically
        $url = site_url("/wp-json/erp-sync/v1/sync-images/");
        $response = wp_remote_post($url, [
            'method'    => 'POST',
            'timeout'   => 30,
            'blocking'  => false,
            'headers'   => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'body'      => ['skus' => $skus]
        ]);

        if (is_wp_error($response)) {
            return "Error: " . $response->get_error_message();
        }

        return "Missing images sync triggered for: " . count($missingImages) . " products.";
    }


    private function update_product_qty($sku, $stock) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id) {
            update_post_meta($product_id, '_stock', $stock);
        }
    }

    private function update_product_price($sku, $price) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id) {
            $product = wc_get_product($product_id);
            $product->set_regular_price($price);
            $product->save();
        }
    }

    private function update_product_description($sku, $description) {
        $product_id = wc_get_product_id_by_sku($sku);
        if ($product_id) {
            wp_update_post(['ID' => $product_id, 'post_content' => $description]);
        }
    }

    private function update_product_image($sku, $imageURL) {
        $product_id = wc_get_product_id_by_sku($sku);
        if (!$product_id) {
            return false;
        }

        // Check if product has an existing image
        $existingImageID = get_post_thumbnail_id($product_id);
        if ($existingImageID) {
            // Delete existing image before updating
            wp_delete_attachment($existingImageID, true);
        }

        // Attach new image URL to product
        $attachmentID = $this->upload_external_image($imageURL, $sku);
        if ($attachmentID) {
            set_post_thumbnail($product_id, $attachmentID);
            return true;
        }

        return false;
    }

    private function upload_external_image($imageURL, $sku) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($imageURL);
        if (!$image_data) {
            return false;
        }

        $filename = "product-image-{$sku}.png";
        $filepath = $upload_dir['path'] . '/' . $filename;
        file_put_contents($filepath, $image_data);

        $filetype = wp_check_filetype($filename, null);
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $filepath);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}
