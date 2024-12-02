<?php
// require_once plugin_dir_path(__FILE__) . 'includes/posting_to_bluesky.php';
require_once 'posting_to_bluesky.php'; 
add_action('wp_ajax_add_bluesky_network', 'add_bluesky_network');
add_action('wp_ajax_nopriv_add_bluesky_network', 'add_bluesky_network');

// Delete Network Action (already in your file)
add_action('wp_ajax_delete_bluesky_network', 'delete_bluesky_network');
add_action('wp_ajax_nopriv_delete_bluesky_network', 'delete_bluesky_network');

// Fetch Network Details Action
add_action('wp_ajax_get_network_details', 'get_network_details');
add_action('wp_ajax_nopriv_get_network_details', 'get_network_details');
add_action('wp_ajax_update_bluesky_network', 'update_bluesky_network');
add_action('wp_ajax_nopriv_update_bluesky_network', 'update_bluesky_network');
add_action('wp_ajax_toggle_bluesky_network_status', 'toggle_bluesky_network_status');
add_action('wp_ajax_bluesky_submit_post', 'bluesky_submit_post');
add_action('wp_ajax_toggle_schedule_status', 'toggle_schedule_status');
add_action('wp_ajax_delete_scheduled_post', 'delete_scheduled_post');
add_action('wp_ajax_fetch_scheduled_post_details', 'fetch_scheduled_post_details');


function add_bluesky_network() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_networks';

    $network_name = sanitize_text_field($_POST['network_name']);
    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);

    if (!$network_name || !$username || !$password) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    $result = $wpdb->insert($table_name, [
        'network_name' => $network_name,
        'username' => $username,
        'password' => $password,
    ]);

    if ($result) {
        wp_send_json_success(['message' => 'Network added successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to add network.']);
    }
}

function delete_bluesky_network() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
        return;
    }

    global $wpdb;
    $network_id = intval($_POST['network_id']);
    $table_name = $wpdb->prefix . 'bluesky_networks';

    $result = $wpdb->delete($table_name, ['id' => $network_id]);

    if ($result) {
        wp_send_json_success(['message' => 'Network deleted successfully!']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete network.']);
    }
}
function get_network_details() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
        return;
    }

    // Fetch network ID from request
    $network_id = isset($_POST['network_id']) ? intval($_POST['network_id']) : 0;

    if (!$network_id) {
        wp_send_json_error(['message' => 'Invalid network ID.']);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_networks';

    // Retrieve the network details
    $network = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $network_id), ARRAY_A);

    if (!$network) {
        wp_send_json_error(['message' => 'Network not found.']);
        return;
    }

    // Send network details as JSON
    wp_send_json_success($network);
}
function update_bluesky_network() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
        return;
    }

    // Fetch data from request
    $network_id = isset($_POST['network_id']) ? intval($_POST['network_id']) : 0;
    $network_name = isset($_POST['network_name']) ? sanitize_text_field($_POST['network_name']) : '';
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';

    if (!$network_id || !$network_name || !$username || !$password) {
        wp_send_json_error(['message' => 'All fields are required.']);
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_networks';

    // Update the network details
    $updated = $wpdb->update(
        $table_name,
        [
            'network_name' => $network_name,
            'username' => $username,
            'password' => $password,
        ],
        ['id' => $network_id],
        ['%s', '%s', '%s'],
        ['%d']
    );

    if ($updated === false) {
        wp_send_json_error(['message' => 'Failed to update the network.']);
        return;
    }

    // Send success response
    wp_send_json_success(['message' => 'Network updated successfully.']);
}


function toggle_bluesky_network_status() {
    global $wpdb;

    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error('Invalid nonce.');
    }

    // Get data from AJAX
    $network_id = intval($_POST['network_id']);
    $status = intval($_POST['status']);

    if ($network_id && in_array($status, [1, 2])) {
        $table_name = $wpdb->prefix . 'bluesky_networks';

        // Update the status in the database
        $updated = $wpdb->update(
            $table_name,
            ['status' => $status],
            ['id' => $network_id],
            ['%d'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Database update failed.');
        }
    } else {
        wp_send_json_error('Invalid data.');
    }
}

