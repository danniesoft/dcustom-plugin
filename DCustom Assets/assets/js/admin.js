jQuery(document).ready(function ($) {

    // --- Snippet Deactivation Confirmation ---
    $('.dcustom-assets-wrapper').on('change', '.dcustom-switch input[type="checkbox"]', function (e) {
        const $checkbox = $(this);
        if (!$checkbox.is(':checked')) {
            const assetName = $checkbox.data('asset-name');
            const assetType = $checkbox.data('asset-type');
            let message = DCustomAssets.confirm_deactivate_message.replace('%s', assetName);
            
            if (assetType === 'php') {
                message = DCustomAssets.confirm_php_deactivate_message.replace('%s', assetName);
            }
            
            if (!confirm(message)) {
                e.preventDefault(); // Stop the change
            }
        }
    });

    // --- Individual Snippet Delete Confirmation ---
    $('.dcustom-assets-table').on('click', '.submitdelete', function (e) {
        if (!confirm(DCustomAssets.confirm_delete_message)) {
            e.preventDefault();
        }
    });

});