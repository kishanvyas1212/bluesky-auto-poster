<?php

/**
 * Function to post a text message on Bluesky network.
 *
 * @param string $username The username for Bluesky.
 * @param string $password The password for Bluesky.
 * @param string $message The message to post on Bluesky.
 * @return array The response from Bluesky API.
 */
function text_poster_with_login_details($username, $password, $message) {
    // Bluesky API URLs
    $login_url = "https://bsky.social/xrpc/com.atproto.server.createSession";
    $post_url = "https://bsky.social/xrpc/com.atproto.repo.createRecord";

    // Get current UTC time in the required format
    $now_str = gmdate("Y-m-d\TH:i:s\Z");
    error_log("Current time ");
    error_log($now_str);
    // 1. Create session (login) to get the JWT tokens
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
        $access_jwt = $login_data['accessJwt'];
        error_log("Access token" . $access_jwt);
        $refresh_jwt = $login_data['refreshJwt'];

        // 2. Post creation (publish a post on Bluesky network)
        $post_data = json_encode([
            "repo" => $username,
            "collection" => "app.bsky.feed.post",
            "record" => [
                "text" => $message,  // Post the provided message
                "createdAt" => $now_str
            ]
        ]);

        $post_headers = [
            "Content-Type: application/json",
            "Authorization: Bearer " . $access_jwt
        ];

        // Initialize cURL session for post creation
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $post_headers);

        $post_response = curl_exec($ch);
        $post_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Return the response from Bluesky API
        return [
            'response' => $post_response,
            'status_code' => $post_status_code
        ];
    } else {
        // Return error if login failed
        return [
            'error' => 'Login failed. Check credentials.',
            'response' => $login_response
        ];
    }
}
function text_poster($access_jwt,$message,$username){
    $post_url = "https://bsky.social/xrpc/com.atproto.repo.createRecord";

    // Get current UTC time in the required format
    $now_str = gmdate("Y-m-d\TH:i:s\Z");
    $post_headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $access_jwt
    ];
    $post_data = json_encode([
        "repo" => $username,
        "collection" => "app.bsky.feed.post",
        "record" => [
            "text" => $message,  // Post the provided message
            "createdAt" => $now_str
        ]
    ]);
    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $post_headers);

        $post_response = curl_exec($ch);
        $post_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Return the response from Bluesky API
        return [
            'response' => $post_response,
            'status_code' => $post_status_code
        ];

}
function bluesky_post_with_attachment($access_jwt,$username, $message, $attachment_path){
    $upload_url = "https://bsky.social/xrpc/com.atproto.repo.uploadBlob";
    $post_url = "https://bsky.social/xrpc/com.atproto.repo.createRecord";
    $now_str = gmdate("Y-m-d\TH:i:s.v\Z");

    // Validate the attachment size (max 1MB)
    if (filesize($attachment_path) > 1000000) {
        return [
            'error' => 'Attachment size too large. Maximum allowed size is 1MB.'
        ];
    }
    $image_data = file_get_contents($attachment_path);
    // $image_mime_type = mime_content_type($attachment_path);
    $image_filename = basename($attachment_path);
    $file_extension = pathinfo($attachment_path, PATHINFO_EXTENSION);
    $mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];
    $image_mime_type = $mime_types[strtolower($file_extension)] ?? null;

if (!$image_mime_type) {
    error_log("Could not determine MIME type for file: $attachment_path");
    return null; // Handle error gracefully
}

if (!$image_data || !$image_mime_type) {
    error_log("Error reading the image file or detecting its MIME type.");
    return null; // Handle error gracefully
}

$ch = curl_init($upload_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_jwt",
    "Content-Type: $image_mime_type", 
    // "Content-Type: multipart/form-data; boundary=$delimiter",
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $image_data);

$upload_response = curl_exec($ch);
$upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($upload_http_code !== 200 || !$upload_response) {
    return [
        'error' => 'Failed to upload the image.',
        'details' => $upload_response,
    ];
}

