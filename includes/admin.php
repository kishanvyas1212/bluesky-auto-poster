<?php
add_action('admin_menu', 'bluesky_poster_menu');
function bluesky_poster_menu() {
    add_menu_page(
        'Bluesky Automated Poster',
        'Bluesky Poster',
        'manage_options',
        'bluesky-poster',
        'bluesky_poster_dashboard',
        'dashicons-megaphone',
        20
    );

    add_submenu_page(
        'bluesky-poster',
        'Manage Bluesky Networks',
        'Manage Networks',
        'manage_options',
        'bluesky-manage-networks',
        'bluesky_manage_networks'
    );

    add_submenu_page(
        'bluesky-poster',
        'Create Schedule Post',
        'Create Post',
        'manage_options',
        'bluesky-create-post',
        'bluesky_create_post_page'
    );

    add_submenu_page(
        'bluesky-poster',
        'Manage Schedule Posts',
        'Manage Posts',
        'manage_options',
        'bluesky-manage-posts',
        'bluesky_manage_posts'
    );

    add_submenu_page(
        'bluesky-poster',
        'View Posted Posts',
        'View Posts',
        'manage_options',
        'bluesky-view-posts',
        'bluesky_view_posts'
    );
}
function bluesky_poster_dashboard(){
    global $wpdb;

    $table_name1 = $wpdb->prefix . 'bluesky_networks';
    $table_name2 = $wpdb->prefix . 'bluesky_scheduled_posts';
    $table_name3 = $wpdb->prefix . 'bluesky_posted_posts';

    // Get total number of posted posts
    $total_posted_posts = $wpdb->get_var("SELECT COUNT(*) FROM $table_name3");

    // Get total number of scheduled posts
    $total_scheduled_posts = $wpdb->get_var("SELECT COUNT(*) FROM $table_name2 WHERE posted_status = 0");

    // Get total number of networks
    $total_networks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name1");

    // Display the data in a table
    ?>
    <div class="wrap">
        <h2>Bluesky Poster Dashboard</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Statistic</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Posted Posts</td>
                    <td><?php echo $total_posted_posts ? $total_posted_posts : 'No data found'; ?></td>
                </tr>
                <tr>
                    <td>Total Scheduled Posts</td>
                    <td><?php echo $total_scheduled_posts ? $total_scheduled_posts : 'No data found'; ?></td>
                </tr>
                <tr>
                    <td>Total Networks</td>
                    <td><?php echo $total_networks ? $total_networks : 'No data found'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php   
}
function bluesky_manage_networks() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bluesky_networks';
    
    // Fetch existing networks
    $networks = $wpdb->get_results("SELECT * FROM $table_name");
    
    ?>
    <div class="wrap">
        <h2>Manage Bluesky Networks</h2>
        <p><a href="#" class="button-primary" id="add-network-button">Add Network</a></p>
    
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Network ID</th>
                    <th>Network Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($networks) : ?>
                    <?php foreach ($networks as $network) : ?>
                        <tr>
                            <td><?php echo $network->id; ?></td>
                            <td><?php echo $network->network_name; ?></td>
                            <td>
                                <!-- Add a toggle switch for the status -->
                                <label class="switch">
                                    <input type="checkbox" class="toggle-status" data-id="<?php echo $network->id; ?>" <?php echo $network->status == 1 ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td>
                                <a href="#" class="edit-network" data-id="<?php echo $network->id; ?>">Edit</a> |
                                <a href="#" class="delete-network" data-id="<?php echo $network->id; ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="nodatafound">No networks found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
<div class="bluesky-overlay"></div>

<!-- Add Network Pop-Up -->
<div id="add-network-popup" class="bluesky-popup">
    <div class="bluesky-popup-header">Add Network</div>
    <div class="bluesky-popup-field">
        <label for="network_name">Network Name</label>
        <input type="text" id="network_name" />
    </div>
    <div class="bluesky-popup-field">
        <label for="username">Username</label>
        <input type="text" id="username" />
    </div>
    <div class="bluesky-popup-field">
        <label for="password">Password</label>
        <input type="password" id="password" />
    </div>
    <div class="bluesky-popup-actions">
        <button id="add-network-submit">Submit</button>
        <button id="close-add-network-popup">Close</button>
    </div>
</div>

