import { handleAddToCart } from './cart.js';
import { generatePaginationHtml } from './pagination.js';
import { applyResponsiveBreakpoints } from './responsive.js';
import { hideSpinner, showModal, showNotification, showSpinner, updateResultsCount } from './feedback.js';
import { loadStateFromUrl, updateUrlFromState } from './url-state.js';

export class AlyntProductsGrid {
    constructor(container) {
        this.container = window.jQuery(container);
        this.currentCategories = [];
        this.currentSearch = '';
        this.currentPage = 1;
        this.isLoading = false;
        this.settings = {
            columns: parseInt(this.container.data('columns'), 10) || 4,
            perPage: parseInt(this.container.data('per-page'), 10) || 12,
            breakpoint5: parseInt(this.container.data('breakpoint-5'), 10) || 1200,
            breakpoint4: parseInt(this.container.data('breakpoint-4'), 10) || 992,
            breakpoint3: parseInt(this.container.data('breakpoint-3'), 10) || 768,
            breakpoint2: parseInt(this.container.data('breakpoint-2'), 10) || 576
        };
        this.allCategories = JSON.parse(this.container.find('.alynt-pg-all-categories').val() || '[]');
        this.categoryMap = JSON.parse(this.container.find('.alynt-pg-category-map').val() || '{}');

        this.init();
    }

    init() {
        this.bindEvents();
        applyResponsiveBreakpoints(this);
        loadStateFromUrl(this);
        this.updateCategoryCounts();
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

        this.container.on('click', '.alynt-pg-add-to-cart-btn:not(.view-cart)', (event) => {
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
        this.loadProducts();
        this.updateCategoryCounts();
    }

    handleSearch(searchTerm) {
        this.currentSearch = searchTerm.trim();
        this.currentPage = 1;
        this.loadProducts();
        this.updateCategoryCounts();
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

        this.loadProducts();
        this.updateCategoryCounts();
    }

    loadProducts() {
        const $ = window.jQuery;

        if (this.isLoading) {
            return;
        }

        this.isLoading = true;
        showSpinner(this);

        const categoryIds = this.currentCategories
            .map((slug) => this.categoryMap[slug])
            .filter((id) => id);

        $.post(alynt_pg_ajax.ajax_url, {
            action: 'alynt_pg_filter_products',
            nonce: alynt_pg_ajax.nonce,
            categories: categoryIds,
            search: this.currentSearch,
            page: this.currentPage,
            per_page: this.settings.perPage,
            _cache_bust: Date.now()
        })
            .done((response) => {
                if (!response.success) {
                    return;
                }

                this.updateProductsGrid(response.data);
                this.updatePagination(response.data);
                updateResultsCount(this, response.data);
                updateUrlFromState(this);
            })
            .fail(() => {
                showNotification(this, (window.alynt_pg_ajax || {}).i18n_failed_to_load || '', 'error');
            })
            .always(() => {
                hideSpinner(this);
                this.isLoading = false;
            });
    }

    updateCategoryCounts() {
        const $ = window.jQuery;
        const categoryIds = this.currentCategories
            .map((slug) => this.categoryMap[slug])
            .filter((id) => id);

        $.post(alynt_pg_ajax.ajax_url, {
            action: 'alynt_pg_get_category_counts',
            nonce: alynt_pg_ajax.nonce,
            categories: categoryIds,
            search: this.currentSearch,
            all_categories: this.allCategories,
            _cache_bust: Date.now()
        })
            .done((response) => {
                if (response.success) {
                    this.updateCategoryButtons(response.data);
                }
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
                $btn.addClass('disabled');
            } else {
                $btn.removeClass('disabled');
            }
        });
    }

    updateProductsGrid(data) {
        this.container.find('.alynt-pg-products-grid').html(data.products_html);
    }

    updatePagination(data) {
        this.container.find('.alynt-pg-pagination').replaceWith(generatePaginationHtml(data));
    }

    showModal(message) {
        showModal(message);
    }

    showNotification(message, type = 'error') {
        showNotification(this, message, type);
    }
}
