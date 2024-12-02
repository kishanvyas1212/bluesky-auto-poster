<?php
add_action('bluesky_cron_job_hook', 'bluesky_process_scheduled_posts');
require_once 'posting_to_bluesky.php'; 
function bluesky_process_scheduled_posts() {
    global $wpdb;
    error_log("cron is called 1");

    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';
    $current_time = current_time('timestamp');  // Get current time in seconds
    error_log($current_time);
    // Fetch posts scheduled to be posted
    $scheduled_posts = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE posted_status = 0 AND UNIX_TIMESTAMP(schedule_time) <= %d",
            $current_time
        )
        
    );
    
    foreach ($scheduled_posts as $post) {
        
        $media_url = $post->attachment_url;
        $selected_networks = $post->network_id;
        error_log($selected_networks);
        $message = $post->message;
        $schedule_time = $post->schedule_time;
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
        }else if(!empty($media_url)){
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
        exit;
        $post_id = intval($post->id);
        $deleted = $wpdb->delete($table_name, ['id' => $post_id]);




    }
}

?>