<!-- Edit Network Pop-Up -->
<div id="edit-network-popup" class="bluesky-popup">
    <div class="bluesky-popup-header">Edit Network</div>
    <input type="hidden" id="network_id" />
    <div class="bluesky-popup-field">
        <label for="edit_network_name">Network Name</label>
        <input type="text" id="edit_network_name" />
    </div>
    <div class="bluesky-popup-field">
        <label for="edit_username">Username</label>
        <input type="text" id="edit_username" />
    </div>
    <div class="bluesky-popup-field">
        <label for="edit_password">Password</label>
        <input type="password" id="edit_password" />
    </div>
    <div class="bluesky-popup-actions">
        <button id="update-network-submit">Update</button>
        <button id="close-edit-network-popup">Close</button>
    </div>
</div>
        <?php wp_enqueue_script('my-script', plugins_url('js/script.js', __FILE__), array('jquery')); ?>
    </div>
    <?php
}
function bluesky_create_post_page(){
    global $wpdb;

    // Display the form
    ?>
<div class="bluesky-create-post">
    <h1>Create Post</h1>

    <!-- Message Section -->
    <div class="message-section">
        <h2>Enter Your Message</h2>
        <textarea id="post-message" maxlength="300" placeholder="Write your message here..."></textarea>
        <p id="char-count">0 / 300 characters</p>
    </div>

    <!-- Media Upload Section -->
    <div class="media-section">
        <h2>Upload Media (Optional)</h2>
        <button id="upload-media-button">Upload Media</button>
        <input type="hidden" id="uploaded-media-url" name="uploaded-media-url" value="" />
        <div class="media-preview" id="media-preview">
            <!-- <p>No media selected.</p> -->
        </div>
    </div>

    <!-- Network Selection Section -->
    <div class="network-section">
    <h2>Select Networks</h2>
    <div class="network-icons" id="network-icons">
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'bluesky_networks';

        // Fetch networks with status = 1
        $networks = $wpdb->get_results("SELECT id, network_name FROM $table_name WHERE status = 1");

        if ($networks) {
            foreach ($networks as $network) {
                ?>
                <div class="network-icon" data-id="<?php echo esc_attr($network->id); ?>" title="<?php echo esc_attr($network->network_name); ?>">
                    <img src="default-icon.png" alt="<?php echo esc_attr($network->network_name); ?>" />
                </div>
                <?php
            }
        } else {
            echo '<p>No active networks available.</p>';
        }
        ?>
    </div>
</div>
 

    <!-- Schedule or Publish Section -->
    <div class="publish-section">
        <label for="post-type">Post Type:</label>
        <select id="post-type">
            <option value="publish">Publish Now</option>
            <option value="schedule">Schedule</option>
        </select>

        <div class="schedule-picker" id="schedule-picker" style="display: none;">
            <label for="schedule-date">Select Date & Time:</label>
            <input type="datetime-local" id="schedule-date" />
        </div>
    </div>

    <!-- Action Button -->
    <div class="action-section">
        <button id="action-button">Publish Now</button>
    </div>
</div>
    <?php
}
 
function bluesky_manage_posts() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_scheduled_posts';

    // Fetch scheduled posts where status is not sent (e.g., status != 3)
    $scheduled_posts = $wpdb->get_results("SELECT * FROM $table_name WHERE posted_status != 2");

    ?>
    <div class="wrap">
        <h2>Manage Scheduled Posts</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Post ID</th>
                    <th>Message</th>
                    <th>Attachment</th>
                    <th>Bluesky Networks</th>
                    <th>Schedule Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($scheduled_posts): ?>
                    <?php foreach ($scheduled_posts as $post): ?>
                        <tr>
                            <td><?php echo $post->id; ?></td>
                            <td><?php echo esc_html($post->message); ?></td>
                            <td>
                                <?php if ($post->attachment_url): ?>
                                    <?php if (strpos($post->attachment_url, '.mp4') !== false): ?>
                                        <a href="#" class="view-attachment" data-url="<?php echo esc_url($post->attachment_url); ?>" data-type="video">View Video</a>
                                    <?php else: ?>
                                        <a href="#" class="view-attachment" data-url="<?php echo esc_url($post->attachment_url); ?>" data-type="image">View Image</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    No Attachment
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($post->network_name); ?></td>
                            <td><?php echo esc_html($post->schedule_time); ?></td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" class="status-toggle" data-id="<?php echo $post->id; ?>" <?php echo $post->posted_status == 0 ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td>
                                <a href="#" class="edit-post" data-id="<?php echo $post->id; ?>">Edit</a> |
                                <a href="#" class="delete-post" data-id="<?php echo $post->id; ?>">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No scheduled posts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Modal for Editing Post -->
