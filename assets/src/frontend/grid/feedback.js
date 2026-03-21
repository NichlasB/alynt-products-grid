export function showSpinner(instance) {
    instance.container.find('.alynt-pg-spinner').show();
    instance.container.find('.alynt-pg-products-grid').css('opacity', '0.5');
}

export function hideSpinner(instance) {
    instance.container.find('.alynt-pg-spinner').hide();
    instance.container.find('.alynt-pg-products-grid').css('opacity', '1');
}

export function updateResultsCount(instance, data) {
    const start = (data.current_page - 1) * instance.settings.perPage + 1;
    const end = Math.min(data.current_page * instance.settings.perPage, data.total);
    const text = `${start} - ${end} of ${data.total} products`;

    instance.container.find('.alynt-pg-showing').text(text);
}

export function showModal(message) {
    const $ = window.jQuery;

    $('.alynt-pg-modal').remove();

    const modal = $(`
        <div class="alynt-pg-modal">
            <div class="alynt-pg-modal-overlay"></div>
            <div class="alynt-pg-modal-content">
                <div class="alynt-pg-modal-message">${message}</div>
            </div>
        </div>
    `);

    $('body').append(modal);
    modal.fadeIn(200);

    setTimeout(() => {
        modal.fadeOut(200, function() {
            $(this).remove();
        });
    }, 1000);
}

export function showNotification(instance, message, type = 'error') {
    const $ = window.jQuery;

    if (type !== 'error') {
        return;
    }

    $('.alynt-pg-notification').remove();

    const notification = $(`
        <div class="alynt-pg-notification alynt-pg-notification-${type}">
            <span class="alynt-pg-notification-message">${message}</span>
            <button class="alynt-pg-notification-close">&times;</button>
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
