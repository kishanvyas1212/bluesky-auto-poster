(function ($) {
    function displayMessage(message, type) {
        const messageContainer = document.getElementById('message-container');
        messageContainer.textContent = message;
      
        if (type === 'success') {
          messageContainer.classList.add('success');
        } else if (type === 'error') {
          messageContainer.classList.add('error');
        }
      
        messageContainer.style.display = 'block';
      
        // Optionally, hide the message after a certain time
        setTimeout(() => {
          messageContainer.style.display = 'none';
          messageContainer.classList.remove('success', 'error');
        }, 2000);
      }
    $(document).ready(function () {
        // Fetch posts on page load
        $('#loader-overlay').show();
        fetchPosts();
        $('#loader-overlay').hide();
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
                    action: 'bluesky_schedule_post',
                    page: page,
                    search: search,
                    network: network,
                    nonce: blueskyPoster.nonce, // Nonce for security
                },
                beforeSend: function () {
                    $('#schedule-posts-table-body').html('<tr><td colspan="5" display: flexbox; text-align: center;>Loading...</td></tr>');
                    $('#pagination-links').html('');  // Clear pagination while loading
                },
                success: function (response) {
                    if (response.success) {
                        $('#schedule-posts-table-body').html(response.data.posts_html);
                        generatePagination(response.data.total_pages, page);
                    } else {
                        $('#schedule-posts-table-body').html('<tr><td colspan="5">No posts found.</td></tr>');
                    }
                },
                error: function () {
                    $('#schedule-posts-table-body').html('<tr><td colspan="5">An error occurred.</td></tr>');
                }
            });
        }

        // Generate dynamic pagination
        function generatePagination(totalPages, currentPage) {
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

                    paginationHtml += '<a href="#" class="pagination-link' + (i === currentPage ? ' active' : '') + '" data-page="' + i + '">' + i + '</a>';
                }
            }

            // Update pagination links container
            $('#pagination-links').html(paginationHtml);
        }
    });

    
})(jQuery);
(function ($) {
    function displayMessage(message, type) {
        const messageContainer = document.getElementById('message-container');
        messageContainer.textContent = message;
      
        if (type === 'success') {
          messageContainer.classList.add('success');
        } else if (type === 'error') {
          messageContainer.classList.add('error');
        }
      
        messageContainer.style.display = 'block';
      
        // Optionally, hide the message after a certain time
        setTimeout(() => {
          messageContainer.style.display = 'none';
          messageContainer.classList.remove('success', 'error');
        }, 2000);
      }
    $(document).ready(function () {
        $(document).on('click', '#delete-post', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this post?')) return;
            $('#loader-overlay').show();
    
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
                        displayMessage("post is successfully deleted",'success');
                        setTimeout(() => {
                            location.reload();
                        }, 2000); 
                    } else {
                        displayMessage("Failed to delete post.",'error');
                    }
                },
                error: function () {
                    $('#loader-overlay').hide();
                    alert('Error while deleting post.');
                }
            });
        });

$(document).on('change', '#status-toggle',function () {
            const postId = $(this).data('id');
            const newStatus = $(this).is(':checked') ? 0: 1;
            $('#loader-overlay').show();
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
                    $('#loader-overlay').hide();
                    response.success ? displayMessage('Status updated successfully!','success') : displayMessage('Failed to update status.','error')
                    
                },
                error: function () {
                    alert('Error while updating status.');
                }
            });
        });
    
    });

    
})(jQuery);
