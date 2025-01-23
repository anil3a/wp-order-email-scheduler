<?php
/**
 * Plugin Name: AP Wordpress Custom Email Scheduler
 * Description: Sends a custom email to the customer X hours after the order is paid and set to "Processing". Admin can configure the email template, delay and test email to a specific order.
 * Version: 1.1
 * Author: Anil Prajpati
 * Author URI: https://anil3a.wordpress.com
 */

if (!defined('ABSPATH')) exit;

// Activation hook to create default options
register_activation_hook(__FILE__, 'apwpscheduler_scheduler_activate');

function apwpscheduler_scheduler_activate() {
    add_option('apwpscheduler_email_delay', 2); // Default 2 hours
    add_option('apwpscheduler_email_subject', 'Your Order is Being Processed!');
    add_option('apwpscheduler_email_body', 'Dear {customer_name},<br><br>Your order {order_id} is being processed. Total amount: {order_total}.<br><br>Thank you for choosing AP WP Order email scheduler!');
}

// Schedule email when order status is set to "Processing"
add_action('woocommerce_order_status_processing', 'apwpscheduler_schedule_email', 10, 1);

function apwpscheduler_schedule_email($order_id) {
    if (!$order_id) return;

    // Get the delay from settings
    $delay = get_option('apwpscheduler_email_delay', 2); // Default 2 hours
    wp_schedule_single_event(time() + $delay * HOUR_IN_SECONDS, 'apwpscheduler_send_email', array($order_id));
}
// Send the email
add_action('apwpscheduler_send_email', 'apwpscheduler_send_email_handler');

function apwpscheduler_send_email_handler($order_id) {
    apwpscheduler_send_email($order_id);
}

function apwpscheduler_send_email($order_id) {
    $order = wc_get_order($order_id);

    if ($order) {
        $subject = get_option('apwpscheduler_email_subject', 'Your Order is Being Processed!');
        $body = get_option('apwpscheduler_email_body', 'Dear {customer_name},<br><br>Your order {order_id} is being processed.');

        $search = [
            '{customer_name}',
            '{customer_email}',
            '{order_id}',
            '{order_total}',
            '{order_date}',
            '{billing_address}',
            '{shipping_address}',
            '{items}',
            '{payment_method}',
            '{shipping_method}'
        ];

        $items_html = '';
        foreach ($order->get_items() as $item_id => $item) {
            $product_name = $item->get_name();
            $product_quantity = $item->get_quantity();
            $product_total = $item->get_total();
            $items_html .= "$product_quantity x $product_name - " . wc_price($product_total) . "<br>";
        }

        $replace = [
            $order->get_billing_first_name(),
            $order->get_billing_email(),
            $order->get_id(),
            $order->get_formatted_order_total(),
            wc_format_datetime($order->get_date_created()),
            $order->get_formatted_billing_address(),
            $order->get_formatted_shipping_address() ?: 'N/A',
            $items_html,
            $order->get_payment_method_title(),
            $order->get_shipping_method()
        ];

        $body = str_replace($search, $replace, $body);

        $to = $order->get_billing_email();
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $body, $headers);
    }
}

// Admin settings menu
add_action('admin_menu', 'apwpscheduler_add_admin_menu');

function apwpscheduler_add_admin_menu() {
    add_menu_page(
        'AP-WP Order Email Scheduler',
        'AP Email Scheduler',
        'manage_options',
        'apwpscheduler-scheduler',
        'apwpscheduler_settings_page'
    );
}

// AJAX search handler
add_action('wp_ajax_apwpscheduler_search_order', 'apwpscheduler_search_order_ajax_handler');

function apwpscheduler_search_order_ajax_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to access this resource.');
    }

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        wp_send_json_error('Order not found.');
    }

    $response = [
        'order_id' => $order->get_id(),
        'order_number' => $order->get_order_number(),
        'order_date' => wc_format_datetime($order->get_date_created()),
        'order_status' => wc_get_order_status_name($order->get_status()),
        'billing_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
    ];
    
    $response_html = "
       <table class='apwpscheduler-order-details'>
            <tr>
                <th>Order Number</th>
                <td>{$response['order_number']}</td>
            </tr>
            <tr>
                <th>Order ID</th>
                <td>{$response['order_id']}</td>
            </tr>
            <tr>
                <th>Order Date</th>
                <td>{$response['order_date']}</td>
            </tr>
            <tr>
                <th>Order Status</th>
                <td>{$response['order_status']}</td>
            </tr>
            <tr>
                <th>Billing Name</th>
                <td>{$response['billing_name']}</td>
            </tr>
            <tr>
                <th>Billing Email</th>
                <td>{$order->get_billing_email()}</td>
            </tr>
            <tr>
                <td colspan='2' class='action-cell'>
                    <button id='apwpscheduler_send_email_button' class='button-primary' data-order-id='{$response['order_id']}'>Send Email to Customer</button>
                </td>
            </tr>
        </table>
        <div class='apwpscheduler-email-info'>
            This email will be sent to: <strong>{$order->get_billing_email()}</strong>
        </div>
    ";
    
    wp_send_json_success($response_html);
}

