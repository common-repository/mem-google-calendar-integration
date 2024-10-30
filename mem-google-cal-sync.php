<?php
// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Name: MEM - Google Calendar Integration
 * Description: Syncronise your events to your Google Calendar.
 * Version:     1.0.1
 * Date:        1st December 2021
 * Author: Mobile Events Manager
 * Author URI:  https://www.mobileeventsmanager.co.uk
 * Text Domain: mem-gcal-integration
 * Domain Path: /languages
 * Copyright   Copyright (c) 2021 Dan Porter, Jack Mawhinney
 */

// Set MEM Google Calendar Integration Version
if (!defined('TMEM_GCAL_VERSION') ) {
    define( 'TMEM_GCAL_VERSION', "1.0.1");
}

    // Set minimum MEM version required
if (!defined('TMEM_REQUIRED') ) {
    define( 'TMEM_REQUIRED', "1.3.9");
}

    // Set minimum required PHP version
if (!defined('TMEM_PHP_MIN') ) {
    define( 'TMEM_PHP_MIN', "5.6");
}
    // Set minimum required WP version
if (!defined('TMEM_WP_MIN') ) {
    define( 'TMEM_WP_MIN', "4.7");
}

    // Define path to MEM Google Calendar Integration Plugin
if (!defined('TMEM_GCAL_PATH') ) {
    define( 'TMEM_GCAL_PATH', untrailingslashit( dirname( __FILE__ ) ) );
}
    // Define URL to MEM Google Calendar Integration Plugin
if (!defined('TMEM_GCAL_URL') ) {
    define( 'TMEM_GCAL_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );
}
    // Define MEM Google Calendar Integration Plugin Basename
if (!defined('TMEM_GCAL_BASENAME') ) {
    define( 'TMEM_GCAL_BASENAME', plugin_basename( __FILE__ ) );
}
    // Define MEM Google Calendar Integration Plugin FILENAME
if (!defined('TMEM_GCAL_FILE') ) {
    define( 'TMEM_GCAL_FILE', __FILE__ );
}


class MEM_GCal_Sync {
    private static $instance;

public static function instance() {

            if ( ! self::$instance ) {
                self::$instance = new MEM_GCal_Sync();
                self::$instance->google = new MEM_GCal_Init();
            }

            return self::$instance;
        } // __construct
}

// Load Plugin Files
require TMEM_GCAL_PATH . '/includes/mem-gcal-init.php';

// Start Plugin
function MEM_GCal()   {
    return MEM_GCal_Sync::instance();
} // MEM_Gcal

add_action( 'plugins_loaded', 'MEM_GCal' );

register_activation_hook( TMEM_GCAL_FILE,  array('MEM_GCal_Init','mem_gcal_activate' ) );
?>