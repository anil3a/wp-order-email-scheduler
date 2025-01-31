<?php 

class APWP_Scheduler
{
    public function __construct()
    {
        add_action('apwp_scheduler_cron_event', [$this, 'process_scheduled_emails']);
    }

    public function process_scheduled_emails()
    {
        $email_enabled = get_option('apwp_email_enabled', 'yes');
        if ($email_enabled !== 'yes') {
            return;
        }

        $wp_datetime = current_datetime();
        $delay_hours = (int) get_option('apwp_email_delay', -2);
        $wp_datetime = $wp_datetime->modify( $delay_hours .' hours');

        // DB datetime
        $timezone_in_db = 'UTC';
        $cutoff_time = $wp_datetime->setTimezone(new DateTimeZone($timezone_in_db))->getTimestamp();

        $args = [
            'status' => 'processing',
            'date_created' => '>' . $cutoff_time,
            'limit' => 100,
        ];
        $orders = wc_get_orders($args);

        if (empty($orders)) {
            return;
        }

        $email_handler = new APWP_Scheduler_Emails();

        foreach ($orders as $order)
        {
            $order_id = $order->get_id();
            $email_handler->send_email($order_id, $order);
        }
    }
}
