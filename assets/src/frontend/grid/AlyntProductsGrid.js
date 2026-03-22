import { handleAddToCart } from './cart.js';
import { generatePaginationHtml } from './pagination.js';
import { getAjaxErrorDetails } from './request-errors.js';
import { applyResponsiveBreakpoints } from './responsive.js';
import { hideSpinner, renderEmptyState, setCategoryCountsLoading, showModal, showNotification, showSpinner, updateResultsCount } from './feedback.js';
import { loadStateFromUrl, updateUrlFromState } from './url-state.js';

const PRODUCTS_REQUEST_TIMEOUT_MS = 15000;
const COUNTS_REQUEST_TIMEOUT_MS = 10000;

export class AlyntProductsGrid {
    constructor(container) {
        this.container = window.jQuery(container);
        this.currentCategories = [];
        this.currentSearch = '';
        this.currentPage = 1;
        this.isLoading = false;
        this.productsRequest = null;
        this.categoryCountsRequest = null;
        this.pendingCategoryCountsRefresh = false;
        this.settings = {
            columns: parseInt(this.container.data('columns'), 10) || 4,
            perPage: parseInt(this.container.data('per-page'), 10) || 12,
            breakpoint5: parseInt(this.container.data('breakpoint-5'), 10) || 1200,
            breakpoint4: parseInt(this.container.data('breakpoint-4'), 10) || 992,
            breakpoint3: parseInt(this.container.data('breakpoint-3'), 10) || 768,
            breakpoint2: parseInt(this.container.data('breakpoint-2'), 10) || 576
        };
        this.allCategories = this.parseJsonData('.alynt-pg-all-categories', []);
        this.categoryMap = this.parseJsonData('.alynt-pg-category-map', {});
        this.gridContext = this.parseJsonData('.alynt-pg-grid-context', null);
        this.gridSignature = String(this.container.find('.alynt-pg-grid-signature').val() || '');
        this.urlStateEnabled = window.jQuery('.alynt-pg-container').length === 1;

        this.init();
    }

    parseJsonData(selector, fallbackValue) {
        const rawValue = this.container.find(selector).val();

        if (!rawValue) {
            return fallbackValue;
        }

        try {
            return JSON.parse(rawValue);
        } catch (error) {
            return fallbackValue;
        }
    }

    init() {
        this.bindEvents();
        applyResponsiveBreakpoints(this);
        loadStateFromUrl(this);
    }

