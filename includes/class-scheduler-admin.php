<?php
if (!defined('ABSPATH')) {
    exit;
}

class APWP_Scheduler_Admin
{
    private static $instance;

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_settings_page()
    {
        add_menu_page(
            'Email Scheduler by APWP',
            'APWP Scheduler',
            'manage_options',
            'apwp-scheduler',
            [$this, 'settings_page_content'],
            'dashicons-email-alt',
            90
        );
        add_submenu_page(
            'apwp-scheduler',
            'Live Orders',
            'Live Orders',
            'manage_options',
            'apwp-live-orders',
            [$this, 'render_live_orders_page']
        );
        add_submenu_page(
            'apwp-scheduler',
            'Email Log',
            'Email Log',
            'manage_options',
            'apwp-email-log',
            [$this, 'render_email_log_page']
        );
    }

    public function register_settings()
    {
        register_setting('apwp_scheduler_settings', 'apwp_email_enabled');
        register_setting('apwp_scheduler_settings', 'apwp_email_delay');
        register_setting('apwp_scheduler_settings', 'apwp_email_template_1_subject');
        register_setting('apwp_scheduler_settings', 'apwp_email_template_1_body');
        register_setting('apwp_scheduler_settings', 'apwp_email_template_2_subject');
        register_setting('apwp_scheduler_settings', 'apwp_email_template_2_body');
        register_setting('apwp_scheduler_settings', 'apwp_email_template_3_subject');
        register_setting('apwp_scheduler_settings', 'apwp_email_template_3_body');
        register_setting('apwp_scheduler_settings', 'apwp_default_email_template');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script(
            'apwp-scheduler-admin',
            plugins_url( 'assets/scheduler-admin.js',  dirname(__FILE__)),
            ['jquery'],
            time(),
            true
        );
        wp_localize_script('apwp-scheduler-admin', 'apwp_scheduler', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('apwp_scheduler_nonce'),
        ]);
        wp_enqueue_style(
            'apwp-scheduler-admin-style',
            plugins_url( 'assets/scheduler-admin.css',  dirname(__FILE__)),
            [],
            time()
        );
    }

    public function settings_page_content()
    {
        $variables = APWP_Email_Variables::get_variables();
        $subject = 'Your Order is being processed! ';
        $body = '<br><br>Your order {order_id} is being processed. Total amount: {order_total}.<br><br>Thank you for choosing WP Scheduler APWP!';
        $email_enabled = get_option('apwp_email_enabled', 'yes');
        $email_delay = get_option('apwp_email_delay', 2);

        $template_1_subj = get_option('apwp_email_template_1_subject', 'Template 1: '. $subject);
        $template_1_body = get_option('apwp_email_template_1_body', 'Dear {customer_name} from Template 1,'. $body);

        $template_2_subj = get_option('apwp_email_template_2_subject', 'Template 2: '. $subject);
        $template_2_body = get_option('apwp_email_template_2_body', 'Dear {customer_name} from Template 2,'. $body);

        $template_3_subj = get_option('apwp_email_template_3_subject', 'Template 3: '. $subject);
        $template_3_body = get_option('apwp_email_template_3_body', 'Dear {customer_name} from Template 3,'. $body);

        $default_template = get_option('apwp_default_email_template', '1');
        ?>
        <div class="wrap">
            <h1>Email Scheduler Settings by APWP</h1>
            <form method="post" action="options.php">
                <?php settings_fields('apwp_scheduler_settings'); ?>
                <table class="form-table">
                    <!-- Enable/Disable Emails -->
                    <tr>
                        <th scope="row"><label for="apwp_email_enabled">Enable Scheduled Emails</label></th>
                        <td>
                            <select id="apwp_email_enabled" name="apwp_email_enabled">
                                <option value="yes" <?php selected($email_enabled, 'yes'); ?>>Yes</option>
                                <option value="no" <?php selected($email_enabled, 'no'); ?>>No</option>
                            </select>
                            <p class="description">Set to "No" to disable all scheduled emails. Manual emails can still be sent.</p>
                        </td>
                    </tr>
    
                    <!-- Email Delay -->
                    <tr>
                        <th scope="row"><label for="apwp_email_delay">Email Delay (Hours)</label></th>
                        <td>
                            <input type="number" id="apwp_email_delay" name="apwp_email_delay" value="<?php echo esc_attr($email_delay); ?>" min="-100" max="100" />
                            <p class="description">Enter the delay (in hours) after the order status changes to "Processing" before sending the email.</p>
                        </td>
                    </tr>
    
                    <!-- Default Email Template -->
                    <tr>
                        <th scope="row"><label for="apwp_default_email_template">Default Email Template</label></th>
                        <td>
                            <select id="apwp_default_email_template" name="apwp_default_email_template">
                                <option value="1" <?php selected($default_template, '1'); ?>>Template 1</option>
                                <option value="2" <?php selected($default_template, '2'); ?>>Template 2</option>
                                <option value="3" <?php selected($default_template, '3'); ?>>Template 3</option>
                            </select>
                            <p class="description">Select the default email template used for scheduled emails.</p>
                        </td>
                    </tr>
    
                    <!-- Email Templates -->
                    <tr>
                        <th scope="row"><label for="apwp_email_template_1_subject">Email Template 1</label></th>
                        <td>
                            <input type="text" id="apwp_email_template_1_subject" name="apwp_email_template_1_subject" value="<?php echo esc_attr($template_1_subj); ?>" style="width: 100%;" placeholder="Email Subject">
                            <br /><br />
                            <textarea id="apwp_email_template_1_body" name="apwp_email_template_1_body" rows="5" style="width: 100%;"><?php echo esc_textarea($template_1_body); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="apwp_email_template_2_subject">Email Template 2</label></th>
                        <td>
                            <input type="text" id="apwp_email_template_2_subject" name="apwp_email_template_2_subject" value="<?php echo esc_attr($template_2_subj); ?>" style="width: 100%;" placeholder="Email Subject">
                            <br /><br />
                            <textarea id="apwp_email_template_2_body" name="apwp_email_template_2_body" rows="5" style="width: 100%;"><?php echo esc_textarea($template_2_body); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="apwp_email_template_3_subject">Email Template 3</label></th>
                        <td>
                            <input type="text" id="apwp_email_template_3_subject" name="apwp_email_template_3_subject" value="<?php echo esc_attr($template_3_subj); ?>" style="width: 100%;" placeholder="Email Subject">
                            <br /><br />
                            <textarea id="apwp_email_template_3_body" name="apwp_email_template_3_body" rows="5" style="width: 100%;"><?php echo esc_textarea($template_3_body); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="apwp_email_template_tags">Available Tags</label>
                        </th>
                        <td>
                            <p class="description">Use the following placeholders:</p>
                            <table class="widefat striped">
                                <thead>
                                    <tr>
                                        <th>Variable</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($variables as $variable => $description): ?>
                                        <tr>
                                            <td><code><?php echo esc_html($variable); ?></code></td>
                                            <td><?php echo esc_html($description); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>

                <!-- Save Button -->
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
                </p>
            </form>

            <?php $this->get_email_tester(); ?>

        </div>
        <?php
    }

    private function get_email_tester()
    {
        ?>
        <hr>

        <h2>Search for an Order to manually send an email</h2>
        <input type="number" id="apwp_order_search_input" placeholder="Enter Order ID" class="regular-text">
        <button id="apwp_order_search_button" class="button-primary">Search</button>
        <div id="apwp_order_search_result" style="margin-top: 20px;">
            <br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
        </div>
        <br /><br /><br /><br />

        <style>
            .apwp-order-details {
                margin-top: 20px;
                border-collapse: collapse;
                width: 100%;
                font-family: Arial, sans-serif;
            }
            .apwp-order-details th, .apwp-order-details td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .apwp-order-details th {
                background-color: #f4f4f4;
                font-weight: bold;
                text-align: left;
            }
            .apwp-order-details tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .apwp-order-details tr:hover {
                background-color: #f1f1f1;
            }
            .apwp-order-details .action-cell {
                text-align: center;
            }
            .apwp-email-info {
                margin-top: 10px;
                font-style: italic;
                color: #555;
            }
        </style>
        <?php
    }

    public function render_live_orders_page()
    {
        global $wpdb;
        $d_format = get_option('date_format');
        $t_format = get_option('time_format');
        $dt_format = (!empty($d_format) ? $d_format : 'Y-m-d') . ' ' . (!empty($t_format) ? $t_format : 'h:i a');

        $wp_datetime = current_datetime();
        $current_datetime = $wp_datetime->format($dt_format);
        $delay_hours = (int) get_option('apwp_email_delay', 2);
        $wp_datetime = $wp_datetime->modify( $delay_hours .' hours');
        $cutoff_datetime = $wp_datetime->format($dt_format);
        
        $timezone_in_db = 'UTC';
        $cutoff_time = $wp_datetime->setTimezone(new DateTimeZone($timezone_in_db))->getTimestamp();

        $args = [
            'status' => 'processing',
            'date_created' => '>' . $cutoff_time,
            'limit' => 100,
        ];
        $orders = wc_get_orders($args);

        $table_name = $wpdb->prefix . 'apwp_customemail_log';
        $dbemail_logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at_gmt DESC LIMIT 50");

        $emaillogs_order = [];
        foreach ($dbemail_logs as $log) {
            $emaillogs_order[$log->order_id] = $log;
        }

        echo '<div class="wrap">';
        echo '<h1>Live Orders</h1>';

        echo '<p>Showing orders in "Processing" status with offset <strong>' . $delay_hours . ' hours</strong>.</p>';

        // Show Current datetime and cutoff datetime
        echo '<p><strong>Current datetime: </strong><span style="font-size:16px;">'. $current_datetime .'</span></p>';
        echo '<p><strong>Cutoff datetime: </strong><span style="font-size:16px;">'. $cutoff_datetime .'</span></p>';
        echo '<br />';

        if (empty($orders)) {
            echo '<p>No orders found that match the criteria.</p>';
        } else {
            echo '<table class="widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Order ID</th>';
            echo '<th>Order Number</th>';
            echo '<th>Customer</th>';
            echo '<th>Email</th>';
            echo '<th>Date Created</th>';
            echo '<th>Status</th>';
            echo '<th>Log Description</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($orders as $order) {
                $_emaillog_description = '';
                $_ord_id = $order->get_id();

                if (isset($emaillogs_order[$_ord_id])) {
                    $_emaillog_description = '
                        <div class="apwp-email-info">
                            <strong>Email sending attempted x<span style="font-size: 20px;">'. esc_html($emaillogs_order[$_ord_id]->attempts) .'</span> time(s)</strong><br>
                            <strong>Status:</strong> ' . esc_html($emaillogs_order[$_ord_id]->status) . '<br>
                            <strong>Last Attempt:</strong> ' . get_date_from_gmt($emaillogs_order[$_ord_id]->last_attempt_gmt, $dt_format) . '<br>
                            <strong>Result:</strong> ' . esc_html($emaillogs_order[$_ord_id]->result) . '<br>
                        </div>
                    ';
                }

                echo '<tr>';
                echo '<td>' . esc_html($_ord_id) . '</td>';
                echo '<td>' . esc_html($order->get_order_number()) . '</td>';
                echo '<td>' . esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '</td>';
                echo '<td>' . esc_html($order->get_billing_email()) . '</td>';
                echo '<td>' . esc_html($order->get_date_created()->date('Y-m-d h:i a')) . '</td>';
                echo '<td>' . esc_html($order->get_status()) . '</td>';
                echo '<td>' . $_emaillog_description . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        }
    
        echo '</div>';
    }

    public function render_email_log_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'apwp_customemail_log';
    
        $logs = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY last_attempt_gmt DESC LIMIT 100"
        );

        $d_format = get_option('date_format');
        $t_format = get_option('time_format');
        $dt_format = (!empty($d_format) ? $d_format : 'Y-m-d') . ' ' . (!empty($t_format) ? $t_format : 'h:i a');
    
        ?>
        <div class="wrap">
            <h1>APWP Email Log</h1>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Last Attempt</th>
                        <th>Subject</th>
                        <th>Actioned by</th>
                        <th>Result</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log->order_id); ?></td>
                            <td><?php echo esc_html($log->email); ?></td>
                            <td><?php echo esc_html(ucfirst($log->status)); ?></td>
                            <td><?php echo esc_html($log->attempts); ?></td>
                            <td>
                                <?php echo (!empty($log->last_attempt_gmt) 
                                    ? get_date_from_gmt($log->last_attempt_gmt, $dt_format) : '-');?>
                            </td>
                            <td><?php echo esc_html($log->subject); ?></td>
                            <td>
                                <?php
                                    echo esc_html($log->user);
                                    if (!empty($log->user_id)) {
                                        echo ' (ID: ' . esc_html($log->user_id) . ')';
                                    }
                                ?>
                            </td>
                            <td><?php echo esc_html($log->result); ?></td>
                            <td>
                                <?php echo (!empty($log->created_at_gmt)
                                    ? get_date_from_gmt($log->created_at_gmt, $dt_format) : '-'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    
}
