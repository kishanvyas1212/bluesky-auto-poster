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
add_action('wp_ajax_fetch_bluesky_posts', 'bluesky_fetch_posts');
// This function will generate the refresh token and store to the database 
function generate_refresh_token($username,$password){
    $login_url = "https://bsky.social/xrpc/com.atproto.server.createSession";
    $login_data = json_encode([
        "identifier" => $username,
        "password" => $password
    ]);

    $login_headers = [
        "Content-Type: application/json"
    ];

    // Initialize cURL session for login
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $login_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $login_headers);

    $login_response = curl_exec($ch);
    curl_close($ch);
    // Decode the login response
    $login_data = json_decode($login_response, true);
    
    // Check if the login was successful and extract JWT tokens
    if (isset($login_data['accessJwt']) && isset($login_data['refreshJwt'])) {
        $did = $login_data['did'];
        $access_jwt = $login_data['accessJwt'];
        $refresh_jwt = $login_data['refreshJwt'];
        return ['refresh_token'=>$refresh_jwt,
        'did'=>$did];
    }
    else return 0;

}
function bluesky_delete_post($network_id,$post_url){
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {; 
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }
    global $wpdb;
    $table_name2 = $wpdb->prefix . 'bluesky_networks';
    $query = $wpdb->prepare("SELECT * FROM $table_name2 WHERE id = %d", $post->network_id);
    $find_user = $wpdb->get_results($query);
    $did = $find_user[0]->did;
    $password = $find_user[0]->password;
    $refreshtoken = $find_user[0]->refreshJWT;
    $access_jwt = refresh_accress_token($refresh_token);
    error_log(print_r($access_jwt,true));
    $access_jwt = $access_jwt['token'];
    $url = "https://bsky.social/xrpc/com.atproto.repo.deleteRecord";
    $record_key =0;
    if (preg_match('/\/([^\/]+)$/', $post_url, $matches)) {
        $record_key = $matches[1]; // This will be "3kld4r62lcs2k"
        echo "Extracted Post ID: " . $record_key;
    }
    // Prepare the body
    error_log($record_key);
    $body = json_encode([
        'repo' => $did,
        'collection' => 'app.bsky.feed.post',
        'rkey' => $record_key, // Record key to delete
    ]);

    // Initialize cURL
    $ch = curl_init($url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $access_jwt",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    
    // Execute request and fetch response
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check response
    if ($http_code !== 200) {
        return [
            'error' => 'Failed to delete the post.',
            'http_code' => $http_code,
            'response' => $response,
        ];
    }

    // Decode and return response
    return json_decode($response, true);
}
function refresh_accress_token($refresh_jwt){
    
    $url = "https://bsky.social/xrpc/com.atproto.server.refreshSession";

    // Set up headers
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $refresh_jwt
    ];

    // Initialize cURL
    $ch = curl_init($url);

    // Configure cURL options
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // Add headers
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
    curl_setopt($ch, CURLOPT_POST, true);           // HTTP POST method
    // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([])); // Send an empty JSON body

    // Execute the request
    $response = curl_exec($ch);

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check for errors
    if (curl_errno($ch)) {
        curl_close($ch);
        return ['error' => 'Curl error: ' . curl_error($ch)];
    }

    curl_close($ch);

    // Decode the response
    $decoded_response = json_decode($response, true);

    // Check for successful response
    if ($http_status === 200 && isset($decoded_response['accessJwt'])) {
        error_log($decoded_response['accessJwt']);
        return ['token'=> $decoded_response['accessJwt'],'status'=> 1]; // Return accessJwt
    } else {
        return [
            'error' => 'Request failed',
            'status' => 0,
            'response' => $decoded_response
        ];
    }
}

