<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Class APWP_Scheduler_Emails
 * 
 * The Plugin Email triggers.
 * 
 * This class is responsible for handling email actions.
 * 
 * @since      3.0.0
 * @package    WP_Order_Email_Scheduler/Classes
 * @author     Anil Prajapati <anilprz3@gmail.com>
 */
class APWP_Scheduler_Emails
{
    private static $instance;
    private $manual = false;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('wp_ajax_apwp_send_email', [$this, 'ajax_send_email']);
        add_action('wp_ajax_apwp_search_order', [$this, 'ajax_get_orders_by_order_id']);
    }

    public function send_email($order_id, $order = null, $manual = false)
    {
        $this->manual = $manual;

        if (empty($order)) {
            if(empty($order_id)){
                return false;
            }
            $order = wc_get_order($order_id);
        } else {
            if (!$order instanceof WC_Order) {
                return false;
            }
            $order_id = $order->get_id();
        }
        if (empty($order)) {
            return false;
        } else {
            if (!$order instanceof WC_Order) {
                return false;
            }
        }

        $to_email = $order->get_billing_email();
        if(empty($to_email) || !is_email($to_email)){
            return false;
        }

        $default_template = get_option('apwp_default_email_template', '1');
        $email_subject = get_option("apwp_email_template_{$default_template}_subject", 'Default email subject');
        $subject = APWP_Scheduler_Email_Variables::replace_variables($email_subject, $order);

        $log_entry = $this->log_email_attempt($order_id, $to_email, $subject);

        if (empty($log_entry) ) {
            return false;
        }

        $email_body = get_option("apwp_email_template_{$default_template}_body", 'Default email body.');
        $body = APWP_Scheduler_Email_Variables::replace_variables($email_body, $order);

        // Prepare headers.
        $from_name = get_option('apwp_email_from_name', get_bloginfo('name'));
        $from_email = get_option('apwp_email_from_email', get_bloginfo('admin_email'));
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

        $sent = wp_mail($to_email, $subject, $body, $headers);
        $log_entry->status = 'failed';
        $this->log_email_attempt($order_id, $to_email, $subject, $log_entry, $sent ? 'sent' : 'failed');

        return $sent;
    }

    /**
     * AJAX handler for sending a test email to a specific order.
     */
    public function ajax_send_email()
    {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have permission to perform this action.');
        }

        // Check for the nonce
        check_ajax_referer('apwp_scheduler_nonce', 'security');

        // Get the order ID from the request
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        if (empty($order_id) || (int) $order_id < 1) {
            wp_send_json_error('Invalid order ID.');
        }

        // Fetch the order
        $order = wc_get_order($order_id);
        if (empty($order)) {
            wp_send_json_error('Order not found.');
        }

        // Send the email
        $email_sent = $this->send_email($order_id, $order, true);

        $to = $order->get_billing_email();

        if ($email_sent) {
            wp_send_json_success('Email sent successfully to ' . $to . '.');
        } else {
            wp_send_json_error('Failed to send the email.');
        }
    }

    /**
     * Log an email attempt for a specific order.
     * 
     * @param int $order_id The order ID.
     * @param string $to_email The email address to which the email is being sent.
     * @param object $log_entry The existing log entry for the email, if any.
     * @param string $status The status of the email attempt.
     * 
     * @return object|bool  if bool no log OR Object then log found
     * @author Anil Prajapati <anilprz3@gmail.com>
     */
    private function log_email_attempt($order_id, $to_email, $subject = '', $log_entry = null, $status = 'processing')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'apwp_customemail_log';

        $existing_log = [];
        if (empty($log_entry)) {
            // Check if email was already sent or max attempts reached
            $existing_log = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE order_id = %d AND email = %s", 
                $order_id, 
                $to_email
            ));
        } else {
            $existing_log = $log_entry;
        }

        $apwp_email_attempts = get_option('apwp_email_attempts', 3);

        if (!empty($existing_log)) {

            // skip processing log status
            if ($existing_log->status === 'processing' && $this->manual === false) {
                return false;
            }
            else if ($existing_log->status === 'sent' && $this->manual === false) {
                return false;
            }
            else if ($existing_log->status === 'failed') {
                if ($this->manual === false) {
                    if ($existing_log->attempts >= $apwp_email_attempts) {
                        return false;
                    }
                }
            } else if ($this->manual === false) {
                // Any other status is not acceptable for auto processing
                return false;
            }

            // Update attempt count
            $wpdb->update(
                $table_name,
                [
                    'status'       => $status,
                    'attempts'     => $existing_log->attempts + 1,
                    'subject'      => $subject,
                    'last_attempt_gmt' => current_time('mysql', true),
                    'result'       => ($this->manual ? 'Manually attempted' : 'Auto scheduled attempt'),
                    'user_id'      => get_current_user_id(),
                    'user'         => get_current_user_id() ? get_userdata(get_current_user_id())->user_login : 'System',
                ],
                ['id' => $existing_log->id]
            );
            return $existing_log;
        }

        $wpdb->insert(
            $table_name,
            [
                'order_id'          => $order_id,
                'email'             => $to_email,
                'status'            => $status,
                'attempts'          => 1,
                'subject'           => '',
                'result'            => ($this->manual ? 'Manually attempted' : 'Auto scheduled attempt'),
                'last_attempt_gmt'  => current_time('mysql', true),
                'created_at_gmt'    => current_time('mysql', true),
                'user_id'           => get_current_user_id(),
                'user'              => get_current_user_id() ? get_userdata(get_current_user_id())->user_login : 'System',
            ]
        );

        $existing_log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE order_id = %d AND email = %s", 
            $order_id, 
            $to_email
        ));
        $existing_log->attempts = 0;
        return $existing_log;
    }

    public function ajax_get_orders_by_order_id()
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

        $order_url = admin_url('post.php?post=' . $order_id . '&action=edit'); // WooCommerce order edit page
        $invoice_number = $order->get_meta('_wcpdf_invoice_number');

        if (empty($invoice_number)) {
            $invoice_number = $order->get_order_number();
        }

        $response_html = "
           <table class='apwp-order-details'>
                <tr>
                    <th>Order ID</th>
                    <td>
                        <a href='{$order_url}' target='_blank'>#{$response['order_id']}</a>
                </tr>
                <tr>
                    <th>Order Number</th>
                    <td>{$invoice_number}</td>
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

}
