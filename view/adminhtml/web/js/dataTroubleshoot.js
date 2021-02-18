define(['jquery', 'domReady!'], function ($) {
    'use strict';

    /**
     * Initializer
     * @param {String} url
     */
    function initTroubleshoot(url) {

        $('#kl_troubleshoot_reset_button').click(function () {
            $('#kl_troubleshoot_response').html('');
        });

        $('#kl_product_id').on('keydown', function(e) {
            if (e.which === 13 || e.keyCode === 13) {
                $('#kl_troubleshoot_submit_button').click();
				return false;
            }
        });

        $('#kl_troubleshoot_submit_button').click(function () {

            $('#kl_troubleshoot_response').html('');
            $('#kl_troubleshoot_error').html('');
            let store_id = $('#kl_store_id').val(),
                product_id = parseInt($('#kl_product_id').val());

            if (store_id && product_id > 0) {
                $('#kl_troubleshoot_submit_button').find('span').text('Submitting');
                $.post(url, {
                    store_id: store_id,
                    product_id: product_id
                }, function (response) {
                    $('#kl_troubleshoot_response').html(response.blockdata);
                    $('#kl_troubleshoot_submit_button').find('span').text('Submit');
                    //window.location.reload();
                });
            } else if (isNaN(product_id) || product_id < 1) {
                $('#kl_troubleshoot_error').html('Invalid Product ID found');
            } else {
                $('#kl_product_id').focus();
                $('#kl_troubleshoot_error').html('Fill out mandatory fields');
            }

        });	
    }

    /**
     * Export/return data
     * @param {Object} data
     */
    return function (data) {
        initTroubleshoot(data.url);
    };
});
