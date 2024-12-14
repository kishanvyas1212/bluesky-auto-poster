jQuery(document).ready(function ($) {
    // Show Add Network Popup when clicking the Add Network button
    $('#add-network-button').click(function () {
        $('.bluesky-overlay').fadeIn(); // Show overlay
        $('#add-network-popup').fadeIn(); // Show pop-up
    });

    // Close Add Network Popup
    $('#close-add-network-popup').click(function () {
        $('.bluesky-overlay').fadeOut(); // Hide overlay
        $('#add-network-popup').fadeOut(); // Hide pop-up
    });

    // Show Edit Network Popup when clicking the Edit Network button
    $('.edit-network').click(function () {
        const networkId = $(this).data('id');

        // AJAX call to get the network details
        $.ajax({
            url: blueskyPoster.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_network_details',
                network_id: networkId,
                nonce: blueskyPoster.nonce,
            },
            success: function (response) {
                if (response.success) {
                    // Fill in the form with the fetched data
                    $('#network_id').val(response.data.id);
                    $('#edit_network_name').val(response.data.network_name);
                    $('#edit_username').val(response.data.username);
                    $('#edit_password').val(response.data.password);

                    // Show the edit pop-up
                    $('.bluesky-overlay').fadeIn();
                    $('#edit-network-popup').fadeIn();
                } else {
                    alert(response.data.message || 'Failed to fetch network details.');
                }
            },
            error: function () {
                alert('An unexpected error occurred.');
            },
        });
    });

    // Close both Add and Edit Network Pop-ups when clicking on overlay or close buttons
    $('.bluesky-overlay, #close-add-network-popup, #close-edit-network-popup').click(function () {
        $('.bluesky-overlay').fadeOut(); // Hide overlay
        $('#add-network-popup, #edit-network-popup').fadeOut(); // Hide pop-ups
    });

    // Prevent click on pop-up from closing it (stop propagation)
    $('#add-network-popup, #edit-network-popup').click(function (e) {
        e.stopPropagation();
    });

    // Submit Add Network Form
    $('#add-network-submit').click(function () {
        const networkName = $('#network_name').val();
        const username = $('#username').val();
        const password = $('#password').val();

        if (!networkName || !username || !password) {
            alert('All fields are required!');
            return;
        }

        // AJAX call to add the network
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'add_bluesky_network',
                network_name: networkName,
                username: username,
                password: password,
                nonce: blueskyPoster.nonce,
            },
            success: function (response) {
                if (response.success) {
                    alert('Network added successfully!');
                    location.reload(); // Refresh the page to show the updated network
                } else {
                    alert(response.data.message || 'An error occurred.');
                }
            },
            error: function () {
                alert('An unexpected error occurred.');
            },
        });
    });

    // Submit Edit Network Form
    $('#update-network-submit').click(function () {
        const networkId = $('#network_id').val();
        const networkName = $('#edit_network_name').val();
        const username = $('#edit_username').val();
        const password = $('#edit_password').val();

        if (!networkName || !username || !password) {
            alert('All fields are required!');
            return;
        }

        // AJAX call to update the network
        $.ajax({
            url: blueskyPoster.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_bluesky_network',
                network_id: networkId,
                network_name: networkName,
                username: username,
                password: password,
                nonce: blueskyPoster.nonce,
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message || 'Network updated successfully!');
                    location.reload(); // Refresh the page to show updated data
                } else {
                    alert(response.data.message || 'An error occurred.');
                }
            },
            error: function () {
                alert('An unexpected error occurred.');
            },
        });
    });

    // Delete Network
    $('.delete-network').click(function () {
        const networkId = $(this).data('id');
        if (confirm('Are you sure you want to delete this network?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_bluesky_network',
                    network_id: networkId,
                    nonce: blueskyPoster.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        alert('Network deleted successfully!');
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to delete network.');
                    }
                },
                error: function () {
                    alert('An unexpected error occurred.');
                },
            });
        }
    });

    $('.toggle-status').on('change', function () {
        const networkId = $(this).data('id');
        const isActive = $(this).is(':checked') ? 1 : 2; // 1 for Active, 2 for Inactive

        console.log(`Toggling status for Network ID: ${networkId} to ${isActive}`);

        // AJAX request to update the status
        $.ajax({
            url: ajaxurl, // Ensure ajaxurl is defined
            type: 'POST',
            data: {
                action: 'toggle_bluesky_network_status',
                network_id: networkId,
                status: isActive,
                nonce: blueskyPoster.nonce, // Pass the nonce for verification
            },
            success: function (response) {
                console.log(response); // Log response for debugging
                if (response.success) {
                    alert('Network status updated successfully!');
                } else {
                    alert(response.data || 'Failed to update network status.');
                }
            },
            error: function (xhr, status, error) {
                alert('An unexpected error occurred.');
            },
        });
    });
     // Submit post form
