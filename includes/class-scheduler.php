<?php 

/**
 * Class APWP_Scheduler
 * 
 * The scheduler class.
 * 
 * This class is responsible for scheduling the emails.
 * 
 * @since      3.0.0
 * @package    WP_Order_Email_Scheduler/Classes
 * @author     Anil Prajapati <anilprz3@gmail.com>
 */
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
            return false;
        }

        $wp_datetime = current_datetime();
        $delay_hours = (int) get_option('apwp_email_delay', 2);
        $apwp_email_order_offset = get_option('apwp_email_order_offset', 0);
        $wp_datetime = $wp_datetime->modify( (($delay_hours+$apwp_email_order_offset)* -1) .' hours');
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
            return false;
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