// Settings page content
function apwpscheduler_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings
    if (isset($_POST['apwpscheduler_save_settings'])) {
        update_option('apwpscheduler_email_delay', intval($_POST['apwpscheduler_email_delay']));
        update_option('apwpscheduler_email_subject', sanitize_text_field($_POST['apwpscheduler_email_subject']));
        update_option('apwpscheduler_email_body', wp_kses_post($_POST['apwpscheduler_email_body']));
        echo '<div class="updated"><p>Settings saved!</p></div>';
    }

    // Get current settings
    $delay = get_option('apwpscheduler_email_delay', 2);
    $subject = get_option('apwpscheduler_email_subject', 'Your Order is Being Processed!');
    $body = get_option('apwpscheduler_email_body', 'Dear {customer_name},<br><br>Your order {order_id} is being processed.');
    ?>

    <div class="wrap">
        <h1>AP WP Order Email Scheduler Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="apwpscheduler_email_delay">Email Delay (hours)</label></th>
                    <td><input name="apwpscheduler_email_delay" type="number" id="apwpscheduler_email_delay" value="<?php echo esc_attr($delay); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="apwpscheduler_email_subject">Email Subject</label></th>
                    <td><input name="apwpscheduler_email_subject" type="text" id="apwpscheduler_email_subject" value="<?php echo esc_attr($subject); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="apwpscheduler_email_body">Email Body</label></th>
                    <td>
                        <textarea name="apwpscheduler_email_body" id="apwpscheduler_email_body" rows="10" class="large-text"><?php echo esc_textarea($body); ?></textarea>
                        <p class="description">Use the following placeholders:</p>
                        <ul>
                            <li><code>{customer_name}</code> - Customer's first name</li>
                            <li><code>{customer_email}</code> - Customer's email address</li>
                            <li><code>{order_id}</code> - Order ID</li>
                            <li><code>{order_total}</code> - Order total amount</li>
                            <li><code>{order_date}</code> - Order date</li>
                            <li><code>{billing_address}</code> - Billing address</li>
                            <li><code>{shipping_address}</code> - Shipping address</li>
                            <li><code>{items}</code> - List of ordered items</li>
                            <li><code>{payment_method}</code> - Payment method</li>
                            <li><code>{shipping_method}</code> - Shipping method</li>
                        </ul>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings', 'primary', 'apwpscheduler_save_settings'); ?>
        </form>

        <hr>

        <h2>Search for an Order to manually send an email</h2>
        <input type="number" id="apwpscheduler_order_search_input" placeholder="Enter Order ID" class="regular-text">
        <button id="apwpscheduler_order_search_button" class="button-primary">Search</button>
        <div id="apwpscheduler_order_search_result" style="margin-top: 20px;"></div>

        <style>
            .apwpscheduler-order-details {
                margin-top: 20px;
                border-collapse: collapse;
                width: 100%;
                font-family: Arial, sans-serif;
            }
            .apwpscheduler-order-details th, .apwpscheduler-order-details td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .apwpscheduler-order-details th {
                background-color: #f4f4f4;
                font-weight: bold;
                text-align: left;
            }
            .apwpscheduler-order-details tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .apwpscheduler-order-details tr:hover {
                background-color: #f1f1f1;
            }
            .apwpscheduler-order-details .action-cell {
                text-align: center;
            }
            .apwpscheduler-email-info {
                margin-top: 10px;
                font-style: italic;
                color: #555;
            }
        </style>

        <script>
            jQuery(document).ready(function ($) {
                $('#apwpscheduler_order_search_button').on('click', function () {
                    const orderId = $('#apwpscheduler_order_search_input').val();
                    if (!orderId) {
                        alert('Please enter an Order ID.');
                        return;
                    }

                    $.post(ajaxurl, { action: 'apwpscheduler_search_order', order_id: orderId }, function (response) {
                        if (response.success) {
                            $('#apwpscheduler_order_search_result').html(`<p style="">${response.data}</p>`);
                        } else {
                            $('#apwpscheduler_order_search_result').html(`<p style="color: red;">${response.data}</p>`);
                        }
                    });
                });
                // Send email to customer
                $(document).on('click', '#apwpscheduler_send_email_button', function () {
                    const orderId = $(this).data('order-id');
                    if (!orderId) {
                        alert('Order ID is missing.');
                        return;
                    }

                    $.post(ajaxurl, { action: 'apwpscheduler_send_email_ajax', order_id: orderId }, function (response) {
                        if (response.success) {
                            $('#apwpscheduler_order_search_result').append(`<p style="color: green; font-weight: bold;">${response.data}</p><br><br>`);
                        } else {
                            $('#apwpscheduler_order_search_result').append(`<p style="color: red;">${response.data}</p>`);
                        }
                    });
                });
            });
        </script>
    </div>
    <?php
}

// AJAX handler to send email for a specific order
add_action('wp_ajax_apwpscheduler_send_email_ajax', 'apwpscheduler_send_email_ajax_handler');

function apwpscheduler_send_email_ajax_handler() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to perform this action.');
    }

    $order_id = intval($_POST['order_id']);
    if (!$order_id) {
        wp_send_json_error('Invalid Order ID.');
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Order not found.');
    }

    // Call the existing function to send the email
    apwpscheduler_send_email($order_id);

    wp_send_json_success('Email sent successfully to the customer for Order ID ' . $order_id);
}
