# AP Wordpress Custom Email Scheduler Plugin

## Overview

The **AP Wordpress Custom Email Scheduler** WordPress plugin provides an easy way to schedule custom emails to customers after their WooCommerce order status is set to "Processing." It allows administrators to manage email templates, set email delay times, and test email functionality directly from the WordPress admin interface. This plugin is fully customizable and includes AJAX-powered features for searching orders and sending test emails.

## Features

- Schedule emails to customers based on WooCommerce order status.
- 3 Templates to choose default email template to send email with.
- Customize the email subject and body, with support for dynamic placeholders.
- Search for specific orders using an AJAX-powered search bar.
- Test email functionality by sending emails for specific orders directly from the settings page.
- Configure email scheduling delay times (e.g., send an email 2 hours after order status changes).
- Styled admin interface for easy management.
- **Live Orders** to see what orders are within the range and their status, attempts, and all details
- **Email log** to see email sent log, can include processing, sent and failed.
   - `processing` if system to trying to send and will not be picked up if it runs sgain.
   - `sent` If email is sent succesfully
   - `failed`  If email sending is failed


## Installation

1. Download the plugin zip file or clone the repository.
2. Log in to your WordPress admin dashboard.
3. Navigate to **Plugins > Add New > Upload Plugin**.
4. Upload the zip file, then click **Install Now**.
5. Activate the plugin from the **Plugins** page.

## Configuration

1. Navigate to **Settings > APWP Scheduler** in the WordPress admin dashboard.
2. Configure the following options:
   - **Email Delay (in Hours):** Set the delay time for sending emails after the order is marked as "Processing."
   - **Email Subject:** Customize the email subject using dynamic placeholders.
   - **Email Body:** Customize the email body with available placeholders.
3. Save changes using the "Save Changes" button.

## Dynamic Placeholders

You can use the following placeholders in your email templates to dynamically insert order data:

| Placeholder          | Description                             |
| -------------------- | --------------------------------------- |
| `{order_id}`         | The unique ID of the order.             |
| `{order_number}`     | The WooCommerce order number.           |
| `{customer_name}`    | Customer’s full name (billing details). |
| `{customer_email}`   | Customer’s billing email address.       |
| `{order_date}`       | The order date.                         |
| `{order_total}`      | Total amount for the order.             |
| `{billing_address}`  | Customer’s billing address.             |
| `{shipping_address}` | Customer’s shipping address.            |
| `{items_list}`       | List of items purchased in the order.   |
| `{payment_method}`   | Payment method used for the order.      |

## Order Search and Test Email

1. Use the **Search for an Order** section to search for specific orders by order ID or customer name.
2. View order details, including order number, status, date, customer name, and email.
3. Use the **Send Email to Customer** button to manually trigger the email for the selected order.

## How It Works

1. When an order’s status changes to "Processing," the plugin schedules an email to be sent after the configured delay time.
2. The email uses the template specified in the settings page.
3. Emails are sent via the default WordPress email system (ensure your email system is configured properly).

## Notes

- This plugin assumes WooCommerce is installed and activated.
- Ensure your email system is properly configured to avoid delivery issues.
- The plugin supports dynamic placeholders for maximum flexibility in email customization.

## Changelog

## Version 3.0.0
- Class names and usage fixes
- Added more settings to control email and order
- Cron fixes and improvements
- Added Invoice number support used by plugin for meta key `_wcpdf_invoice_number`

## Version 2.0.0
- Added multiple email templates
- Ability to change subject per email templates
- Ability to disable cron schedule with enable/disable
- Ability to Log email attempts to prevent duplicate and see records
- Added Two new pages Live orders and Email log
- Live Orders will show orders in that offset date range and also email log status
- Email Log will show log for email template with some more infomration about sending email
- Fix wp cron schedule event for this auto schedule and set to hourly to run

### Version 1.0.0

- Initial release.
- Email scheduling after order status changes to "Processing."
- Fully customizable email templates with dynamic placeholders.
- AJAX-powered order search and test email functionality.

## Support

For issues, feature requests, or contributions, please contact the developer or submit an issue on the repository.

## License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

