<?php
/*
Plugin Name: Bluesky Auto Poster
Description: Automatically post to Bluesky from WordPress.
Version: 1.0
Author: Kishan Vyas
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


if (!defined('ABSPATH')) exit; // Exit if accessed directly
// Add custom cron schedule for every minute.
add_filter('cron_schedules', 'bluesky_custom_cron_intervals');
function bluesky_custom_cron_intervals($schedules) {
    $schedules['every_five_minutes'] = [
        'interval' => 300, // 5 minute
        'display' => __('Every 5 Minutes'),
    ];
    return $schedules;
}

function create_bluesky_tables() {
    global $wpdb;
    $table_name1 = $wpdb->prefix . 'bluesky_networks';
    $table_name2 = $wpdb->prefix . 'bluesky_scheduled_posts';
    $table_name3 = $wpdb->prefix . 'bluesky_posted_posts';
    $table_name4 = $wpdb->prefix . 'bluesky_posts_reports';
    $table_logs = $wpdb->prefix . 'bluesky_logs'; 

    // Check if the tables exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name1'") != $table_name1) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name1 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            network_name VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            refreshJWT TEXT NOT NULL,
            did TEXT NOT NULL,
            avatar TEXT NOT NULL,
            status TINYINT(1) DEFAULT 1

        ) $charset_collate;";
        $wpdb->query($sql);
    }

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name2'") != $table_name2) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name2 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            network_id TEXT NOT NULL,
            network_name VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            attachment_url VARCHAR(255),
            schedule_time DATETIME NOT NULL,
            posted_status TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL
        ) $charset_collate;";
         $wpdb->query($sql);
    }

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name3'") != $table_name3) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name3 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            network_id INT NOT NULL,
            network_name VARCHAR(255) NOT NULL,
            scheduled_post_id INT NOT NULL,
            response TEXT,
            actual_response Text,
            message TEXT NOT NULL,
            attachment_url VARCHAR(255),
            schedule_time DATETIME NOT NULL,
            posted_at DATETIME NOT NULL
        ) $charset_collate;";
        $wpdb->query($sql);
    }
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_logs'") != $table_logs) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type INT NOT NULL,
            log_name TEXT NOT NULL,
            log_data TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        $wpdb->query($sql);
    }
}
register_activation_hook(__FILE__, 'create_bluesky_tables');
// Register a custom cron schedule globally
add_filter('cron_schedules', 'bluesky_custom_cron_schedules');
function bluesky_custom_cron_schedules($schedules) {
    // Retrieve the user-defined cron interval (default 5 minutes)
    $cron_interval = (int) get_option('bluesky_cron_time', 5);

    // Validate and convert to seconds
    if ($cron_interval < 1) {
        $cron_interval = 5; // Fallback to 5 minutes if invalid value is set
    }
    $interval_in_seconds = $cron_interval * 60;

    // Register the custom interval
    $schedules['bluesky_custom_interval'] = [
        'interval' => $interval_in_seconds,
        'display'  => sprintf(__('Every %d minutes'), $cron_interval),
    ];

    return $schedules;
}

// Register activation hook to set up the initial cron job
register_activation_hook(__FILE__, 'bluesky_schedule_cron_job');
function bluesky_schedule_cron_job() {
    // Ensure custom cron schedule is available
    add_filter('cron_schedules', 'bluesky_custom_cron_schedules');

    // Clear any existing scheduled events
    bluesky_clear_cron_job();

    // Schedule the cron job with the current settings
    if (!wp_next_scheduled('bluesky_cron_job_hook')) {
        wp_schedule_event(time(), 'bluesky_custom_interval', 'bluesky_cron_job_hook');
        error_log("Cron job scheduled with custom interval.");
    } else {
        error_log("Cron job already exists.");
    }
}

// Register deactivation hook to clear the cron job
register_deactivation_hook(__FILE__, 'bluesky_clear_cron_job');
function bluesky_clear_cron_job() {
    $timestamp = wp_next_scheduled('bluesky_cron_job_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'bluesky_cron_job_hook');
        error_log("Cron job cleared.");
    }
}

// Hook into settings update to reschedule cron dynamically
add_action('update_option_bluesky_cron_time', 'bluesky_on_cron_time_change', 10, 2);
function bluesky_on_cron_time_change($old_value, $new_value) {
    error_log("Settings updated: Old interval = $old_value, New interval = $new_value");

    // Clear existing scheduled events
    bluesky_clear_cron_job();

    // Schedule the cron job with the new settings
    bluesky_schedule_cron_job();
}
// Register a custom cron schedule globally for refreshing tokens
add_filter('cron_schedules', 'bluesky_custom_refresh_token_schedules');
function bluesky_custom_refresh_token_schedules($schedules) {
    // Retrieve the user-defined refresh token interval (default 12 hours)
    $refresh_token_interval = (int) get_option('bluesky_refresh_token_interval', 12);

    // Validate and convert to seconds (12 hours by default)
    if ($refresh_token_interval < 1) {
        $refresh_token_interval = 12; // Fallback to 12 hours if invalid value is set
    }
    $interval_in_seconds = $refresh_token_interval * 60 * 60; // Convert to seconds

    // Register the custom refresh token interval
    $schedules['bluesky_refresh_token_interval'] = [
        'interval' => $interval_in_seconds,
        'display'  => sprintf(__('Every %d hours'), $refresh_token_interval),
    ];

    return $schedules;
}

// Register activation hook to set up the initial cron job for token refresh
register_activation_hook(__FILE__, 'bluesky_schedule_refresh_token_cron_job');
function bluesky_schedule_refresh_token_cron_job() {
    // Ensure the custom cron schedule is available
    add_filter('cron_schedules', 'bluesky_custom_refresh_token_schedules');

    // Clear any existing scheduled events
    bluesky_clear_refresh_token_cron_job();

    // Schedule the refresh token cron job with the current settings
    if (!wp_next_scheduled('bluesky_refresh_token_hook')) {
        wp_schedule_event(time(), 'bluesky_refresh_token_interval', 'bluesky_refresh_token_hook');
        error_log("Token refresh cron job scheduled with custom interval.");
    } else {
        error_log("Token refresh cron job already exists.");
    }
}

// Register deactivation hook to clear the refresh token cron job
register_deactivation_hook(__FILE__, 'bluesky_clear_refresh_token_cron_job');
function bluesky_clear_refresh_token_cron_job() {
    $timestamp = wp_next_scheduled('bluesky_refresh_token_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'bluesky_refresh_token_hook');
        error_log("Token refresh cron job cleared.");
    }
}

// Hook into settings update to reschedule the refresh token cron job dynamically
add_action('update_option_bluesky_refresh_token_interval', 'bluesky_on_refresh_token_interval_change', 10, 2);
function bluesky_on_refresh_token_interval_change($old_value, $new_value) {
    error_log("Settings updated: Old interval = $old_value, New interval = $new_value");

    // Clear existing scheduled events
    bluesky_clear_refresh_token_cron_job();

    // Schedule the refresh token cron job with the new settings
    bluesky_schedule_refresh_token_cron_job();
}



// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/posting_to_bluesky.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron.php';
// Enqueue scripts and styles
function enqueue_view_posted_post() {
    $current_url = $_SERVER['REQUEST_URI'];
    $is_target_page = false;
    $is_target_page1=false;
    if (strpos($current_url, 'page=bluesky-view-posts') !== false) {
        $is_target_page = true;
    }
    if (strpos($current_url, 'page=bluesky-manage-posts') !== false) {
        $is_target_page1 = true;
    }
    if($is_target_page == true){
    // wp_enqueue_script('my-custom-script', plugins_url('js/my-script.js', __FILE__), array('jquery'));
    wp_enqueue_script('media-uploader-js', plugin_dir_url(__FILE__) . 'js/view_data.js', ['jquery'], null, true);
    }elseif($is_target_page1 == true){
    wp_enqueue_script('media-uploader-js', plugin_dir_url(__FILE__) . 'js/schedule_data.js', ['jquery'], null, true);
    } 
  }
add_action('admin_enqueue_scripts', 'enqueue_view_posted_post');

function bluesky_poster_enqueue_scripts() {
    wp_enqueue_script(
        'bluesky-poster-script',
        plugins_url('js/script.js', __FILE__),
        array('jquery'),
        '1.0',
        true
    );

    // Localize the script with nonce and AJAX URL
    wp_localize_script('bluesky-poster-script', 'blueskyPoster', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bluesky_nonce'), // Create a nonce
    ));
}
add_action('admin_enqueue_scripts', 'bluesky_poster_enqueue_scripts');
function bluesky_enqueue_styles() {
    // Enqueue your popup styles
    wp_enqueue_style(
        'bluesky-popup-styles',
        plugin_dir_url(__FILE__) . 'css/bluesky-popup-styles.css',
        array(),
        null
    );
}

add_action('admin_enqueue_scripts', 'bluesky_enqueue_styles');
add_action('wp_enqueue_scripts', 'bluesky_enqueue_styles');

function enqueue_media_uploader_scripts() {
    wp_enqueue_media(); // Enqueue WordPress Media Uploader
    wp_enqueue_script('media-uploader-js', plugin_dir_url(__FILE__) . 'js/media-uploader.js', ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'enqueue_media_uploader_scripts');