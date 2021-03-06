jQuery(document).ready(function () {

    jQuery('a.buy_button').tipsy({
        trigger: 'manual',
        gravity: 's',
        fade: true
    });

    jQuery('a.buy_button').bind('click', function (e) {
        var id = jQuery(this).attr('id').replace('buy-product-', '');
        var caller = e.target;
        jQuery(caller).attr('title', 'please wait...').tipsy('show');

        jQuery.ajax({
            type: 'get',
            url: shop_helper.ajaxurl + '?action=ajax-call-front&component=shop&con=cart&task=add_to_cart&id=' + id,
            success: function (data, text) {

                switch (data.action) {

                    case "msg":

                        jQuery('#orillacart_cart_widget_container').block({
                            message: null,
                            overlayCSS: {
                                background: '#fff ',
                                opacity: 0.6
                            }

                        });

                        jQuery('#orillacart_cart_widget').html(data.cart_ajax_data);
                        jQuery('#orillacart_cart_widget_container').unblock();

                        jQuery(caller).attr('title', data.data).tipsy('show');
                        setTimeout(function () {
                            jQuery(caller).tipsy('hide');

                        }, 3000);
                        break;

                    case "redirect":
                        window.location = data.data;
                        break;
                }
            },
            error: function (request, status, error) {
                throw(request.responseText);

            }
        });

        return false;

    });

    jQuery('form#product_addtocart_form').validate({
        errorPlacement: function (error, element) {
            jQuery(error).css('display', 'block');
            if (element.is(':checkbox')) {
                if (element.attr('name') == 'files[]') {
                    error.insertBefore(element, element.parent());
                } else {
                    error.insertBefore(element.parent(), element.parent().parent());
                }
            } else {
                error.insertBefore(element, element.parent());
            }
        },
        submitHandler: function (form) {

            var url = shop_helper.ajaxurl + '?action=ajax-call-front&component=shop&con=cart&task=add_to_cart_custom';
            var data = jQuery(form).serialize();

            jQuery('#submit-form-container').block({
                message: null,
                overlayCSS: {
                    background: '#fff ',
                    opacity: 0.6
                }
            });

            jQuery('button.addToCartButton', form).tipsy({
                trigger: 'manual',
                gravity: 's',
                fade: true
            }).attr('title', 'Please wait...').tipsy('show');

            jQuery.post(url, data, function (data) {

                switch (data.action) {
                    case "msg":
                        jQuery('#orillacart_cart_widget_container').block({
                            message: null,
                            overlayCSS: {
                                background: '#fff ',
                                opacity: 0.6
                            }

                        });

                        jQuery('#orillacart_cart_widget').html(data.cart_ajax_data);
                        jQuery('#orillacart_cart_widget_container').unblock();
                        jQuery('button.addToCartButton', form).attr('title', data.data).tipsy('show');

                        setTimeout(function () {
                            jQuery('button.addToCartButton', form).tipsy('hide');

                        }, 3000);
                        break;
                    case "redirect":
                        window.location = data.data;
                        break;
                }
            }).complete(function () {
                jQuery('#submit-form-container').unblock();
            });

        }
    });

});
