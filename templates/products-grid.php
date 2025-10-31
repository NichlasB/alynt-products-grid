<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="alynt-pg-container" 
     data-columns="<?php echo esc_attr($atts['columns']); ?>"
     data-per-page="<?php echo esc_attr($atts['per_page']); ?>"
     data-breakpoint-5="<?php echo esc_attr($atts['breakpoint_5']); ?>"
     data-breakpoint-4="<?php echo esc_attr($atts['breakpoint_4']); ?>"
     data-breakpoint-3="<?php echo esc_attr($atts['breakpoint_3']); ?>"
     data-breakpoint-2="<?php echo esc_attr($atts['breakpoint_2']); ?>">
     
    <!-- Category Filters -->
    <div class="alynt-pg-filters">
        <div class="alynt-pg-category-filters">
            <button class="alynt-pg-category-btn active" data-category="all">
                <?php _e('All', 'alynt-products-grid'); ?>
            </button>
            <?php
            // Separate special and regular categories
            $special_cats = array();
            $regular_cats = array();
            
            foreach ($categories as $category) {
                if (in_array($category->term_id, $special_categories)) {
                    $special_cats[] = $category;
                } else {
                    $regular_cats[] = $category;
                }
            }
            
            // Sort both groups alphabetically by name
            usort($special_cats, function($a, $b) {
                return strcmp($a->name, $b->name);
            });
            usort($regular_cats, function($a, $b) {
                return strcmp($a->name, $b->name);
            });
            
            // Render special categories first
            foreach ($special_cats as $category) : ?>
                <button class="alynt-pg-category-btn alynt-pg-category-special" 
                        data-category="<?php echo esc_attr($category->slug); ?>"
                        data-category-id="<?php echo esc_attr($category->term_id); ?>"
                        data-category-slug="<?php echo esc_attr($category->slug); ?>"
                        data-special="true">
                    <?php echo esc_html($category->name); ?>
                    <span class="alynt-pg-category-count">(<?php echo $category->count; ?>)</span>
                </button>
            <?php endforeach;
            
            // Render regular categories
            foreach ($regular_cats as $category) : ?>
                <button class="alynt-pg-category-btn" 
                        data-category="<?php echo esc_attr($category->slug); ?>"
                        data-category-id="<?php echo esc_attr($category->term_id); ?>"
                        data-category-slug="<?php echo esc_attr($category->slug); ?>">
                    <?php echo esc_html($category->name); ?>
                    <span class="alynt-pg-category-count">(<?php echo $category->count; ?>)</span>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Search Field -->
        <div class="alynt-pg-search-wrapper">
            <input type="text" 
                   class="alynt-pg-search" 
                   placeholder="<?php _e('Search products...', 'alynt-products-grid'); ?>">
        </div>
        
        <!-- Reset Button -->
        <button class="alynt-pg-reset-btn">
            <?php _e('Reset', 'alynt-products-grid'); ?>
        </button>
    </div>
    
    <!-- Results Count -->
    <div class="alynt-pg-results-count">
        <span class="alynt-pg-showing">
            <?php 
            $start = ($products_data['current_page'] - 1) * $atts['per_page'] + 1;
            $end = min($products_data['current_page'] * $atts['per_page'], $products_data['total']);
            printf(
                __('%d - %d of %d products', 'alynt-products-grid'),
                $start,
                $end,
                $products_data['total']
            );
            ?>
        </span>
    </div>
    
    <!-- Loading Spinner -->
    <div class="alynt-pg-spinner" style="display: none;">
        <div class="alynt-pg-spinner-inner"></div>
    </div>
    
    <!-- Products Grid -->
    <div class="alynt-pg-products-grid" style="--columns: <?php echo esc_attr($atts['columns']); ?>;">
        <?php if (!empty($products_data['products'])) : ?>
            <?php foreach ($products_data['products'] as $product) : ?>
                <?php include 'product-card.php'; ?>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="alynt-pg-no-products">
                <p><?php _e('No products found.', 'alynt-products-grid'); ?></p>
                <?php if (current_user_can('manage_options')) : ?>
                    <p><small>Debug: Total products in query: <?php echo $products_data['total']; ?></small></p>
                    <p><small>Debug: Check your WordPress error log for more details.</small></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($products_data['pages'] > 1) : ?>
        <div class="alynt-pg-pagination">
            <?php
            $current_page = $products_data['current_page'];
            $total_pages = $products_data['pages'];
            
            // Previous button
            if ($current_page > 1) : ?>
                <button class="alynt-pg-page-btn alynt-pg-prev" data-page="<?php echo $current_page - 1; ?>">
                    <?php _e('« Previous', 'alynt-products-grid'); ?>
                </button>
            <?php endif;
            
            // Page numbers
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1) : ?>
                <button class="alynt-pg-page-btn" data-page="1">1</button>
                <?php if ($start_page > 2) : ?>
                    <span class="alynt-pg-ellipsis">...</span>
                <?php endif;
            endif;
            
            for ($i = $start_page; $i <= $end_page; $i++) : ?>
                <button class="alynt-pg-page-btn <?php echo ($i == $current_page) ? 'active' : ''; ?>" 
                        data-page="<?php echo $i; ?>">
                    <?php echo $i; ?>
                </button>
            <?php endfor;
            
            if ($end_page < $total_pages) :
                if ($end_page < $total_pages - 1) : ?>
                    <span class="alynt-pg-ellipsis">...</span>
                <?php endif; ?>
                <button class="alynt-pg-page-btn" data-page="<?php echo $total_pages; ?>">
                    <?php echo $total_pages; ?>
                </button>
            <?php endif;
            
            // Next button
            if ($current_page < $total_pages) : ?>
                <button class="alynt-pg-page-btn alynt-pg-next" data-page="<?php echo $current_page + 1; ?>">
                    <?php _e('Next »', 'alynt-products-grid'); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- Hidden data for JS -->
    <input type="hidden" class="alynt-pg-all-categories" 
           value="<?php echo esc_attr(json_encode(array_column($categories, 'term_id'))); ?>">
    <input type="hidden" class="alynt-pg-category-map" 
           value="<?php echo esc_attr(json_encode(array_combine(array_column($categories, 'slug'), array_column($categories, 'term_id')))); ?>">
    <input type="hidden" class="alynt-pg-restricted-categories" 
           value="<?php echo esc_attr(json_encode($restricted_categories)); ?>">
</div>
