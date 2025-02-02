<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Class APWP_Scheduler_Core
 * 
 * The Plugin Core class.
 * 
 * This class is responsible for core workflows.
 * 
 * @since      3.0.0
 * @package    WP_Order_Email_Scheduler/Classes
 * @author     Anil Prajapati <anilprz3@gmail.com>
 */
class APWP_Scheduler_Core
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
        $this->includes();
        $this->init_hooks();
    }

    private function includes()
    {
        require_once APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler-admin.php';
        require_once APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler-emails.php';
        require_once APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler-email-variables.php';
    }

    private function init_hooks()
    {
        // Initialize admin settings.
        APWP_Scheduler_Admin::instance();

        // Initialize email functionality.
        APWP_Scheduler_Emails::instance();

        // Initialize email variables.
        APWP_Scheduler_Email_Variables::instance();
    }
}
