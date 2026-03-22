import { showModal, showNotification } from './feedback.js';
import { getAjaxErrorDetails } from './request-errors.js';

const ADD_TO_CART_TIMEOUT_MS = 12000;

function restoreButton(btn, originalText) {
    btn.prop('disabled', false)
        .removeAttr('aria-busy')
        .text(originalText)
        .removeClass('loading');
}

export function handleAddToCart(instance, btn) {
    const $ = window.jQuery;
    const i18n = window.alynt_pg_ajax || {};
    const productId = btn.data('product-id');
    const originalText = btn.text();

    if (!productId) {
        showNotification(instance, i18n.i18n_error_add_to_cart || i18n.i18n_unexpected_error || '', 'error');
        return;
    }

    if (typeof window.wc_add_to_cart_params === 'undefined' || !wc_add_to_cart_params.ajax_url) {
        showNotification(instance, i18n.i18n_failed_add_to_cart || i18n.i18n_unexpected_error || '', 'error');
        return;
    }

    btn.prop('disabled', true)
        .attr('aria-busy', 'true')
        .html(`<span class="alynt-pg-spinner-small"></span> ${i18n.i18n_adding || ''}`)
        .addClass('loading');

    $.ajax({
        url: wc_add_to_cart_params.ajax_url,
        method: 'POST',
        timeout: ADD_TO_CART_TIMEOUT_MS,
        data: {
            action: 'woocommerce_add_to_cart',
            product_id: productId,
            quantity: 1
        }
    })
        .done((response) => {
            if (response && response.error) {
                restoreButton(btn, originalText);

                if (response.product_url) {
                    showNotification(instance, i18n.i18n_choose_options || i18n.i18n_error_add_to_cart || '', 'error', {
                        action: {
                            label: i18n.i18n_view_product || i18n.i18n_view_cart || '',
                            handler: () => {
                                window.location.href = response.product_url;
                            }
                        }
                    });
                    return;
                }

                showNotification(instance, i18n.i18n_error_add_to_cart || i18n.i18n_unexpected_error || '', 'error');
                return;
            }

            btn.removeAttr('aria-busy')
                .text(i18n.i18n_view_cart || '')
                .removeClass('loading')
                .addClass('view-cart')
                .attr('href', wc_add_to_cart_params.cart_url)
                .prop('disabled', false)
                .off('click')
                .removeAttr('data-product-id');

            if (response && response.fragments) {
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
        .fail((jqXHR, textStatus) => {
            restoreButton(btn, originalText);

            const errorDetails = getAjaxErrorDetails(jqXHR, textStatus, i18n, i18n.i18n_failed_add_to_cart || '');
            if (errorDetails.aborted) {
                return;
            }

            showNotification(
                instance,
                errorDetails.message || i18n.i18n_failed_add_to_cart || '',
                'error',
                errorDetails.allowRetry ? {
                    action: {
                        label: i18n.i18n_retry || 'Retry',
                        handler: () => {
                            handleAddToCart(instance, btn);
                        }
                    }
                } : {}
            );
        });
}