function add_bluesky_network() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
        return;
    }
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_networks';

    $network_name = sanitize_text_field($_POST['network_name']);
    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);
    error_log($network_name);
    if (!$network_name || !$username || !$password) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }
    
    $response = generate_refresh_token($username,$password);
    $refreshJWT = $response['refresh_token'];
    $did = $response['did'];

    if($refreshJWT!=0){ 
    $result = $wpdb->insert($table_name, [
        'network_name' => $network_name,
        'username' => $username,
        'password' => $password,
        'refreshJWT' =>$refreshJWT,
        'did' =>$did
    ]);
    }
    else{
        wp_send_json_error(['message' => 'Can not generated refresh token please try again']);
    }
    error_log(print_r($result,true));
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
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
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
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
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
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }

    // Fetch data from request
    $network_id = isset($_POST['network_id']) ? intval($_POST['network_id']) : 0;
    $network_name = isset($_POST['network_name']) ? sanitize_text_field($_POST['network_name']) : '';
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
    $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
    if (!$network_name || !$username || !$password) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }
    
    $response = generate_refresh_token($username,$password);
    $refreshJWT = $response['refresh_token'];
    $did = $response['did'];

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
            'refreshJwt' => $refreshJWT,
        ],
        ['id' => $network_id],
        ['%s', '%s', '%s','%s'],
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
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
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
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }

    // Retrieve form data
    $message = sanitize_text_field($_POST['message']);
    $media_url = esc_url_raw($_POST['media_url']);
    $selected_networks = $_POST['networks']; // Array of network IDs
    $selected_networks_name = $_POST['networksnames'];
    $post_type = sanitize_text_field($_POST['post_type']);
    $schedule_time = sanitize_text_field($_POST['schedule_time']); // Only for scheduled posts
    if(!empty($media_url)){
        if (filesize($media_url) > 1000000) {
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
         
        $i = 0;
        foreach($selected_networks as $net){
add_action('wp_ajax_fetch_bluesky_posts', 'bluesky_fetch_posts');
add_action('wp_ajax_nopriv_fetch_bluesky_posts', 'bluesky_fetch_posts'); // For non-logged-in users, if needed

            $net = intval($net);
            $table_name2 = $wpdb->prefix . 'bluesky_networks'; 
            $query = $wpdb->prepare("SELECT * FROM $table_name2 WHERE id = %d", $net);
            $posted_posts = $wpdb->get_results($query);
            $refresh_jwt = $posted_posts[0]->refreshJWT;
            $username = $posted_posts[0]->username;
            $password = $posted_posts[0]->password;
            
            $access_token = refresh_accress_token($refresh_jwt);
            if($access_token['status']==0){
                $response = generate_refresh_token($username,$password);
                $refresh_token = $response['refresh_token'];

                if($refresh_token!=0){
                $access_token = refresh_accress_token($refresh_jwt);
                $table_name = $wpdb->prefix . 'bluesky_networks';

                // Update the network details
                $updated = $wpdb->update(
                    $table_name,
                    [
                        'network_name' => $network_name,
                        'username' => $username,
                        'password' => $password,
                        'refreshJwt' => $refreshJWT,
                    ],
                    ['id' => $net],
                    ['%s', '%s', '%s','%s'],
                    ['%d']
                );
                }
                else{
                    error_log("User name or password is not proper for");
                    error_log($net);

                }
            }
            $access_token = $access_token['token'];
            

            if(empty($media_url)) {
            $response = text_poster($access_token,$message,$username);
            }
            else if(!empty($media_url)) {
                $file_extension = pathinfo($media_url, PATHINFO_EXTENSION);
                $mime_types = [
                    'mp4' => 'video/mp4',
                ];
                
                $mime_type = $mime_types[strtolower($file_extension)] ?? null;
                
                if ($mime_type === 'video/mp4') {
                    $response = bluesky_post_with_video($access_token, $username, $message, $media_url);
                }else {
                $response = bluesky_post_with_attachment($access_token,$username, $message, $media_url);
                }
            }
            $response= $response['response'];
            $response_data = json_decode($response, true);
            if (isset($response_data['uri'])) {
                // Extract the post ID from the URI using a regular expression
                preg_match('/\/([^\/]+)$/', $response_data['uri'], $matches);
        
                // If a match is found, display the custom post URL
                if (isset($matches[1])) {
                    $post_id = $matches[1];
                    $post_url = "https://bsky.app/profile/{$username}/post/{$post_id}";
                }
            }else {
                $post_url =0;
            }
                               
            $selected_networks_names = $selected_networks_name[$i];
            $i = $i + 1;
            $table_name1 = $wpdb->prefix .'bluesky_posted_posts';
            $wpdb->insert($table_name1, [
                'network_id' => $net,
                'network_name' => json_encode($selected_networks_names),
                'response' => $post_url,
                'actual_response' =>json_encode($response),
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

function toggle_schedule_status() {
    global $wpdb;
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }
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
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }
    
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';

    $post_id = intval($_POST['post_id']);

    $deleted = $wpdb->delete($table_name, ['id' => $post_id]);

    wp_send_json(['success' => $deleted !== false]);
}

function fetch_scheduled_post_details() {
    // Check if post_id is provided
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {; 
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }
    
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
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {; 
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }
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
function bluesky_fetch_posts() {
    global $wpdb;
    // Validate nonce
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'hi dear you can not access it :)'], 403);
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'bluesky_nonce')) {; 
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }
    $table_name = $wpdb->prefix . 'bluesky_posted_posts';
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $network = isset($_POST['network']) ? sanitize_text_field($_POST['network']) : '';
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $posts_per_page = 10;

    $offset = ($page - 1) * $posts_per_page;

    // Base query
    $query = "SELECT * FROM $table_name WHERE 1=1";

    // Search filter
    if (!empty($search)) {
        $query .= $wpdb->prepare(" AND message LIKE %s", '%' . $wpdb->esc_like($search) . '%');
    }

    // Network filter
    if (!empty($network)) {
        $query .= $wpdb->prepare(" AND network_name = %s", $network);
    }

    // Order by latest
    $query .= " ORDER BY id DESC LIMIT %d OFFSET %d";
    $query = $wpdb->prepare($query, $posts_per_page, $offset);

    // Fetch posts
    $posts = $wpdb->get_results($query);

    // Count total posts for pagination
    $total_query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
    if (!empty($search)) {
        $total_query .= $wpdb->prepare(" AND message LIKE %s", '%' . $wpdb->esc_like($search) . '%');
    }
    if (!empty($network)) {
        $total_query .= $wpdb->prepare(" AND network_name = %s", $network);
    }
    $total_posts = $wpdb->get_var($total_query);

    // Build HTML for posts
    ob_start();
    if ($posts) {
        foreach ($posts as $post) {
            $network = $post->network_name;
            $network = str_replace('"', '',$network);
            $response = $post->response;
            

            ?>
            
            <tr>
                <td><?php echo esc_html($post->id); ?></td>
                <td><?php echo esc_html($post->message); ?></td>
                <td>
                    <?php if ($post->attachment_url): ?>
                        <?php if (strpos($post->attachment_url, '.mp4') !== false): ?>
                            <a href="<?php echo esc_url($post->attachment_url); ?>" target="_blank">View Video</a>
                        <?php else: ?>
                            <a href="<?php echo esc_url($post->attachment_url); ?>" target="_blank">View Image</a>
                        <?php endif; ?>
                    <?php else: ?>
                        No Attachment
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($network); ?></td>
                <td>
                    <?php 
                    if ($response == 0) {
                        $display_response = $post->actual_response;
                        echo esc_html($display_response);
                    } else { 
                    ?>
                        <a href="<?php echo esc_url($post->response); ?>" target="_blank">View Post</a>
                    <?php 
                    } 
                    ?>
                </td>
                <td>
                    <a href="#" class="delete-post" data-id="<?php echo esc_attr($post->id); ?>">Delete</a>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="5">No posts found.</td></tr>';
    }
    $posts_html = ob_get_clean();

    // Build pagination HTML
    $total_pages = ceil($total_posts / $posts_per_page);
    ob_start();
    if ($total_pages > 1) {
        for ($i = 1; $i <= $total_pages; $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<a href='#' class='pagination-link $active' data-page='$i'>$i</a> ";
        }
    }
    $pagination_html = ob_get_clean();

    // Return response
    wp_send_json_success([
        'posts_html' => $posts_html,
        'pagination_html' => $pagination_html,
        'total_pages'=>$total_pages
    ]);
}

?>