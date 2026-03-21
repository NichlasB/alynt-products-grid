export function updateUrlFromState(instance) {
    const url = new URL(window.location);
    const params = [];

    if (instance.currentCategories.length > 0) {
        params.push(`categories=${instance.currentCategories.join(',')}`);
    }

    if (instance.currentSearch) {
        params.push(`search=${encodeURIComponent(instance.currentSearch)}`);
    }

    if (instance.currentPage > 1) {
        params.push(`page=${instance.currentPage}`);
    }

    const newUrl = params.length > 0 ? `${url.pathname}?${params.join('&')}` : url.pathname;
    window.history.pushState({
        categories: instance.currentCategories,
        search: instance.currentSearch,
        page: instance.currentPage
    }, '', newUrl);
}

export function loadStateFromUrl(instance) {
    const urlParams = new URLSearchParams(window.location.search);

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

    instance.currentSearch = urlParams.get('search') || '';
    instance.currentPage = parseInt(urlParams.get('page'), 10) || 1;

    instance.container.find('.alynt-pg-category-btn').removeClass('active');
    instance.container.find('.alynt-pg-search').val(instance.currentSearch);

    if (instance.currentCategories.length === 0) {
        instance.container.find('.alynt-pg-category-btn[data-category="all"]').addClass('active');
    } else {
        instance.currentCategories.forEach((categorySlug) => {
            instance.container.find(`.alynt-pg-category-btn[data-category="${categorySlug}"]`).addClass('active');
        });
    }

    instance.loadProducts();
}
