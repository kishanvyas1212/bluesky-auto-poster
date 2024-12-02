jQuery(document).ready(function ($) {
    let mediaUploader;

    $('#upload-media-button').click(function (e) {
        e.preventDefault();

        // Open WordPress Media Uploader
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select or Upload Media',
            button: {
                text: 'Use this media'
            },
            multiple: false // Allow single file upload
        });

        // Handle media selection
        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            const mediaUrl = attachment.url;
            const mediaType = attachment.type; // Check if image or video

            // Update hidden input with URL
            $('#uploaded-media-url').val(mediaUrl);

            // Clear existing preview
            $('#media-preview').empty();

            // Display preview based on type
            if (mediaType === 'image') {
                $('#media-preview').append(`<img src="${mediaUrl}" alt="Preview" class="media-thumbnail" />`);
            } else if (mediaType === 'video') {
                $('#media-preview').append(`
                    <div class="video-thumbnail-wrapper">
                        <video src="${mediaUrl}" controls class="media-thumbnail"></video>
                        <a href="${mediaUrl}" class="open-video-player" target="_blank">Open Video</a>
                    </div>
                `);
            }
        });

        mediaUploader.open();
    });
});
