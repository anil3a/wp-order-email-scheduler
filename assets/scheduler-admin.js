jQuery(document).ready(function ($) {
    $('#APWP_order_search_button').on('click', function () {
        const orderId = $('#APWP_order_search_input').val();
        if (!orderId) {
            alert('Please enter an Order ID.');
            return;
        }

        $.post(ajaxurl, { action: 'APWP_search_order', order_id: orderId }, function (response) {
            if (response.success) {
                $('#APWP_order_search_result').html(`<div style="color:green;">${response.data}</div>`);
            } else {
                $('#APWP_order_search_result').html(`<div style="color:red;margin-bottom:120px;">${response.data}</div>`);
            }
        });
    });
    // Send email to customer
    $(document).on('click', '#APWP_send_email_button', function () {
        const orderId = $(this).data('order-id');
        var orderID = $(this).data('order-id');
        var security = APWP_scheduler.security;

        if (!orderID) {
            alert('Invalid order ID.');
            return;
        }

        // Show loading indicator
        var $button = $(this);
        $button.prop('disabled', true).text('Sending...');


        if (!orderId) {
            alert('Order ID is missing.');
            return;
        }

        $.ajax({
            url: APWP_scheduler.ajax_url,
            type: 'POST',
            data: {
                action: 'APWP_send_email',
                order_id: orderID,
                security: security,
            },
            success: function (response) {
                $button.prop('disabled', false).text('Send Email to Customer');
                if (response.success) {
                    $('#APWP_order_search_result').append(
                        `<p style="color: green; font-weight: bold;">` +
                            `<strong>${new Date().toLocaleString()}</strong> -  ${response.data}` +
                        `</p>`
                    );
                } else {
                    $('#APWP_order_search_result').append(
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