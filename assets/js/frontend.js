jQuery(function($) {
    $(document).on('click', '.cht-order-sample', function(e) {
        e.preventDefault();
        var button = $(this);
        var productId = button.data('product-id');
        var originalText = button.text();
        
        button.prop('disabled', true).text(cht_sample_frontend.i18n.adding);
        
        $.post(cht_sample_frontend.ajax_url, {
            action: 'cht_add_sample_to_cart',
            nonce: cht_sample_frontend.nonce,
            product_id: productId
        }, function(response) {
            if (response.success) {
                button.text(cht_sample_frontend.i18n.added);

                const actionType = cht_sample_frontend.after_add_to_cart;

                if (actionType === 'redirect') {
                    // Redirect to cart after delay
                    setTimeout(() => {
                        window.location.href = response.data.redirect;
                    }, 1500);
                } else if (actionType === 'custom') {
                    // Trigger custom action if defined
                    const customAction = cht_sample_frontend.custom_action;
                    if (customAction && typeof window[customAction] === 'function') {
                        window[customAction](response.data);
                    } else {
                        console.error('Custom action not found:', customAction);
                    }
                }
                
                // Show success message
                if (response.data.message) {
                    // Create or update notice container
                    var noticeContainer = $('.cht-sample-notice');
                    if (noticeContainer.length === 0) {
                        noticeContainer = $('<div class="cht-sample-notice"></div>');
                        button.closest('.cht-sample-button-container').before(noticeContainer);
                    }
                    
                }

            } else {
                button.prop('disabled', false).text(originalText);
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            button.prop('disabled', false).text(originalText);
            alert(cht_sample_frontend.i18n.error);
        });
    });
});