$('#action-button').on('click', function () {
    const message = $('#post-message').val();
    const mediaUrl = $('#uploaded-media-url').val();
    const selectedNetworks = [];
    const selectedNetworksNames = [];

    // Collect selected network IDs
    $('.network-icon.selected').each(function () {
        const networkId = $(this).data('id'); // Ensure data-id attribute is correctly set in HTML
        if (networkId) {
            selectedNetworks.push(networkId);
        }
    });

    // Collect selected network names
    $('.network-icon.selected').each(function () {
        const networkName = $(this).attr('title'); // Access the title attribute
        if (networkName) {
            selectedNetworksNames.push(networkName);
        }
    });


        const postType = $('#post-type').val();
        const scheduleTime = $('#schedule-date').val();

        if (!message) {
            alert('Message cannot be empty.');
            return;
        }

        if (message.length > 300) {
            alert('Message exceeds 300 characters.');
            return;
        }

        if (selectedNetworks.length === 0) {
            alert('Please select at least one network.');
            return;
        }

        if (postType === 'schedule' && !scheduleTime) {
            alert('Please select a valid schedule time.');
            return;
        }

        // AJAX request
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'bluesky_submit_post',
                nonce: blueskyPoster.nonce,
                message: message,
                media_url: mediaUrl,
                networks: selectedNetworks,
                networksnames: selectedNetworksNames,
                post_type: postType,
                schedule_time: scheduleTime,
                // nonce: blueskyPoster.nonce,
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || 'An error occurred.');
                }
            },
            error: function () {
                alert('An unexpected error occurred.');
            },
        });
    });


    $('#post-message').on('input', function () {
        const charCount = $(this).val().length;
        $('#char-count').text(`${charCount} / 300 characters`);
    });

    // Toggle schedule picker based on post type selection
    $('#post-type').on('change', function () {
        if ($(this).val() === 'schedule') {
            $('#schedule-picker').show();
            $('#action-button').text('Schedule Post');
        } else {
            $('#schedule-picker').hide();
            $('#action-button').text('Publish Now');
        }
    });

    // Select network on icon click
    $('.network-icon').on('click', function () {
        $(this).toggleClass('selected'); // Add or remove 'selected' class
    });

    $('.status-toggle').on('change', function () {
        const postId = $(this).data('id');
        const newStatus = $(this).is(':checked') ? 0: 1;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_schedule_status',
                post_id: postId,
                status: newStatus,
                nonce: blueskyPoster.nonce,

            },
            success: function (response) {
                alert(response.success ? 'Status updated successfully!' : 'Failed to update status.');
            },
            error: function () {
                alert('Error while updating status.');
            }
        });
    });

    // Delete post
    $('.delete-post').on('click', function (e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this post?')) return;

        const postId = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_scheduled_post',
                post_id: postId,
                nonce: blueskyPoster.nonce,
            },
            success: function (response) {
                if (response.success) {
                    location.reload(); // Reload the page to refresh the table
                } else {
                    alert('Failed to delete post.');
                }
            },
            error: function () {
                alert('Error while deleting post.');
            }
        });
    });

    // View attachment
    $('.view-attachment').on('click', function (e) {
        e.preventDefault();
        const url = $(this).data('url');
        const type = $(this).data('type');

        let content = '';
        if (type === 'video') {
            content = `<video controls autoplay><source src="${url}" type="video/mp4">Your browser does not support the video tag.</video>`;
        } else {
            content = `<img src="${url}" alt="Attachment Preview">`;
        }

        const modal = `<div class="modal" id="attachment-modal">
            <div class="modal-content">${content}</div>
        </div>`;
        $('body').append(modal);

        $('#attachment-modal').fadeIn();

        // Close modal on click
        $('#attachment-modal').on('click', function () {
            $(this).fadeOut(function () {
                $(this).remove();
            });
        });
    }); 

        // Prevent closing of modal when clicking on certain elements
        $('#edit-post-message, #edit-post-network, #edit-post-schedule-time').on('click', function (e) {
            e.stopPropagation(); // Prevents closing the modal when clicking inside the input fields
        });
    
            // Show Edit Network Popup when clicking the Edit Network button
            $('.edit-network').click(function () {
                const networkId = $(this).data('id');
        
                // AJAX call to get the network details
                $.ajax({
                    url: blueskyPoster.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_network_details',
                        network_id: networkId,
                        nonce: blueskyPoster.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            // Fill in the form with the fetched data
                            $('#network_id').val(response.data.id);
                            $('#edit_network_name').val(response.data.network_name);
                            $('#edit_username').val(response.data.username);
                            $('#edit_password').val(response.data.password);
        
                            // Show the edit pop-up
                            $('.bluesky-overlay').fadeIn();
                            $('#edit-network-popup').fadeIn();
                        } else {
                            alert(response.data.message || 'Failed to fetch network details.');
                        }
                    },
                    error: function () {
                        alert('An unexpected error occurred.');
                    },
                });
            });
        
            // Handle the attachment for editing the post
            $('.edit-post').on('click', function (e) {
                e.preventDefault();
        
                var postId = $(this).data('id');
        
                // Send an AJAX request to fetch the post data
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fetch_scheduled_post_details',
                        post_id: postId,
                        nonce: blueskyPoster.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            var post = response.data.post;
                            var networks = response.data.networks;
        
                            // Populate the modal with post details
                            $('#edit-post-id').val(post.id);
                            $('#edit-post-message').val(post.message);
                            $('#edit-post-schedule-time').val(post.schedule_time);
                            
                            // Populate network dropdown with multi-select
                            $('#edit-post-network').empty();
                            $.each(networks, function(index, network) {
                                $('#edit-post-network').append('<option value="'+network.id+'">'+network.network_name+'</option>');
                            });
        
                            // Allow multiple selections in the network field
                            $('#edit-post-network').attr('multiple', 'multiple');
        
                            // Populate attachment preview
                            if (post.attachment_url) {
                                $('#edit-post-attachment-preview').show().html('<img src="' + post.attachment_url + '" class="attachment-preview" style="width: 100%; max-width: 200px;">');
                                $('#edit-post-attachment-url').val(post.attachment_url); // Set the URL for the attachment field
                            } else {
                                $('#edit-post-attachment-preview').hide();
                            }
        
                            // Show the modal
                            $('.bluesky-overlay').fadeIn();
                            $('#edit-post-popup').fadeIn();
                        } else {
                            alert('Failed to fetch post details.');
                        }
                    },
                    error: function () {
                        alert('An unexpected error occurred.');
                    }
                });
            });
        
            // Handle Edit Post Form submission (to update the post)
            $('#update-post-submit').on('click', function () {
                var postId = $('#edit-post-id').val();
                var message = $('#edit-post-message').val();
                var networkIds = $('#edit-post-network').val(); // Get selected network IDs (multiple selections allowed)
                var scheduleTime = $('#edit-post-schedule-time').val();
                var attachmentUrl = $('#edit-post-attachment-url').val();
        
                // Send the updated data via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_scheduled_post',
                        post_id: postId,
                        message: message,
                        network_ids: networkIds,  // Pass selected network IDs as an array
                        schedule_time: scheduleTime,
                        attachment_url: attachmentUrl,
                        nonce:blueskyPoster.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Post updated successfully!');
                            location.reload(); // Refresh the page to show the updated data
                        } else {
                            alert('Failed to update post.');
                        }
                    },
                    error: function () {
                        alert('An unexpected error occurred.');
                    }
                });
            });
        
            // Close the edit post modal on overlay click or close button
            $('#close-edit-post-popup').on('click', function () {
                $('.bluesky-overlay').fadeOut();
                $('#edit-post-popup').fadeOut();
            });
        
            // Prevent pop-up close on clicking the message area
            $('#edit-post-message').on('click', function(e) {
                e.stopPropagation(); // Prevents closing the modal when clicking on the message area
            });
            (function ($) {
                $(document).ready(function () {
                    // Fetch posts on page load
                    fetchPosts();
            
                    // Search functionality
                    $('#search-button').on('click', function () {
                        const search = $('#search-posts').val();
                        const network = $('#network-filter').val();
                        fetchPosts(1, search, network);
                    });
            
                    // Pagination click
                    $(document).on('click', '.pagination-link', function (e) {
                        e.preventDefault();
                        const page = $(this).data('page');
                        const search = $('#search-posts').val();
                        const network = $('#network-filter').val();
                        fetchPosts(page, search, network);
                    });
            
                    // Fetch posts
                    function fetchPosts(page = 1, search = '', network = '') {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'fetch_bluesky_posts',
                                page: page,
                                search: search,
                                network: network,
                                nonce: blueskyPoster.nonce, // Nonce for security
                            },
                            beforeSend: function () {
                                $('#posts-table-body').html('<tr><td colspan="5">Loading...</td></tr>');
                                $('#pagination-links').html('');  // Clear pagination while loading
                            },
                            success: function (response) {
                                console.log(response);
                                if (response.success) {
                                    $('#posts-table-body').html(response.data.posts_html);
                                    generatePagination(response.data.total_pages, page);
                                } else {
                                    $('#posts-table-body').html('<tr><td colspan="5">No posts found.</td></tr>');
                                }
                            },
                            error: function () {
                                $('#posts-table-body').html('<tr><td colspan="5">An error occurred.</td></tr>');
                            }
                        });
                    }
            
                    // Generate dynamic pagination
                    function generatePagination(totalPages, currentPage) {
                        console.log("function is called");
                        let paginationHtml = '';
            
                        // Check if total pages are greater than 3
                        if (totalPages > 3) {
                            // Always show the first page
                            paginationHtml += '<a href="#" class="pagination-link" data-page="1">1</a>';
            
                            // Show previous page if needed
                            if (currentPage > 1) {
                                paginationHtml += '<a href="#" class="pagination-link" data-page="' + (currentPage - 1) + '">Prev</a>';
                            }
            
                            // Show current page
                            paginationHtml += '<a href="#" class="pagination-link active" data-page="' + currentPage + '">' + currentPage + '</a>';
            
                            // Show next page if needed
                            if (currentPage < totalPages) {
                                paginationHtml += '<a href="#" class="pagination-link" data-page="' + (currentPage + 1) + '">Next</a>';
                            }
            
                            // Always show the last page
                            if (currentPage < totalPages) {
                                paginationHtml += '<a href="#" class="pagination-link" data-page="' + totalPages + '">' + totalPages + '</a>';
                            }
                        } else {
                            // If only 3 or fewer pages, display all pages
                            for (let i = 1; i <= totalPages; i++) {
                                console.log(totalPages);
                                paginationHtml += '<a href="#" class="pagination-link' + (i === currentPage ? ' active' : '') + '" data-page="' + i + '">' + i + '</a>';
                            }
                        }
            
                        // Update pagination links container
                        $('#pagination-links').html(paginationHtml);
                    }
                });
            })(jQuery);
            
                                
});
