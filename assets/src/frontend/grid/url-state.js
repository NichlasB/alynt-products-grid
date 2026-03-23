function canUseUrlState(instance) {
    return Boolean(
        instance
        && instance.urlStateEnabled
        && typeof window.URL !== 'undefined'
        && typeof window.URLSearchParams !== 'undefined'
        && window.history
        && typeof window.history.pushState === 'function'
    );
}

export function updateUrlFromState(instance) {
    if (!canUseUrlState(instance)) {
        return;
    }

    const url = new URL(window.location.href);
    const params = url.searchParams;

    params.delete('categories');
    params.delete('search');
    params.delete('page');

    if (instance.hasCategoryFilters && instance.currentCategories.length > 0) {
        params.set('categories', instance.currentCategories.join(','));
    }

    if (instance.hasSearchField && instance.currentSearch) {
        params.set('search', instance.currentSearch);
    }

    if (instance.currentPage > 1) {
        params.set('page', String(instance.currentPage));
    }

    const searchString = params.toString();
    const newUrl = `${url.pathname}${searchString ? `?${searchString}` : ''}${url.hash || ''}`;
    window.history.pushState({
        categories: instance.currentCategories,
        search: instance.currentSearch,
        page: instance.currentPage
    }, '', newUrl);
}

export function loadStateFromUrl(instance) {
    const urlParams = canUseUrlState(instance) ? new URLSearchParams(window.location.search) : new URLSearchParams();

    if (instance.hasCategoryFilters) {
        const categories = urlParams.get('categories');
        if (categories) {
            const catArray = categories.split(',').map((category) => category.trim());
            if (catArray.length > 0 && !Number.isNaN(Number(catArray[0]))) {
                const idToSlug = Object.fromEntries(
                    Object.entries(instance.categoryMap).map(([slug, id]) => [id, slug])
                );
                instance.currentCategories = catArray
                    .map((id) => idToSlug[parseInt(id, 10)])
                    .filter((slug) => slug);
            } else {
                instance.currentCategories = catArray.filter((slug) => instance.categoryMap[slug]);
            }
        } else {
            instance.currentCategories = [];
        }
    } else {
        instance.currentCategories = [];
    }

    instance.currentSearch = instance.hasSearchField ? (urlParams.get('search') || '').trim() : '';

    const parsedPage = parseInt(urlParams.get('page'), 10);
    instance.currentPage = parsedPage > 0 ? parsedPage : 1;

    instance.container.find('.alynt-pg-category-btn').removeClass('active');

    if (instance.hasSearchField) {
        instance.container.find('.alynt-pg-search').val(instance.currentSearch);
    }

    if (instance.hasCategoryFilters) {
        if (instance.currentCategories.length === 0) {
            instance.container.find('.alynt-pg-category-btn[data-category="all"]').addClass('active');
        } else {
            instance.currentCategories.forEach((categorySlug) => {
                instance.container.find(`.alynt-pg-category-btn[data-category="${categorySlug}"]`).addClass('active');
            });
        }
    }

    instance.loadProducts(instance.shouldRefreshCategoryCounts());
}
