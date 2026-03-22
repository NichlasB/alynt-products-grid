function closeNotification(notification) {
    notification.fadeOut(300, function() {
        window.jQuery(this).remove();
    });
}

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

export function setCategoryCountsLoading(instance, isLoading) {
    const filters = instance.container.find('.alynt-pg-category-filters');

    filters.toggleClass('is-updating', Boolean(isLoading));

    if (isLoading) {
        filters.attr('aria-busy', 'true');
    } else {
        filters.removeAttr('aria-busy');
    }
}

export function updateResultsCount(instance, data) {
    const i18n = window.alynt_pg_ajax || {};
    const total = Number(data.total) || 0;

    if (total < 1) {
        instance.container.find('.alynt-pg-showing').text(i18n.i18n_results_count_empty || '');
        return;
    }

    const currentPage = Math.max(1, parseInt(data.current_page, 10) || 1);
    const perPage = Math.max(1, parseInt(instance.settings.perPage, 10) || 1);
    const start = (currentPage - 1) * perPage + 1;
    const end = Math.min(currentPage * perPage, total);
    const template = total === 1 ? i18n.i18n_results_count_singular : i18n.i18n_results_count_plural;
    const text = (template || '')
        .replace('%1$s', start)
        .replace('%2$s', end)
        .replace('%3$s', total);

    instance.container.find('.alynt-pg-showing').text(text);
}

export function renderEmptyState(title, message = '') {
    const $ = window.jQuery;
    const wrapper = $('<div />', { class: 'alynt-pg-no-products' });

    wrapper.append($('<p />', { text: title }));

    if (message) {
        wrapper.append($('<small />', { text: message }));
    }

    return $('<div />').append(wrapper).html();
}

export function showModal(message, options = {}) {
    if (!message) {
        return null;
    }

    const $ = window.jQuery;
    const i18n = window.alynt_pg_ajax || {};
    const duration = typeof options.duration === 'number' ? options.duration : 1500;

    $('.alynt-pg-modal').remove();

    const triggerElement = document.activeElement;
    const modal = $('<div />', {
        class: 'alynt-pg-modal',
        role: 'dialog',
        'aria-modal': 'true',
        'aria-label': i18n.i18n_cart_notification || 'Cart notification'
    });

    modal.append($('<div />', { class: 'alynt-pg-modal-overlay' }));

    const content = $('<div />', {
        class: 'alynt-pg-modal-content',
        tabindex: '-1'
    });

    content.append($('<div />', {
        class: 'alynt-pg-modal-message',
        text: message
    }));

    modal.append(content);

    $('body').append(modal);
    modal.fadeIn(200, function() {
        content.trigger('focus');
    });

    setTimeout(() => {
        modal.fadeOut(200, function() {
            $(this).remove();

            if (triggerElement) {
                $(triggerElement).trigger('focus');
            }
        });
    }, duration);

    return modal;
}

export function showNotification(instance, message, type = 'error', options = {}) {
    if (!instance || !message) {
        return null;
    }

    const $ = window.jQuery;
    const i18n = window.alynt_pg_ajax || {};
    const autoHideMs = typeof options.autoHideMs === 'number' ? options.autoHideMs : (type === 'error' ? 0 : 5000);

    instance.container.find('.alynt-pg-notification').remove();

    const notification = $('<div />', {
        class: `alynt-pg-notification alynt-pg-notification-${type}`,
        role: type === 'error' ? 'alert' : 'status',
        'aria-live': type === 'error' ? 'assertive' : 'polite'
    });

    notification.append($('<span />', {
        class: 'alynt-pg-notification-message',
        text: message
    }));

    const actions = $('<div />', {
        class: 'alynt-pg-notification-actions'
    });

    if (options.action && options.action.label && typeof options.action.handler === 'function') {
        const actionButton = $('<button />', {
            type: 'button',
            class: 'alynt-pg-notification-action',
            text: options.action.label
        });

        actionButton.on('click', () => {
            closeNotification(notification);
            options.action.handler();
        });

        actions.append(actionButton);
    }

    const closeButton = $('<button />', {
        type: 'button',
        class: 'alynt-pg-notification-close',
        'aria-label': i18n.i18n_close_notification || 'Close notification'
    }).html('&times;');

    closeButton.on('click', () => {
        closeNotification(notification);
    });

    actions.append(closeButton);
    notification.append(actions);

    instance.container.prepend(notification);

    if (autoHideMs > 0) {
        setTimeout(() => {
            closeNotification(notification);
        }, autoHideMs);
    }

    return notification;
}
