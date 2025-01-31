<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class APWP_Email_Variables
{

    private static $instance;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get available dynamic variables and their descriptions.
     *
     * @return array
     */
    public static function get_variables()
    {
        return [
            '{order_id}'         => 'The unique ID of the order.',
            '{order_number}'     => 'The WooCommerce order number.',
            '{customer_name}'    => 'Customer’s full name (billing details).',
            '{customer_email}'   => 'Customer’s billing email address.',
            '{order_date}'       => 'The order date.',
            '{order_total}'      => 'Total amount for the order.',
            '{billing_address}'  => 'Customer’s billing address.',
            '{shipping_address}' => 'Customer’s shipping address.',
            '{items_list}'       => 'List of items purchased in the order.',
            '{payment_method}'   => 'Payment method used for the order.',
            '{shipping_method}'  => 'Shipping method used for the order.',
        ];
    }

    /**
     * Replace dynamic variables in a template.
     *
     * @param string $template The email template (subject or body).
     * @param WC_Order $order The WooCommerce order object.
     * @return string
     */
    public static function replace_variables($template, $order)
    {
        if (!$order instanceof WC_Order) {
            return $template;
        }

        $variables = [
            '{order_id}'         => $order->get_id(),
            '{order_number}'     => $order->get_order_number(),
            '{customer_name}'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '{customer_email}'   => $order->get_billing_email(),
            '{order_date}'       => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
            '{order_total}'      => wc_price($order->get_total()),
            '{billing_address}'  => $order->get_formatted_billing_address() ?: '',
            '{shipping_address}' => $order->get_formatted_shipping_address() ?: '',
            '{items_list}'       => self::format_items_list($order),
            '{payment_method}'   => $order->get_payment_method_title(),
            '{shipping_method}'  => $order->get_shipping_method(),
        ];

        // Replace variables in the template.
        return strtr($template, $variables);
    }

    /**
     * Format the list of items purchased in the order.
     *
     * @param WC_Order $order
     * @return string
     */
    private static function format_items_list($order)
    {
        $items_html = '<style>.tt-scheduler-order-items{width:100%;border:1px solid #c3c3c3;border-collapse:collapse;}'.
            '.tt-scheduler-order-items th{border:1px solid #c3c3c3;}'.
            '.tt-scheduler-order-items td{border:1px solid #c3c3c3;text-align:right;}'.
            '.tt-scheduler-order-items td:first-child{text-align:left;}'.
            '</style>'.
            '<table class="wp-list-table tt-scheduler-order-items">'.
            '<thead><tr><th>Product</th><th>Quantity</th><th>Total</th></tr></thead>'.
            '<tbody>';
        foreach ($order->get_items() as $item_id => $item) {
            $product_name = $item->get_name();
            $product_quantity = $item->get_quantity();
            $product_total = $item->get_total();
            $items_html .= "<tr><td>$product_name</td><td>$product_quantity</td><td>" . wc_price($product_total) . "</td></tr>";
        }
        $items_html .= '</tbody></table>';
        return $items_html;
    }
}