<!-- Modal for Editing Post -->
<div class="bluesky-overlay" style="display:none;">
    <div class="modal-popup" id="edit-post-popup" style="position: fixed; top: 20%; left: 50%; transform: translateX(-50%); z-index: 9999; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 60%; max-width: 800px;">
        <h2>Edit Post</h2>
        <label for="edit-post-message">Message:</label>
        <textarea id="edit-post-message" rows="5" style="width: 100%;"></textarea>

        <label for="edit-post-network">Network:</label>
        <select id="edit-post-network" style="width: 100%; padding: 8px; margin-bottom: 10px;">
            <!-- Network options will be populated dynamically -->
        </select>

        <label for="edit-post-schedule-time">Schedule Time:</label>
        <input type="datetime-local" id="edit-post-schedule-time" style="width: 100%; padding: 8px; margin-bottom: 10px;" />

        <div id="edit-post-attachment-section">
            <label for="edit-post-attachment">Change Attachment:</label>
            <input type="file" id="edit-post-attachment" accept="image/*, video/*">
            <div id="edit-post-attachment-preview"></div>
            <input type="hidden" id="edit-post-attachment-url">
        </div>

        <input type="hidden" id="edit-post-id" />

        <div style="margin-top: 10px;">
            <button id="update-post-submit" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; cursor: pointer;">Update Post</button>
            <button id="close-edit-post-popup" style="padding: 10px 20px; background-color: #f44336; color: white; border: none; cursor: pointer;">Close</button>
        </div>
    </div>
</div>
    <?php
}


function bluesky_view_posts() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bluesky_posted_posts';

    // Fetch posted posts (filtering out only those with a status that is "posted" or similar)
    $posted_posts = $wpdb->get_results("SELECT * FROM $table_name WHERE 1");

    ?>
    <div class="wrap">
        <h2>View Posted Post</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Post ID</th>
                    <th>Message</th>
                    <th>Attachment</th>
                    <th>Bluesky Networks</th>
                    <th>response</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php if ($posted_posts): ?>
                    <?php foreach ($posted_posts as $post): ?>
           <?php             $table_name2 = $wpdb->prefix . 'bluesky_networks';
            $query = $wpdb->prepare("SELECT * FROM $table_name2 WHERE id = %d", $post->network_id);
            $find_user = $wpdb->get_results($query);
            if (!empty($find_user) && is_array($find_user) && isset($find_user[0])) {
                $username = $find_user[0]->username;
            }
            
            
            ?>    
            <tr>
                            <td><?php echo esc_html($post->id); ?></td>
                            <td><?php echo esc_html($post->message); ?></td>
                            <td>
                                <?php if ($post->attachment_url): ?>
                                    <?php if (strpos($post->attachment_url, '.mp4') !== false): ?>
                                        <a href="#" class="view-attachment" data-url="<?php echo esc_url($post->attachment_url); ?>" data-type="video">View Video</a>
                                    <?php else: ?>
                                        <a href="#" class="view-attachment" data-url="<?php echo esc_url($post->attachment_url); ?>" data-type="image">View Image</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    No Attachment
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Get the network names associated with this post
                                $network =$post->network_name;
                                if(!empty($network)) {
                                $network = str_replace('"',"",$network);
                            }
                                if ($network) {
                                    echo esc_html($network);
                                } else {
                                    echo "No Networks";
                                }
                                ?>
                            </td>
                            <td>
                            <?php if ($post->response !=0): ?>
    <?php 
    // Decode the JSON response to check the URI
    
    $post_url = $post->response;
       echo '<span class="status success"><a href="' . esc_url($post_url) . '" target="_blank">View Post</a></span>';
    ?>
<?php else: 
    echo '<span class="status failed">' . esc_html($post->actual_response) . '</span>';
    ?>
<?php endif; ?>

                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No posted posts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    
    
}
