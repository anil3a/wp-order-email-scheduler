<?php
/**
 * Plugin Name: AP Wordpress Custom Email Scheduler
 * Description: Sends a custom email to the customer X hours after the order is paid and set to "Processing". Admin can configure the email template, delay and test email to a specific order.
 * Version: 1.1
 * Author: Anil Prajpati
 * Author URI: https://anil3a.wordpress.com
 */

 if (!defined('ABSPATH')) exit;

// Define constants for plugin paths.
define('APWP_CUSTOMEMAIL_SCHEDULER_VERSION', '1.0.0');
define('APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload required files.
require_once APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler-core.php';
require_once APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler-activator.php';
require_once APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler-deactivator.php';
require_once APWP_CUSTOMEMAIL_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler.php';

// Initialize the plugin.
add_action('plugins_loaded', function () {
    APWP_Scheduler_Core::instance();
});

register_activation_hook(__FILE__, ['APWP_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['APWP_Deactivator', 'deactivate']);

new APWP_Scheduler();