export function generatePaginationHtml(data) {
    const i18n = window.alynt_pg_ajax || {};

    if (data.pages <= 1) {
        return '<div class="alynt-pg-pagination"></div>';
    }

    let html = '<div class="alynt-pg-pagination">';

    if (data.current_page > 1) {
        html += `<button class="alynt-pg-page-btn alynt-pg-prev" data-page="${data.current_page - 1}" aria-label="${i18n.i18n_prev_page || 'Previous page'}">${i18n.i18n_previous || ''}</button>`;
    }

    const startPage = Math.max(1, data.current_page - 2);
    const endPage = Math.min(data.pages, data.current_page + 2);

    if (startPage > 1) {
        const firstPageLabel = (i18n.i18n_page_label || 'Page %s').replace('%s', 1);
        html += `<button class="alynt-pg-page-btn" data-page="1" aria-label="${firstPageLabel}">1</button>`;
        if (startPage > 2) {
            html += '<span class="alynt-pg-ellipsis" aria-hidden="true">...</span>';
        }
    }

    for (let i = startPage; i <= endPage; i += 1) {
        const activeClass = i === data.current_page ? ' active' : '';
        const ariaCurrent = i === data.current_page ? ' aria-current="page"' : '';
        const pageLabel = (i18n.i18n_page_label || 'Page %s').replace('%s', i);
        html += `<button class="alynt-pg-page-btn${activeClass}" data-page="${i}" aria-label="${pageLabel}"${ariaCurrent}>${i}</button>`;
    }

    if (endPage < data.pages) {
        if (endPage < data.pages - 1) {
            html += '<span class="alynt-pg-ellipsis" aria-hidden="true">...</span>';
        }
        const lastPageLabel = (i18n.i18n_page_label || 'Page %s').replace('%s', data.pages);
        html += `<button class="alynt-pg-page-btn" data-page="${data.pages}" aria-label="${lastPageLabel}">${data.pages}</button>`;
    }

    if (data.current_page < data.pages) {
        html += `<button class="alynt-pg-page-btn alynt-pg-next" data-page="${data.current_page + 1}" aria-label="${i18n.i18n_next_page || 'Next page'}">${i18n.i18n_next || ''}</button>`;
    }

    html += '</div>';

    return html;
}
