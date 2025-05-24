jQuery(document).ready(function($) {
    $('.ld-button').on('click', function(e) {
        e.preventDefault();

        var $btn = $(this);
        var $container = $btn.closest('.ld-buttons');
        var itemId = $container.data('id');
        var itemType = $container.data('type');
        var actionType = $btn.hasClass('ld-like') ? 'like' : 'dislike';

        // Disable buttons immediately
        $container.find('.ld-button').prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: ld_ajax_object.ajax_url,
            data: {
                action: 'ld_handle_vote',
                nonce: ld_ajax_object.nonce,
                item_id: itemId,
                type: itemType,
                action_type: actionType
            },
            success: function(response) {
                if (response.success) {
                    $container.find('.ld-like-count').text(response.data.likes);
                    $container.find('.ld-dislike-count').text(response.data.dislikes);
                } else {
                    alert(response.data.message);
                }
            },
            complete: function() {
                $container.find('.ld-button').prop('disabled', false);
            }
        });
    });
});