function bluesky_submit_post() {
    global $wpdb;

    // Validate nonce
    // if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_post_nonce')) {
    //     wp_send_json_error(['message' => 'Invalid nonce.']);
    // }

    // Retrieve form data
    $message = sanitize_text_field($_POST['message']);
    $media_url = esc_url_raw($_POST['media_url']);
    $selected_networks = $_POST['networks']; // Array of network IDs
    $selected_networks_name = $_POST['networksnames'];
    $post_type = sanitize_text_field($_POST['post_type']);
    $schedule_time = sanitize_text_field($_POST['schedule_time']); // Only for scheduled posts
    if(!empty($media_url)){
        if (filesize($attachment_path) > 1000000) {
            wp_send_json_error(['message' => 'Attachment size too large. Maximum allowed size is 1MB.']);
            return [
                'error' => 'Attachment size too large. Maximum allowed size is 1MB.'
            ];
        }
    }
    if (empty($message)) {
        wp_send_json_error(['message' => 'Message cannot be empty.']);
    }

    if (strlen($message) > 300) {
        wp_send_json_error(['message' => 'Message exceeds 300 characters.']);
    }

    if (empty($selected_networks)) {
        wp_send_json_error(['message' => 'Please select at least one network.']);
    }

    // Determine post type (publish now or schedule)
    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';
    if ($post_type === 'schedule') {
        if (empty($schedule_time) || strtotime($schedule_time) <= time()) {
            wp_send_json_error(['message' => 'Please select a valid future date and time.']);
        }

        // Insert into scheduled posts table
        $wpdb->insert($table_name, [
            'message' => $message,
            'attachment_url' => $media_url,
            'network_id' => json_encode($selected_networks),
            'network_name' => json_encode($selected_networks_name),
            'schedule_time' => $schedule_time,
            'posted_status' => 0,
            'created_at' => current_time('mysql')
        ]);
        wp_send_json_success(['message' => 'Post scheduled successfully!']);
    } else {
        // Publish immediately (direct API call can be implemented here)
        if(empty($media_url)){ 
        $i = 0;
        foreach($selected_networks as $net){
            $net = intval($net);
            $table_name2 = $wpdb->prefix . 'bluesky_networks'; 
            $query = $wpdb->prepare("SELECT * FROM $table_name2 WHERE id = %d", $net);
            $posted_posts = $wpdb->get_results($query);
            $username = $posted_posts[0]->username;
            $password = $posted_posts[0]->password;

            $response = text_poster($username, $password, $message);
            $response= $response['response'];
                               
            $selected_networks_names = $selected_networks_name[$i];
            $i = $i + 1;
            $table_name1 = $wpdb->prefix .'bluesky_posted_posts';
            $wpdb->insert($table_name1, [
                'network_id' => $net,
                'network_name' => json_encode($selected_networks_names),
                'response' => $response,
                'scheduled_post_id' => 0,
                'message' => $message,
                'attachment_url' => $media_url,
                'schedule_time' => $schedule_time,
                'posted_at' => current_time('mysql')
            ]);
        }
    }
        else if(!empty($media_url)){
            $i = 0;
            foreach($selected_networks as $net){
                $net = intval($net);
                $table_name2 = $wpdb->prefix . 'bluesky_networks'; 
                $query = $wpdb->prepare("SELECT * FROM $table_name2 WHERE id = %d", $net);
                $posted_posts = $wpdb->get_results($query);
                $username = $posted_posts[0]->username;
                $password = $posted_posts[0]->password;
                $response = bluesky_post_with_attachment($username, $password, $message,$media_url);
                $response= $response['response'];
                                   
                $selected_networks_names = $selected_networks_name[$i];
                $i = $i + 1;
                $table_name1 = $wpdb->prefix .'bluesky_posted_posts';
                $wpdb->insert($table_name1, [
                    'network_id' => $net,
                    'network_name' => json_encode($selected_networks_names),
                    'response' => $response,
                    'scheduled_post_id' => 0,
                    'message' => $message,
                    'attachment_url' => $media_url,
                    'schedule_time' => $schedule_time,
                    'posted_at' => current_time('mysql')
                ]);
            }
        }
        wp_send_json_success(['message' => 'Post published successfully!']);
    }
}

function toggle_schedule_status() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';

    $post_id = intval($_POST['post_id']);
    $status = intval($_POST['status']);

    $updated = $wpdb->update(
        $table_name,
        ['posted_status' => $status],
        ['id' => $post_id]
    );

    wp_send_json(['success' => $updated !== false]);
}
function delete_scheduled_post() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';

    $post_id = intval($_POST['post_id']);

    $deleted = $wpdb->delete($table_name, ['id' => $post_id]);

    wp_send_json(['success' => $deleted !== false]);
}

function fetch_scheduled_post_details() {
    // Check if post_id is provided
    if (!isset($_POST['post_id'])) {
        wp_send_json_error(array('message' => 'Post ID is required.'));
        error_log('Post ID is required.');
        return;
    }

    $post_id = intval($_POST['post_id']);
    global $wpdb;

    // Fetch post details
    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';
    $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $post_id));

    if (!$post) {
        wp_send_json_error(array('message' => 'Post not found.'));
        return;
    }

    // Fetch active networks
    $networks = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bluesky_networks WHERE status = 1");

    // Send response with post and networks data
    wp_send_json_success(array(
        'post' => $post,
        'networks' => $networks
    ));

    wp_die();
}

add_action('wp_ajax_update_scheduled_post', 'update_scheduled_post');

function update_scheduled_post() {
    if (!isset($_POST['post_id'])) {
        wp_send_json_error(array('message' => 'Post ID is required.'));
        return;
    }

    $post_id = intval($_POST['post_id']);
    $message = sanitize_text_field($_POST['message']);
    $network_id = intval($_POST['network_id']);
    $schedule_time = sanitize_text_field($_POST['schedule_time']);
    $attachment_url = esc_url_raw($_POST['attachment_url']);

    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';

    // Update the post details in the database
    $result = $wpdb->update(
        $table_name,
        array(
            'message' => $message,
            'network_id' => $network_id,
            'schedule_time' => $schedule_time,
            'attachment_url' => $attachment_url,
        ),
        array('id' => $post_id),
        array('%s', '%d', '%s', '%s'),
        array('%d')
    );

    if ($result !== false) {
        wp_send_json_success(array('message' => 'Post updated successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to update post.'));
    }

    wp_die(); // Always call this at the end of the function to terminate the request properly
}

?>