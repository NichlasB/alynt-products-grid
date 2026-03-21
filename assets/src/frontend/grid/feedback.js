export function showSpinner(instance) {
    instance.container.attr('aria-busy', 'true');
    instance.container.find('.alynt-pg-spinner').show();
    instance.container.find('.alynt-pg-products-grid').css('opacity', '0.5');
}

export function hideSpinner(instance) {
    instance.container.removeAttr('aria-busy');
    instance.container.find('.alynt-pg-spinner').hide();
    instance.container.find('.alynt-pg-products-grid').css('opacity', '1');
}

export function updateResultsCount(instance, data) {
    const i18n = window.alynt_pg_ajax || {};
    const start = (data.current_page - 1) * instance.settings.perPage + 1;
    const end = Math.min(data.current_page * instance.settings.perPage, data.total);
    const template = Number(data.total) === 1 ? i18n.i18n_results_count_singular : i18n.i18n_results_count_plural;
    const text = (template || '')
        .replace('%1$s', start)
        .replace('%2$s', end)
        .replace('%3$s', data.total);

    instance.container.find('.alynt-pg-showing').text(text);
}

export function showModal(message) {
    const $ = window.jQuery;
    const i18n = window.alynt_pg_ajax || {};

    $('.alynt-pg-modal').remove();

    const triggerElement = document.activeElement;

    const modal = $(`
        <div class="alynt-pg-modal" role="dialog" aria-modal="true" aria-label="${i18n.i18n_cart_notification || 'Cart notification'}">
            <div class="alynt-pg-modal-overlay"></div>
            <div class="alynt-pg-modal-content" tabindex="-1">
                <div class="alynt-pg-modal-message">${message}</div>
            </div>
        </div>
    `);

    $('body').append(modal);
    modal.fadeIn(200, function() {
        modal.find('.alynt-pg-modal-content').focus();
    });

    setTimeout(() => {
        modal.fadeOut(200, function() {
            $(this).remove();
            if (triggerElement) {
                $(triggerElement).focus();
            }
        });
    }, 1000);
}

export function showNotification(instance, message, type = 'error') {
    const $ = window.jQuery;

    if (type !== 'error') {
        return;
    }

    $('.alynt-pg-notification').remove();

    const i18n = window.alynt_pg_ajax || {};
    const notification = $(`
        <div class="alynt-pg-notification alynt-pg-notification-${type}" role="alert">
            <span class="alynt-pg-notification-message">${message}</span>
            <button class="alynt-pg-notification-close" aria-label="${i18n.i18n_close_notification || 'Close notification'}">&times;</button>
        </div>
    `);

    instance.container.prepend(notification);

    setTimeout(() => {
        notification.fadeOut(300, function() {
            $(this).remove();
        });
    }, 5000);

    notification.find('.alynt-pg-notification-close').on('click', function() {
        notification.fadeOut(300, function() {
            $(this).remove();
        });
    });
}