    bindEvents() {
        const $ = window.jQuery;
        let searchTimeout;

        this.container.on('click', '.alynt-pg-category-btn:not(.disabled)', (event) => {
            event.preventDefault();
            this.handleCategoryFilter($(event.currentTarget));
        });

        this.container.on('input', '.alynt-pg-search', (event) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.handleSearch($(event.currentTarget).val());
            }, 300);
        });

        this.container.on('click', '.alynt-pg-reset-btn', (event) => {
            event.preventDefault();
            this.resetFilters();
        });

        this.container.on('click', '.alynt-pg-page-btn', (event) => {
            event.preventDefault();
            const page = parseInt($(event.currentTarget).data('page'), 10);

            if (page && page !== this.currentPage) {
                this.handlePagination(page);
            }
        });

        $(window).on('popstate', (event) => {
            if (event.originalEvent.state) {
                loadStateFromUrl(this);
            }
        });

        this.container.on('click', '.alynt-pg-add-to-cart-btn[data-product-id]:not(.view-cart)', (event) => {
            event.preventDefault();
            handleAddToCart(this, $(event.currentTarget));
        });

        $(document.body).on('added_to_cart', (event, fragments, cartHash, button) => {
            if (button && button.hasClass('alynt-pg-add-to-cart-btn')) {
                setTimeout(() => {
                    this.container.find('.alynt-pg-product-card .added_to_cart').remove();
                }, 50);
            }
        });
    }

    getSelectedCategoryIds() {
        return this.currentCategories
            .map((slug) => this.categoryMap[slug])
            .filter((id) => id);
    }

    getGridContextPayload() {
        if (!this.gridContext) {
            return '';
        }

        return JSON.stringify(this.gridContext);
    }

    getRetryAction(handler) {
        const i18n = window.alynt_pg_ajax || {};

        if (typeof handler !== 'function') {
            return {};
        }

        return {
            action: {
                label: i18n.i18n_retry || 'Retry',
                handler
            }
        };
    }

    hasErrorNotification() {
        return this.container.find('.alynt-pg-notification-error').length > 0;
    }

    handleCategoryFilter(btn) {
        const categorySlug = btn.data('category');

        if (categorySlug === 'all') {
            this.currentCategories = [];
            this.container.find('.alynt-pg-category-btn').removeClass('active');
            btn.addClass('active');
        } else {
            const index = this.currentCategories.indexOf(categorySlug);

            if (index > -1) {
                this.currentCategories.splice(index, 1);
                btn.removeClass('active');
            } else {
                this.currentCategories.push(categorySlug);
                btn.addClass('active');
            }

            this.container.find('.alynt-pg-category-btn[data-category="all"]')
                .toggleClass('active', this.currentCategories.length === 0);
        }

        this.currentPage = 1;
        this.loadProducts(true);
    }

    handleSearch(searchTerm) {
        this.currentSearch = searchTerm.trim();
        this.currentPage = 1;
        this.loadProducts(true);
    }

    handlePagination(page) {
        this.currentPage = page;
        this.loadProducts();

        window.jQuery('html, body').animate({
            scrollTop: this.container.offset().top - 50
        }, 500);
    }

    resetFilters() {
        this.currentCategories = [];
        this.currentSearch = '';
        this.currentPage = 1;

        this.container.find('.alynt-pg-category-btn').removeClass('active');
        this.container.find('.alynt-pg-category-btn[data-category="all"]').addClass('active');
        this.container.find('.alynt-pg-search').val('');

        this.loadProducts(true);
    }

    loadProducts(shouldRefreshCategoryCounts = false) {
        const $ = window.jQuery;
        const i18n = window.alynt_pg_ajax || {};

        this.pendingCategoryCountsRefresh = this.pendingCategoryCountsRefresh || Boolean(shouldRefreshCategoryCounts);

        if (this.productsRequest) {
            const previousRequest = this.productsRequest;
            this.productsRequest = null;
            previousRequest.abort();
        }

        this.isLoading = true;
        showSpinner(this);

        const request = $.ajax({
            url: alynt_pg_ajax.ajax_url,
            method: 'POST',
            timeout: PRODUCTS_REQUEST_TIMEOUT_MS,
            data: {
                action: 'alynt_pg_filter_products',
                nonce: alynt_pg_ajax.nonce,
                categories: this.getSelectedCategoryIds(),
                search: this.currentSearch,
                page: this.currentPage,
                per_page: this.settings.perPage,
                grid_context: this.getGridContextPayload(),
                grid_signature: this.gridSignature
            }
        });

        this.productsRequest = request;

        request
            .done((response, textStatus, jqXHR) => {
                if (!response || response.success !== true || !response.data) {
                    const errorDetails = getAjaxErrorDetails(jqXHR, textStatus, i18n, i18n.i18n_failed_to_load || '');

                    if (errorDetails.aborted) {
                        return;
                    }

                    this.showNotification(
                        errorDetails.message || i18n.i18n_failed_to_load || '',
                        'error',
                        this.getRetryAction(() => this.loadProducts())
                    );
                    return;
                }

                this.currentPage = Math.max(1, parseInt(response.data.current_page, 10) || 1);
                this.updateProductsGrid(response.data);
                this.updatePagination(response.data);
                updateResultsCount(this, response.data);
                updateUrlFromState(this);

                if (this.pendingCategoryCountsRefresh) {
                    this.pendingCategoryCountsRefresh = false;
                    this.updateCategoryCounts();
                }
            })
            .fail((jqXHR, textStatus) => {
                const errorDetails = getAjaxErrorDetails(jqXHR, textStatus, i18n, i18n.i18n_failed_to_load || '');

                if (errorDetails.aborted) {
                    return;
                }

                this.showNotification(
                    errorDetails.message || i18n.i18n_failed_to_load || '',
                    'error',
                    errorDetails.allowRetry ? this.getRetryAction(() => this.loadProducts(this.pendingCategoryCountsRefresh)) : {}
                );
            })
            .always(() => {
                if (this.productsRequest !== request) {
                    return;
                }

                this.productsRequest = null;
                this.isLoading = false;
                hideSpinner(this);
            });
    }

    updateCategoryCounts() {
        const $ = window.jQuery;
        const i18n = window.alynt_pg_ajax || {};

        if (this.categoryCountsRequest) {
            const previousRequest = this.categoryCountsRequest;
            this.categoryCountsRequest = null;
            previousRequest.abort();
        }

        setCategoryCountsLoading(this, true);

        const request = $.ajax({
            url: alynt_pg_ajax.ajax_url,
            method: 'POST',
            timeout: COUNTS_REQUEST_TIMEOUT_MS,
            data: {
                action: 'alynt_pg_get_category_counts',
                nonce: alynt_pg_ajax.nonce,
                categories: this.getSelectedCategoryIds(),
                search: this.currentSearch,
                all_categories: this.allCategories,
                grid_context: this.getGridContextPayload(),
                grid_signature: this.gridSignature
            }
        });

        this.categoryCountsRequest = request;

        request
            .done((response, textStatus, jqXHR) => {
                if (!response || response.success !== true || !response.data) {
                    const errorDetails = getAjaxErrorDetails(jqXHR, textStatus, i18n, i18n.i18n_counts_failed || '');

                    if (errorDetails.aborted) {
                        return;
                    }

                    this.resetCategoryButtonAvailability();

                    if (!this.hasErrorNotification()) {
                        this.showNotification(
                            errorDetails.message || i18n.i18n_counts_failed || '',
                            'error',
                            errorDetails.allowRetry ? this.getRetryAction(() => this.updateCategoryCounts()) : {}
                        );
                    }
                    return;
                }

                this.updateCategoryButtons(response.data);
            })
            .fail((jqXHR, textStatus) => {
                const errorDetails = getAjaxErrorDetails(jqXHR, textStatus, i18n, i18n.i18n_counts_failed || '');

                if (errorDetails.aborted) {
                    return;
                }

                this.resetCategoryButtonAvailability();

                if (!this.hasErrorNotification()) {
                    this.showNotification(
                        errorDetails.message || i18n.i18n_counts_failed || '',
                        'error',
                        errorDetails.allowRetry ? this.getRetryAction(() => this.updateCategoryCounts()) : {}
                    );
                }
            })
            .always(() => {
                if (this.categoryCountsRequest !== request) {
                    return;
                }

                this.categoryCountsRequest = null;
                setCategoryCountsLoading(this, false);
            });
    }

    resetCategoryButtonAvailability() {
        this.container.find('.alynt-pg-category-btn').each(function() {
            const $btn = window.jQuery(this);

            if ($btn.data('category') === 'all') {
                return;
            }

            $btn.removeClass('disabled')
                .removeAttr('aria-disabled')
                .prop('disabled', false);
        });
    }

    updateCategoryButtons(counts) {
        this.container.find('.alynt-pg-category-btn').each(function() {
            const $btn = window.jQuery(this);
            const categorySlug = $btn.data('category');

            if (categorySlug === 'all') {
                return;
            }

            const categoryId = $btn.data('category-id');
            const count = counts[categoryId] || 0;
            $btn.find('.alynt-pg-category-count').text(`(${count})`);

            if (count === 0 && !$btn.hasClass('active')) {
                $btn.addClass('disabled').attr('aria-disabled', 'true').prop('disabled', true);
            } else {
                $btn.removeClass('disabled').removeAttr('aria-disabled').prop('disabled', false);
            }
        });
    }

    updateProductsGrid(data) {
        const i18n = window.alynt_pg_ajax || {};
        const productsHtml = data.products_html || renderEmptyState(
            i18n.i18n_no_products_title || '',
            i18n.i18n_no_products_message || ''
        );

        this.container.find('.alynt-pg-products-grid').html(productsHtml);
    }

    updatePagination(data) {
        const paginationHtml = generatePaginationHtml(data);
        const pagination = this.container.find('.alynt-pg-pagination');

        if (pagination.length > 0) {
            pagination.replaceWith(paginationHtml);
            return;
        }

        this.container.find('.alynt-pg-products-grid').after(paginationHtml);
    }

    showModal(message) {
        showModal(message);
    }

    showNotification(message, type = 'error', options = {}) {
        showNotification(this, message, type, options);
    }
}
