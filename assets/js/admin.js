jQuery(document).ready(function($) {
    // Create sample product
    $('.cht-create-sample').click(function() {
        var button = $(this);
        var productId = button.data('product-id');
        
        button.prop('disabled', true).text('Creating...');
        
        $.post(cht_sample_products.ajax_url, {
            action: 'cht_create_sample_product',
            product_id: productId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                button.prop('disabled', false).text('Create Sample Product');
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Delete sample product
    $('.cht-delete-sample').click(function() {
        if (!confirm('Are you sure you want to delete this sample product?')) return;
        
        var button = $(this);
        var productId = button.data('product-id');
        
        button.prop('disabled', true).text('Deleting...');
        
        $.post(cht_sample_products.ajax_url, {
            action: 'cht_delete_sample_product',
            product_id: productId
        }, function() {
            location.reload();
        });
    });
});
