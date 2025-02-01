<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_apwp_search_order', 'apwp_ajax_search_order');
function apwp_ajax_search_order()
{
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
       <table class='apwp-order-details'>
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
                    <button id='apwp_send_email_button' class='button-primary' data-order-id='{$response['order_id']}'>Send Email to Customer</button>
                </td>
            </tr>
        </table>
        <div class='apwp-email-info'>
            This email will be sent to: <strong>{$order->get_billing_email()}</strong>
        </div>
    ";

    wp_send_json_success($response_html);
}
