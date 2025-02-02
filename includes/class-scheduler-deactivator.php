<?php

/**
 * Clas APWP_Scheduler_Deactivator
 * 
 * The Plugin Deactivator class.
 * 
 * This class is responsible for de-registering plugin from WP.
 * 
 * @since      3.0.0
 * @package    WP_Order_Email_Scheduler/Classes
 * @author     Anil Prajapati <anilprz3@gmail.com>
 */
class APWP_Scheduler_Deactivator
{
    public static function deactivate()
    {
        wp_clear_scheduled_hook('apwp_scheduler_cron_event');
    }
}