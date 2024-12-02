<?php
/*
Plugin Name: Bluesky Auto Poster
Description: Automatically post to Bluesky from WordPress.
Version: 1.0
Author: Kishan Vyas
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly
// Add custom cron schedule for every minute.
add_filter('cron_schedules', 'bluesky_custom_cron_intervals');
function bluesky_custom_cron_intervals($schedules) {
    $schedules['every_minute'] = [
        'interval' => 60, // 1 minute
        'display' => __('Every Minute'),
    ];
    return $schedules;
}

function create_bluesky_tables() {
    global $wpdb;
    $table_name1 = $wpdb->prefix . 'bluesky_networks';
    $table_name2 = $wpdb->prefix . 'bluesky_scheduled_posts';
    $table_name3 = $wpdb->prefix . 'bluesky_posted_posts';

    // Check if the tables exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name1'") != $table_name1) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name1 (
            id INT AUTO_INCREMENT PRIMARY KEY,
            network_name VARCHAR(255) NOT NULL,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
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
            message TEXT NOT NULL,
            attachment_url VARCHAR(255),
            schedule_time DATETIME NOT NULL,
            posted_at DATETIME NOT NULL
        ) $charset_collate;";
        $wpdb->query($sql);
    }
}
register_activation_hook(__FILE__, 'create_bluesky_tables');
register_activation_hook(__FILE__, 'bluesky_schedule_cron_job');
function bluesky_schedule_cron_job() {
    error_log("this one");
    if (!wp_next_scheduled('bluesky_cron_job_hook')) {
        wp_schedule_event(time(), 'every_minute', 'bluesky_cron_job_hook');
    }
    
}
register_deactivation_hook(__FILE__, 'bluesky_clear_cron_job');
function bluesky_clear_cron_job() {
    $timestamp = wp_next_scheduled('bluesky_cron_job_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'bluesky_cron_job_hook');
    }
}


// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/posting_to_bluesky.php';
require_once plugin_dir_path(__FILE__) . 'includes/cron.php';
// Enqueue scripts and styles
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
        '1.0'
    );
}

add_action('admin_enqueue_scripts', 'bluesky_enqueue_styles');
function enqueue_media_uploader_scripts() {
    wp_enqueue_media(); // Enqueue WordPress Media Uploader
    wp_enqueue_script('media-uploader-js', plugin_dir_url(__FILE__) . 'js/media-uploader.js', ['jquery'], null, true);
}
add_action('admin_enqueue_scripts', 'enqueue_media_uploader_scripts');