$upload_body = json_decode($upload_response, true);
$blob = $upload_body['blob'] ?? null;

if (!$blob) {
    return [
        'error' => 'Image upload failed.',
        'response' => $upload_body,
    ];
}

    // Step 3: Post the content
    $embed_data = [
        '$type' => 'app.bsky.embed.images',
        'images' => [
            [
                'alt' => 'Brief alt text description of the image',
                'image' => $blob,
                'aspectRatio' => [
                    'width' => 1000,
                    'height' => 500,
                ],
            ],
        ],
    ];

    $record_data = [
        'text' => $message,
        'embed' => $embed_data,
        'createdAt' => $now_str,
    ];

    $post_data = json_encode([
        'repo' => $username,
        'collection' => 'app.bsky.feed.post',
        'record' => $record_data,
    ]);

    $ch = curl_init($post_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $access_jwt",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $post_response = curl_exec($ch);
    $post_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($post_http_code !== 200 || !$post_response) {
        return [
            'error' => 'Failed to post to Bluesky.',
            'details' => $post_response,
        ];
    }else {
        error_log("Post succeeded. Response: $post_response");
    }

    $post_body = $post_response;

    
    if (empty($post_body['uri'])) {
        return [
            'error' => 'Post creation failed.',
            'response' => $post_body,
        ];
    }
    return [
        'success' => true,
        'response' => $post_body,
    ];

}

