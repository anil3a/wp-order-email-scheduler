<?php


class APWP_Deactivator
{
    public static function deactivate()
    {
        wp_clear_scheduled_hook('apwp_scheduler_cron_event');
    }
}