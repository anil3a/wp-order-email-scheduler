<?php

class APWP_Activator
{
    public static function activate()
    {
        // Call necessary setup functions
        self::create_email_log_table();

        if (!wp_next_scheduled('apwp_scheduler_cron_event')) {
            wp_schedule_event(time(), 'hourly', 'apwp_scheduler_cron_event');
        }
    }

    private static function create_email_log_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'apwp_customemail_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `order_id` BIGINT UNSIGNED NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `status` ENUM('processing', 'failed', 'sent') NOT NULL DEFAULT 'processing',
            `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
            `last_attempt_gmt` DATETIME DEFAULT NULL,
            `subject` VARCHAR(255) DEFAULT NULL,
            `result` VARCHAR(500) DEFAULT NULL,
            `user` VARCHAR(255) DEFAULT NULL,
            `user_id` BIGINT UNSIGNED DEFAULT NULL,
            `created_at_gmt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

