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

## Support
For support or contributions, contact:
- **Primary Contact**: lawkunchi@yahoo.com
- **Author**: Lawrence Chibondo

## License
GPL-2.0+ (https://www.gnu.org/licenses/gpl-2.0.html)