function bluesky_post_with_video($access_jwt, $username, $message, $video_path) {
    $upload_url = "https://bsky.social/xrpc/com.atproto.repo.uploadBlob";
    $post_url = "https://bsky.social/xrpc/com.atproto.repo.createRecord";
    $now_str = gmdate("Y-m-d\TH:i:s.v\Z");

    // Validate the video file size (max 10MB)
    if (filesize($video_path) > 10 * 1024 * 1024) { // 10MB
        return [
            'error' => 'Video size too large. Maximum allowed size is 10MB.'
        ];
    }

    // Validate the file extension and MIME type
    $file_extension = pathinfo($video_path, PATHINFO_EXTENSION);
    $mime_types = [
        'mp4' => 'video/mp4'
    ];
    $video_mime_type = $mime_types[strtolower($file_extension)] ?? null;

    if (!$video_mime_type) {
        return [
            'error' => 'Invalid file type. Only MP4 videos are supported.'
        ];
    }

    // Read video file
    $video_data = file_get_contents($video_path);
    if (!$video_data) {
        return [
            'error' => 'Unable to read video file.'
        ];
    }

    // Step 1: Upload the video
    $ch = curl_init($upload_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_jwt",
        "Content-Type: $video_mime_type",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $video_data);

    $upload_response = curl_exec($ch);
    $upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    

    if ($upload_http_code !== 200 || !$upload_response) {
        return [
            'error' => 'Failed to upload the video.',
            'details' => $upload_response,
        ];
    }

    $upload_body = json_decode($upload_response, true);
    $blob = $upload_body['blob'] ?? null;

    if (!$blob) {
        return [
            'error' => 'Video upload failed.',
            'response' => $upload_body,
        ];
    }

    // Step 2: Create a post with the video embed
    $embed_data = [
        '$type' => 'app.bsky.embed.video',
        'video' => $blob,
        'alt' => 'Brief alt text description of the video',
    ];

    $record_data = [
        'text' => $message,
        'embed' => $embed_data,
        'createdAt' => $now_str,
    ];

    $post_data = json_encode([
        'repo' => $username,
        'collection' => 'app.bsky.feed.post',
        'record' => $record_data,
    ]);

    // Step 3: Post the content
    $ch = curl_init($post_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $access_jwt",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $post_response = curl_exec($ch);
    $post_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log("its start from here for posting_to_bluesky file line 350");
    error_log($post_response);
    error_log("its end from here for posting_to_bluesky file line 350");
    curl_close($ch);

    if ($post_http_code !== 200 || !$post_response) {
        return [
            'error' => 'Failed to post to Bluesky.',
            'details' => $post_response,
        ];
    }

    $post_body = $post_response;

    if (empty($post_body['uri'])) {
        return [
            'error' => 'Post creation failed.',
            'response' => $post_body,
        ];
    }

    return [
        'success' => true,
        'response' => $post_body,
    ];
}


function bluesky_post_with_attachment_with_login_details($username, $password, $message, $attachment_path) {
    // API URLs
    $login_url = "https://bsky.social/xrpc/com.atproto.server.createSession";
    $upload_url = "https://bsky.social/xrpc/com.atproto.repo.uploadBlob";
    $post_url = "https://bsky.social/xrpc/com.atproto.repo.createRecord";

    // Get current UTC time in ISO 8601 format
    $now_str = gmdate("Y-m-d\TH:i:s.v\Z");

    // Validate the attachment size (max 1MB)
    if (filesize($attachment_path) > 1000000) {
        return [
            'error' => 'Attachment size too large. Maximum allowed size is 1MB.'
        ];
    }


    // Step 1: Authenticate
    $login_data = json_encode([
        'identifier' => $username,
        'password' => $password,
    ]);

    $ch = curl_init($login_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);

    $login_response = curl_exec($ch);
    $login_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($login_http_code !== 200 || !$login_response) {
        return [
            'error' => 'Failed to authenticate with Bluesky.',
            'details' => $login_response,
        ];
    }

    $login_body = json_decode($login_response, true);
    $access_jwt = $login_body['accessJwt'] ?? null;

    if (!$access_jwt) {
        return [
            'error' => 'Authentication failed.',
            'response' => $login_body,
        ];
    }
    // Step 2: Upload the image
$image_data = file_get_contents($attachment_path);
// $image_mime_type = mime_content_type($attachment_path);
$image_filename = basename($attachment_path);
$file_extension = pathinfo($attachment_path, PATHINFO_EXTENSION);
$mime_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
];

$image_mime_type = $mime_types[strtolower($file_extension)] ?? null;

if (!$image_mime_type) {
    error_log("Could not determine MIME type for file: $attachment_path");
    return null; // Handle error gracefully
}

if (!$image_data || !$image_mime_type) {
    error_log("Error reading the image file or detecting its MIME type.");
    return null; // Handle error gracefully
}

$ch = curl_init($upload_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_jwt",
    "Content-Type: $image_mime_type", 
    // "Content-Type: multipart/form-data; boundary=$delimiter",
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $image_data);

$upload_response = curl_exec($ch);
$upload_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($upload_http_code !== 200 || !$upload_response) {
    return [
        'error' => 'Failed to upload the image.',
        'details' => $upload_response,
    ];
}

$upload_body = json_decode($upload_response, true);
$blob = $upload_body['blob'] ?? null;

if (!$blob) {
    return [
        'error' => 'Image upload failed.',
        'response' => $upload_body,
    ];
}

    // Step 3: Post the content
    $embed_data = [
        '$type' => 'app.bsky.embed.images',
        'images' => [
            [
                'alt' => 'Brief alt text description of the image',
                'image' => $blob,
                'aspectRatio' => [
                    'width' => 1000,
                    'height' => 500,
                ],
            ],
        ],
    ];

    $record_data = [
        'text' => $message,
        'embed' => $embed_data,
        'createdAt' => $now_str,
    ];

    $post_data = json_encode([
        'repo' => $username,
        'collection' => 'app.bsky.feed.post',
        'record' => $record_data,
    ]);

    $ch = curl_init($post_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $access_jwt",
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $post_response = curl_exec($ch);
    $post_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($post_http_code !== 200 || !$post_response) {
        return [
            'error' => 'Failed to post to Bluesky.',
            'details' => $post_response,
        ];
    }else {
        error_log("Post succeeded. Response: $post_response");
    }

    $post_body = $post_response;

    
    if (empty($post_body['uri'])) {
        return [
            'error' => 'Post creation failed.',
            'response' => $post_body,
        ];
    }
    return [
        'success' => true,
        'response' => $post_body,
    ];
}


?>
