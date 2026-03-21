(function($) {
    'use strict';

    class AlyntProductsGrid {
        constructor(container) {
            this.container = $(container);
            this.currentCategories = []; // Now stores slugs instead of IDs
            this.currentSearch = '';
            this.currentPage = 1;
            this.isLoading = false;
            
            // Get settings from data attributes
            this.settings = {
                columns: parseInt(this.container.data('columns')) || 4,
                perPage: parseInt(this.container.data('per-page')) || 12,
                breakpoint5: parseInt(this.container.data('breakpoint-5')) || 1200,
                breakpoint4: parseInt(this.container.data('breakpoint-4')) || 992,
                breakpoint3: parseInt(this.container.data('breakpoint-3')) || 768,
                breakpoint2: parseInt(this.container.data('breakpoint-2')) || 576
            };
            
            // Get category data
            this.allCategories = JSON.parse(this.container.find('.alynt-pg-all-categories').val() || '[]');
            this.categoryMap = JSON.parse(this.container.find('.alynt-pg-category-map').val() || '{}'); // slug -> ID mapping
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.applyResponsiveBreakpoints();
            this.loadStateFromUrl();
            
            // Initial category counts update
            this.updateCategoryCounts();
        }

        bindEvents() {
            const self = this;
            
            // Category filter buttons
            this.container.on('click', '.alynt-pg-category-btn:not(.disabled)', function(e) {
                e.preventDefault();
                self.handleCategoryFilter($(this));
            });
            
            // Search input
            let searchTimeout;
            this.container.on('input', '.alynt-pg-search', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    self.handleSearch($(this).val());
                }, 300);
            });
            
            // Reset button
            this.container.on('click', '.alynt-pg-reset-btn', function(e) {
                e.preventDefault();
                self.resetFilters();
            });
            
            // Pagination
            this.container.on('click', '.alynt-pg-page-btn', function(e) {
                e.preventDefault();
                const page = parseInt($(this).data('page'));
                if (page && page !== self.currentPage) {
                    self.handlePagination(page);
                }
            });
            
            // Handle browser back/forward
            $(window).on('popstate', function(e) {
                if (e.originalEvent.state) {
                    self.loadStateFromUrl();
                }
            });
            
            // Add to cart functionality
            this.container.on('click', '.alynt-pg-add-to-cart-btn:not(.view-cart)', function(e) {
                e.preventDefault();
                self.handleAddToCart($(this));
            });
            
            // Prevent WooCommerce from adding automatic "View cart" buttons
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
                if (button && button.hasClass('alynt-pg-add-to-cart-btn')) {
                    // Remove any WooCommerce auto-generated buttons in our product cards
                    setTimeout(() => {
                        self.container.find('.alynt-pg-product-card .added_to_cart').remove();
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
                // Toggle category (using slug now)
                const index = this.currentCategories.indexOf(categorySlug);
                if (index > -1) {
                    this.currentCategories.splice(index, 1);
                    btn.removeClass('active');
                } else {
                    this.currentCategories.push(categorySlug);
                    btn.addClass('active');
                }
                
                // Update "All" button
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
            
            // Scroll to top of grid
            $('html, body').animate({
                scrollTop: this.container.offset().top - 50
            }, 500);
        }

        resetFilters() {
            this.currentCategories = [];
            this.currentSearch = '';
            this.currentPage = 1;
            
            // Reset UI
            this.container.find('.alynt-pg-category-btn').removeClass('active');
            this.container.find('.alynt-pg-category-btn[data-category="all"]').addClass('active');
            this.container.find('.alynt-pg-search').val('');
            
            this.loadProducts();
            this.updateCategoryCounts();
        }

        loadProducts() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showSpinner();
            
            // Convert slugs to IDs for the backend
            const categoryIds = this.currentCategories.map(slug => this.categoryMap[slug]).filter(id => id);
            
            const data = {
                action: 'alynt_pg_filter_products',
                nonce: alynt_pg_ajax.nonce,
                categories: categoryIds,
                search: this.currentSearch,
                page: this.currentPage,
                per_page: this.settings.perPage,
                _cache_bust: Date.now()
            };
            
            $.post(alynt_pg_ajax.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.updateProductsGrid(response.data);
                        this.updatePagination(response.data);
                        this.updateResultsCount(response.data);
                        this.updateUrlFromState();
                    }
                })
                .fail(() => {
                    this.showNotification('Failed to load products', 'error');
                })
                .always(() => {
                    this.hideSpinner();
                    this.isLoading = false;
                });
        }

        updateCategoryCounts() {
            // Convert slugs to IDs for the backend
            const categoryIds = this.currentCategories.map(slug => this.categoryMap[slug]).filter(id => id);
            
            const data = {
                action: 'alynt_pg_get_category_counts',
                nonce: alynt_pg_ajax.nonce,
                categories: categoryIds,
                search: this.currentSearch,
                all_categories: this.allCategories,
                _cache_bust: Date.now()
            };
            
            $.post(alynt_pg_ajax.ajax_url, data)
                .done((response) => {
                    if (response.success) {
                        this.updateCategoryButtons(response.data);
                    }
                });
        }

        updateCategoryButtons(counts) {
            this.container.find('.alynt-pg-category-btn').each(function() {
                const $btn = $(this);
                const categorySlug = $btn.data('category');
                
                if (categorySlug === 'all') return;
                
                // Use category-id attribute since backend returns counts indexed by ID
                const categoryId = $btn.data('category-id');
                const count = counts[categoryId] || 0;
                const $countSpan = $btn.find('.alynt-pg-category-count');
                
                $countSpan.text(`(${count})`);
                
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
            const paginationHtml = this.generatePaginationHtml(data);
            this.container.find('.alynt-pg-pagination').replaceWith(paginationHtml);
        }

        generatePaginationHtml(data) {
            if (data.pages <= 1) {
                return '<div class="alynt-pg-pagination"></div>';
            }
            
            let html = '<div class="alynt-pg-pagination">';
            
            // Previous button
            if (data.current_page > 1) {
                html += `<button class="alynt-pg-page-btn alynt-pg-prev" data-page="${data.current_page - 1}">« Previous</button>`;
            }
            
            // Page numbers
            const startPage = Math.max(1, data.current_page - 2);
            const endPage = Math.min(data.pages, data.current_page + 2);
            
            if (startPage > 1) {
                html += '<button class="alynt-pg-page-btn" data-page="1">1</button>';
                if (startPage > 2) {
                    html += '<span class="alynt-pg-ellipsis">...</span>';
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = (i === data.current_page) ? ' active' : '';
                html += `<button class="alynt-pg-page-btn${activeClass}" data-page="${i}">${i}</button>`;
            }
            
            if (endPage < data.pages) {
                if (endPage < data.pages - 1) {
                    html += '<span class="alynt-pg-ellipsis">...</span>';
                }
                html += `<button class="alynt-pg-page-btn" data-page="${data.pages}">${data.pages}</button>`;
            }
            
            // Next button
            if (data.current_page < data.pages) {
                html += `<button class="alynt-pg-page-btn alynt-pg-next" data-page="${data.current_page + 1}">Next »</button>`;
            }
            
            html += '</div>';
            return html;
        }

        updateResultsCount(data) {
            const start = (data.current_page - 1) * this.settings.perPage + 1;
            const end = Math.min(data.current_page * this.settings.perPage, data.total);
            const text = `${start} - ${end} of ${data.total} products`;
            
            this.container.find('.alynt-pg-showing').text(text);
        }

        showSpinner() {
            this.container.find('.alynt-pg-spinner').show();
            this.container.find('.alynt-pg-products-grid').css('opacity', '0.5');
        }

        hideSpinner() {
            this.container.find('.alynt-pg-spinner').hide();
            this.container.find('.alynt-pg-products-grid').css('opacity', '1');
        }

        updateUrlFromState() {
            const url = new URL(window.location);
            const params = [];
            
            // Use slugs in the URL (build manually to avoid encoding commas)
            if (this.currentCategories.length > 0) {
                params.push('categories=' + this.currentCategories.join(','));
            }
            
            if (this.currentSearch) {
                params.push('search=' + encodeURIComponent(this.currentSearch));
            }
            
            if (this.currentPage > 1) {
                params.push('page=' + this.currentPage);
            }
            
            // Update URL without page reload
            const newUrl = params.length > 0 ? `${url.pathname}?${params.join('&')}` : url.pathname;
            window.history.pushState({
                categories: this.currentCategories,
                search: this.currentSearch,
                page: this.currentPage
            }, '', newUrl);
        }

        loadStateFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Load categories (now expects slugs, but handle IDs for backwards compatibility)
            const categories = urlParams.get('categories');
            if (categories) {
                const catArray = categories.split(',').map(c => c.trim());
                // Check if they're numeric IDs (backwards compatibility) or slugs
                if (catArray.length > 0 && !isNaN(catArray[0])) {
                    // Old format with IDs - convert to slugs
                    const slugToId = this.categoryMap;
                    const idToSlug = Object.fromEntries(Object.entries(slugToId).map(([k, v]) => [v, k]));
                    this.currentCategories = catArray.map(id => idToSlug[parseInt(id)]).filter(slug => slug);
                } else {
                    // New format with slugs
                    this.currentCategories = catArray.filter(slug => this.categoryMap[slug]);
                }
            } else {
                this.currentCategories = [];
            }
            
            // Load search
            this.currentSearch = urlParams.get('search') || '';
            
            // Load page
            this.currentPage = parseInt(urlParams.get('page')) || 1;
            
            // Update UI
            this.container.find('.alynt-pg-category-btn').removeClass('active');
            this.container.find('.alynt-pg-search').val(this.currentSearch);
            
            if (this.currentCategories.length === 0) {
                this.container.find('.alynt-pg-category-btn[data-category="all"]').addClass('active');
            } else {
                this.currentCategories.forEach(catSlug => {
                    this.container.find(`.alynt-pg-category-btn[data-category="${catSlug}"]`).addClass('active');
                });
            }
            
            // Load products with current state
            this.loadProducts();
        }

        applyResponsiveBreakpoints() {
            const style = document.createElement('style');
            style.textContent = `
                @media (max-width: ${this.settings.breakpoint5}px) {
                    .alynt-pg-container[data-columns="${this.settings.columns}"] .alynt-pg-products-grid {
                        grid-template-columns: repeat(${Math.min(4, this.settings.columns)}, 1fr);
                    }
                }
                @media (max-width: ${this.settings.breakpoint4}px) {
                    .alynt-pg-container[data-columns="${this.settings.columns}"] .alynt-pg-products-grid {
                        grid-template-columns: repeat(${Math.min(3, this.settings.columns)}, 1fr);
                    }
                }
                @media (max-width: ${this.settings.breakpoint3}px) {
                    .alynt-pg-container[data-columns="${this.settings.columns}"] .alynt-pg-products-grid {
                        grid-template-columns: repeat(${Math.min(2, this.settings.columns)}, 1fr);
                    }
                }
                @media (max-width: ${this.settings.breakpoint2}px) {
                    .alynt-pg-container[data-columns="${this.settings.columns}"] .alynt-pg-products-grid {
                        grid-template-columns: 1fr;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        handleAddToCart(btn) {
            const productId = btn.data('product-id');
            const originalText = btn.text();
            
            // Show loading state
            btn.prop('disabled', true)
               .html('<span class="alynt-pg-spinner-small"></span> Adding...')
               .addClass('loading');
            
            // AJAX add to cart
            const data = {
                action: 'woocommerce_add_to_cart',
                product_id: productId,
                quantity: 1
            };
            
            $.post(wc_add_to_cart_params.ajax_url, data)
                .done((response) => {
                    if (response.error) {
                        // Handle error
                        btn.prop('disabled', false)
                           .text(originalText)
                           .removeClass('loading');
                        this.showNotification('Error adding product to cart', 'error');
                    } else {
                        // Success - change to "View cart"
                        btn.text('View cart')
                           .removeClass('loading')
                           .addClass('view-cart')
                           .attr('href', wc_add_to_cart_params.cart_url)
                           .prop('disabled', false)
                           .off('click') // Remove the add to cart handler
                           .removeAttr('data-product-id'); // Remove product ID to prevent re-adding
                        
                        // Update cart fragments if available
                        if (response.fragments) {
                            $.each(response.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                        }
                        
                        // Remove any WooCommerce auto-generated cart links in this product card
                        const productCard = btn.closest('.alynt-pg-product-card');
                        productCard.find('.added_to_cart').remove();
                        
                        // Show success modal
                        this.showModal('Product added to cart successfully!');
                        
                        // Trigger cart updated event (but prevent WooCommerce from adding its button)
                        $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, btn]);
                        
                        // Clean up any WooCommerce buttons that might be added after the event
                        setTimeout(() => {
                            productCard.find('.added_to_cart').remove();
                        }, 100);
                    }
                })
                .fail(() => {
                    // Handle AJAX failure
                    btn.prop('disabled', false)
                       .text(originalText)
                       .removeClass('loading');
                    this.showModal('Failed to add product to cart');
                });
        }

        showModal(message) {
            // Remove existing modals
            $('.alynt-pg-modal').remove();
            
            // Create modal
            const modal = $(`
                <div class="alynt-pg-modal">
                    <div class="alynt-pg-modal-overlay"></div>
                    <div class="alynt-pg-modal-content">
                        <div class="alynt-pg-modal-message">${message}</div>
                    </div>
                </div>
            `);
            
            // Add to body
            $('body').append(modal);
            
            // Show modal with animation
            modal.fadeIn(200);
            
            // Auto-hide after 1 second
            setTimeout(() => {
                modal.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 1000);
        }

        showNotification(message, type = 'error') {
            // Keep this for error messages only
            if (type === 'error') {
                // Remove existing notifications
                $('.alynt-pg-notification').remove();
                
                // Create notification
                const notification = $(`
                    <div class="alynt-pg-notification alynt-pg-notification-${type}">
                        <span class="alynt-pg-notification-message">${message}</span>
                        <button class="alynt-pg-notification-close">&times;</button>
                    </div>
                `);
                
                // Add to container
                this.container.prepend(notification);
                
                // Auto-hide after 5 seconds
                setTimeout(() => {
                    notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
                
                // Handle close button
                notification.find('.alynt-pg-notification-close').on('click', function() {
                    notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                });
            }
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        $('.alynt-pg-container').each(function() {
            new AlyntProductsGrid(this);
        });
    });

})(jQuery);
