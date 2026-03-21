import { showModal, showNotification } from './feedback.js';

export function handleAddToCart(instance, btn) {
    const $ = window.jQuery;
    const i18n = window.alynt_pg_ajax || {};
    const productId = btn.data('product-id');
    const originalText = btn.text();

    btn.prop('disabled', true)
        .html(`<span class="alynt-pg-spinner-small"></span> ${i18n.i18n_adding || ''}`)
        .addClass('loading');

    $.post(wc_add_to_cart_params.ajax_url, {
        action: 'woocommerce_add_to_cart',
        product_id: productId,
        quantity: 1
    })
        .done((response) => {
            if (response.error) {
                btn.prop('disabled', false)
                    .text(originalText)
                    .removeClass('loading');
                showNotification(instance, i18n.i18n_error_add_to_cart || '', 'error');
                return;
            }

            btn.text(i18n.i18n_view_cart || '')
                .removeClass('loading')
                .addClass('view-cart')
                .attr('href', wc_add_to_cart_params.cart_url)
                .prop('disabled', false)
                .off('click')
                .removeAttr('data-product-id');

            if (response.fragments) {
                $.each(response.fragments, function(key, value) {
                    $(key).replaceWith(value);
                });
            }

            const productCard = btn.closest('.alynt-pg-product-card');
            productCard.find('.added_to_cart').remove();

            showModal(i18n.i18n_added_successfully || '');

            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, btn]);

            setTimeout(() => {
                productCard.find('.added_to_cart').remove();
            }, 100);
        })
        .fail(() => {
            btn.prop('disabled', false)
                .text(originalText)
                .removeClass('loading');
            showModal(i18n.i18n_failed_add_to_cart || '');
        });
}
