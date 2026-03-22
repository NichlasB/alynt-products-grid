function getServerMessage(jqXHR) {
    if (!jqXHR || !jqXHR.responseJSON) {
        return '';
    }

    if (jqXHR.responseJSON.data && typeof jqXHR.responseJSON.data.message === 'string') {
        return jqXHR.responseJSON.data.message.trim();
    }

    if (typeof jqXHR.responseJSON.message === 'string') {
        return jqXHR.responseJSON.message.trim();
    }

    return '';
}

function getServerCode(jqXHR) {
    if (!jqXHR || !jqXHR.responseJSON || !jqXHR.responseJSON.data) {
        return '';
    }

    if (typeof jqXHR.responseJSON.data.code === 'string') {
        return jqXHR.responseJSON.data.code.trim();
    }

    return '';
}

export function getAjaxErrorDetails(jqXHR, textStatus, i18n, fallbackMessage = '') {
    if (textStatus === 'abort') {
        return {
            aborted: true,
            allowRetry: false,
            message: ''
        };
    }

    const serverMessage = getServerMessage(jqXHR);
    const serverCode = getServerCode(jqXHR);
    if (serverMessage) {
        return {
            aborted: false,
            allowRetry: jqXHR.status !== 403 && !['alynt_pg_invalid_context', 'alynt_pg_session_expired', 'alynt_pg_forbidden'].includes(serverCode),
            message: serverMessage
        };
    }

    if (textStatus === 'timeout') {
        return {
            aborted: false,
            allowRetry: true,
            message: i18n.i18n_timeout_error || fallbackMessage
        };
    }

    if ((jqXHR && jqXHR.status === 403) || (jqXHR && jqXHR.responseText === '-1')) {
        return {
            aborted: false,
            allowRetry: false,
            message: i18n.i18n_session_expired || fallbackMessage
        };
    }

    if (typeof window.navigator !== 'undefined' && window.navigator.onLine === false) {
        return {
            aborted: false,
            allowRetry: true,
            message: i18n.i18n_offline_error || fallbackMessage
        };
    }

    if (jqXHR && jqXHR.status >= 500) {
        return {
            aborted: false,
            allowRetry: true,
            message: i18n.i18n_server_error || fallbackMessage
        };
    }

    if (jqXHR && jqXHR.status === 0) {
        return {
            aborted: false,
            allowRetry: true,
            message: i18n.i18n_network_error || i18n.i18n_offline_error || fallbackMessage
        };
    }

    return {
        aborted: false,
        allowRetry: true,
        message: fallbackMessage || i18n.i18n_unexpected_error || ''
    };
}
