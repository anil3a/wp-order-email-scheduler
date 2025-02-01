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
        $delay_hours = (int) get_option('apwp_email_delay', 2);
        $wp_datetime = $wp_datetime->modify( (($delay_hours+1)* -1) .' hours');        
        $timezone_in_db = 'UTC';
        $wp_datetime->setTime ( $wp_datetime->format("H"), 0, 0);
        $cutoff_time = $wp_datetime->setTimezone(new DateTimeZone($timezone_in_db))->getTimestamp();

        $args = [
            'status' => 'processing',
            'date_created' => '>' . $cutoff_time,
            'limit' => 50,
        ];
        $orders = wc_get_orders($args);

        if (empty($orders)) {
            return;
        }

        $email_handler = new APWP_Scheduler_Emails();

        $current_datetime_obj = current_datetime();
        foreach ($orders as $order)
        {
            $order_id = $order->get_id();
            $_ord_createdat_datetime = $order->get_date_created();
            $diff = $current_datetime_obj->diff($_ord_createdat_datetime);
            $diff_in_seconds = $diff->d * 24 * 3600 + $diff->h * 3600 + $diff->i * 60 + $diff->s;

            if ($diff_in_seconds < (($delay_hours) *60*60) ) {
                continue;
            }
            $email_handler->send_email($order_id, $order);
        }
        return true;
    }

}
