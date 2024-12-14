<?php
require_once 'ajax.php';
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

    // Add the new settings page
    add_submenu_page(
        'bluesky-poster',
        'Bluesky Settings',
        'Settings',
        'manage_options',
        'bluesky-settings',
        'bluesky_settings_page'
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
    ?>
    <div class="wrap">
        <h2>View scheduled Posts</h2>
        <div class="filter-bar">
            <input type="text" id="search-posts" placeholder="Search posts..." />

            <button id="search-button">Search</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Schedule Post ID</th>
                    <th>Message</th>
                    <th>Attachment</th>
                    <th>Bluesky Networks</th>
                    <th>Status</th>
                    <th>Schedule Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="schedule-posts-table-body">
                <!-- AJAX content will be loaded here -->
            </tbody>
        </table>
        <div id="pagination-links" class="pagination-links" style="text-align: right;"></div>
    </div>
    <?php
}
add_action('admin_init', 'bluesky_register_settings');
function bluesky_register_settings() {
    // Register settings
    register_setting('bluesky_settings_group', 'bluesky_cron_time');
    register_setting('bluesky_settings_group', 'bluesky_delete_posts');
    register_setting('bluesky_settings_group', 'bluesky_refresh_token_interval');

    // Add settings section
    add_settings_section(
        'bluesky_settings_section',
        'Bluesky Poster Settings',
        'bluesky_settings_section_callback',
        'bluesky-settings'
    );

    // Add fields
    add_settings_field(
        'bluesky_cron_time',
        'Cron Time for Scheduling Posts',
        'bluesky_cron_time_callback',
        'bluesky-settings',
        'bluesky_settings_section'
    );

    add_settings_field(
        'bluesky_delete_posts',
        'Delete Posts from Bluesky on Post Deletion',
        'bluesky_delete_posts_callback',
        'bluesky-settings',
        'bluesky_settings_section'
    );

    add_settings_field(
        'bluesky_refresh_token_interval',
        'Cron Interval for Refresh Token',
        'bluesky_refresh_token_callback',
        'bluesky-settings',
        'bluesky_settings_section'
    );
}

function bluesky_settings_section_callback() {
    echo '<p>Configure the settings for the Bluesky Automated Poster plugin.</p>';
}

// Callback for Cron Time
function bluesky_cron_time_callback() {
    $value = get_option('bluesky_cron_time', 5); // Default to 5 minutes
    echo '<input type="number" name="bluesky_cron_time" value="' . esc_attr($value) . '" min="1" step="1">';
    echo '<p class="description">Enter the cron time interval in minutes. Default is 5 minutes.</p>';
}

// Callback for Delete Posts Option
function bluesky_delete_posts_callback() {
    $value = get_option('bluesky_delete_posts', 0); // Default to No (0)
    echo '<select name="bluesky_delete_posts">';
    echo '<option value="0"' . selected($value, 0, false) . '>No</option>';
    echo '<option value="1"' . selected($value, 1, false) . '>Yes</option>';
    echo '</select>';
    echo '<p class="description">Choose whether to delete posts from Bluesky when they are deleted from WordPress. Default is No.</p>';
}

// Callback for Refresh Token Interval
function bluesky_refresh_token_callback() {
    $value = get_option('bluesky_refresh_token_interval', 12); // Default to 12 hours
    echo '<select name="bluesky_refresh_token_interval">';
    echo '<option value="1"' . selected($value, 1, false) . '>1 Hour</option>';
    echo '<option value="2"' . selected($value, 2, false) . '>2 Hours</option>';
    echo '<option value="12"' . selected($value, 12, false) . '>12 Hours</option>';
    echo '</select>';
    echo '<p class="description">Set the cron interval for refreshing the refresh token. Default is 12 hours.</p>';
}

function bluesky_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>Bluesky Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('bluesky_settings_group');
    do_settings_sections('bluesky-settings');
    submit_button();
    echo '</form>';
    echo '</div>';
}
function bluesky_view_posts() {
    ?>
    <div id="message-container"></div>
    <div class="wrap">
        <h2>View Posted Posts</h2>
        <div class="filter-bar">
            <input type="text" id="search-posts" placeholder="Search posts..." />
            <select id="network-filter">
                <option value="">All Networks</option>
                <option value="Network1">Network1</option>
                <option value="Network2">Network2</option>
            </select>
            <button id="search-button">Search</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Post ID</th>
                    <th>Message</th>
                    <th>Attachment</th>
                    <th>Bluesky Networks</th>
                    <th>response</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="posts-table-body">
                <!-- AJAX content will be loaded here -->
            </tbody>
        </table>
        <div id="pagination-links" class="pagination-links" style="text-align: right;"></div>
    </div>
    <?php
}
    