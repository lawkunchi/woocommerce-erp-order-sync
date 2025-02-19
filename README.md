# Woocommerce ERP Order Sync Plugin

## Description
The **Woocommerce ERP Order Sync Plugin** allows seamless integration between WooCommerce and an external ERP system. This plugin automatically sends order data to the ERP system using XML-based SOAP API requests, ensuring order details are efficiently transferred upon order completion.

## Features
- Syncs WooCommerce orders with an external ERP system using SOAP XML.
- Adds a custom order action to manually resend orders.
- Blocks checkout if a user's account is on hold.
- Provides an admin settings page to configure API endpoint and notification emails.
- Logs errors and sends email notifications for failed order transmissions.

## Installation
1. Upload the plugin files to the `/wp-content/plugins/woocommerce-erp-order-sync` directory, or install the plugin via the WordPress plugin repository.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > ERP Order Sync** to configure API details.

## Usage
### Order Sync
- Orders are automatically sent to the ERP system when a purchase is completed.
- Admins can manually resend an order using the "Resend Order to Xact" option in WooCommerce order actions.

### Account Validation
- The plugin checks the user's account status before checkout.
- If the account is on hold, checkout is blocked with an appropriate message.

## Settings Page
Navigate to **Settings > ERP Order Sync** to configure:
- **API Endpoint**: URL for sending order data.
- **Handler API Endpoint**: URL for account status validation.
- **Notification Emails**: Emails to receive error notifications.

## File Structure
```
woocommerce-erp-order-sync/
|-- admin/
|   |-- class-settings.php (Admin settings page)
|-- includes/
|   |-- class-rest-order-handler.php (Handles order sync on checkout completion)
|   |-- class-order-resend-handler.php (Handles manual order resends)
|   |-- handlers/
|   |   |-- class-checkout-validation-handler.php (Blocks checkout for on-hold accounts)
|-- woocommerce-erp-order-sync.php (Main plugin file)
```

## Hooks & Actions
### Actions
- `woocommerce_thankyou`: Triggers order sync upon successful checkout.
- `woocommerce_order_actions`: Adds "Resend Order To Xact" option.
- `woocommerce_order_action_asc_resend_xact_order_action`: Handles manual order resends.
- `woocommerce_after_checkout_validation`: Blocks checkout for on-hold accounts.

## Error Handling
- If an order fails to sync, an email notification is sent to the configured admin emails.
- Error messages are logged in the WooCommerce order notes.


### Product Sync

 ERP system to keep WooCommerce stock levels, pricing, product descriptions, and images up to date. It provides REST API endpoints for manual execution and scheduled WordPress cron jobs for automatic syncing.


== REST API Endpoints ==

Use these REST API endpoints to trigger specific imports:

| **Import Type**         | **Endpoint URL**                                       | **Method** |
|-------------------------|------------------------------------------------------|------------|
| **Stock Levels**        | `/wp-json/erp-sync/v1/sync-qty/`                     | `POST` |
| **Prices (Batch 1)**    | `/wp-json/erp-sync/v1/sync-prices/`                  | `POST` |
| **Prices (Batch 2)**    | `/wp-json/erp-sync/v1/sync-prices-batch-2/`          | `POST` |
| **Descriptions**        | `/wp-json/erp-sync/v1/sync-descriptions/`            | `POST` |
| **Missing Images**      | `/wp-json/erp-sync/v1/sync-images/`                  | `POST` |
| **Run All Imports**     | `/wp-json/erp-sync/v1/sync-all/`                     | `POST` |

== How to Run Imports ==

### **1️⃣ Using cURL (Command Line)**
Run any of these commands:

```bash
curl -X POST /wp-json/erp-sync/v1/sync-qty/
curl -X POST /wp-json/erp-sync/v1/sync-prices/
curl -X POST /wp-json/erp-sync/v1/sync-prices-batch-2/
curl -X POST /wp-json/erp-sync/v1/sync-descriptions/
curl -X POST /wp-json/erp-sync/v1/sync-images/ -d skus=22,332,12345  
curl -X POST /wp-json/erp-sync/v1/sync-all/

```

### **2️⃣ Using WP All Import**
Add this function in WP All Import **Function Editor**:

```php
function run_erp_sync($endpoint) {
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

// Run all imports after WP All Import finishes
run_erp_sync('sync-all');
```

### **3️⃣ Using WordPress Dashboard**
1. **Go to `WordPress Admin > ERP Sync`**
2. **Select the import you want to run**
3. **Click ‘Run Sync’**
4. **Check error logs for any failed imports**

== WP Cron Jobs ==

This plugin automatically schedules cron jobs:

| **Import Type**       | **Cron Hook**             | **Schedule** |
|----------------------|-------------------------|-------------|
| **Stock Levels**    | `erp_sync_qty_cron`     | Every 30 minutes |
| **Prices (Batch 1)** | `erp_sync_prices_cron` | Every hour |
| **Prices (Batch 2)** | `erp_sync_prices_cron` | Every hour |
| **Descriptions**    | `erp_sync_descriptions_cron` | Every 2 hours |
| **Missing Images**  | `erp_sync_images_cron`  | Every 3 hours |

To manually run a cron job:
```bash
wp cron event run erp_sync_qty_cron
wp cron event run erp_sync_prices_cron
wp cron event run erp_sync_descriptions_cron
wp cron event run erp_sync_images_cron
```

== Error Logs & Debugging ==

The plugin logs errors in:
```
/wp-content/plugins/erp-woocommerce-sync/logs/error_log.txt
```

To check errors:
1. **Go to `WordPress Admin > ERP Sync`**  
2. **See the error log section**  
3. **Manually rerun failed imports**  

## Support
For support or contributions, contact:
- **Primary Contact**: lawkunchi@yahoo.com
- **Author**: Lawrence Chibondo

## License
GPL-2.0+ (https://www.gnu.org/licenses/gpl-2.0.html)



