jQuery(document).ready(function ($) {
    $('#apwp_order_search_button').on('click', function () {
        const orderId = $('#apwp_order_search_input').val();
        if (!orderId) {
            alert('Please enter an Order ID.');
            return;
        }

        $.post(ajaxurl, { action: 'apwp_search_order', order_id: orderId }, function (response) {
            
            if (response.success) {
                $('#apwp_order_search_result').html(`<div style="color:green;">${response.data}</div>`);
            } else {
                $('#apwp_order_search_result').html(`<div style="color:red;margin-bottom:120px;">${response.data}</div>`);
            }
        });
    });

    $("#apwp_email_delay").on('change', function () {
        var delay = $(this).val();
        $('#apwp_email_order_offset').val(parseInt(delay) + 1);
    });

    // Send email to customer
    $(document).on('click', '#apwp_send_email_button', function () {
        const orderId = $(this).data('order-id');
        const templateId = $('#apwp_default_email_template_for_test').val();
        var security = APWP_scheduler.security;

        if (!orderId) {
            alert('Invalid order ID.');
            return;
        }

        // Show loading indicator
        var $button = $(this);
        $button.prop('disabled', true).text('Sending...');

        $.ajax({
            url: APWP_scheduler.ajax_url,
            type: 'POST',
            data: {
                action: 'apwp_send_email',
                order_id: orderId,
                security: security,
                template_id: templateId,
            },
            success: function (response) {
                $button.prop('disabled', false).text('Send Email to Customer');

                if (response.success) {
                    $('#apwp_order_search_result').append(
                        `<p style="color: green; font-weight: bold;">` +
                            `<strong>${new Date().toLocaleString()}</strong> -  ${response.data}` +
                        `</p>`
                    );
                } else {
                    $('#apwp_order_search_result').append(
                        `<p style="color: red;">` +
                            `<strong>${new Date().toLocaleString()}</strong> -  ${response.data}` +
                        `</p>`
                    );
                }
            },
            error: function () {
                $button.prop('disabled', false).text('Send Email to Customer');
                alert('An error occurred while sending the email.');
            },
        });
    });
});
