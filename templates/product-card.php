<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="alynt-pg-product-card">
    <!-- Product Image -->
    <div class="alynt-pg-product-image">
        <a href="<?php echo esc_url($product['permalink']); ?>">
            <?php if ($product['image']) : ?>
                <img src="<?php echo esc_url($product['image'][0]); ?>" 
                     alt="<?php echo esc_attr($product['title']); ?>"
                     width="300" 
                     height="300">
            <?php else : ?>
                <div class="alynt-pg-no-image">
                    <span><?php _e('No Image', 'alynt-products-grid'); ?></span>
                </div>
            <?php endif; ?>
        </a>
    </div>
    
    <!-- Product Categories -->
    <div class="alynt-pg-product-categories">
        <?php if (!empty($product['categories'])) : ?>
            <?php foreach ($product['categories'] as $category) : ?>
                <span class="alynt-pg-product-category">
                    <?php echo esc_html($category->name); ?>
                </span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Product Title -->
    <h3 class="alynt-pg-product-title">
        <a href="<?php echo esc_url($product['permalink']); ?>">
            <?php echo esc_html($product['title']); ?>
        </a>
    </h3>
    
    <!-- Pricing Tier Label Section -->
    <div class="alynt-pg-pricing-tier">
        <?php
        // Extract and remove pricing tier labels from the price HTML
        $price_html = $product['price'];
        $tier_label = '';
        
        // Extract the special-price-label content
        if (preg_match('/<span[^>]*class="special-price-label"[^>]*>(.*?)<\/span>/i', $price_html, $matches)) {
            $tier_label = strip_tags($matches[1]);
            // Remove the label from the price HTML for clean display in footer
            $product['price'] = preg_replace('/<span[^>]*class="special-price-label"[^>]*>.*?<\/span>/i', '', $price_html);
            $product['price'] = trim($product['price']);
        }
        
        // Display the tier label if found
        if (!empty($tier_label)) {
            echo '<span class="alynt-pg-tier-label">' . esc_html($tier_label) . '</span>';
        }
        ?>
    </div>
    
    <!-- Product Price and Add to Cart -->
    <div class="alynt-pg-product-footer">
        <div class="alynt-pg-product-price">
            <?php echo $product['price']; ?>
        </div>
        
        <div class="alynt-pg-product-actions">
            <?php if ($product['in_stock']) : ?>
                <a href="<?php echo esc_url($product['add_to_cart_url']); ?>" 
                   class="alynt-pg-add-to-cart-btn"
                   data-product-id="<?php echo esc_attr($product['id']); ?>">
                    <?php _e('Add to cart', 'alynt-products-grid'); ?>
                </a>
            <?php else : ?>
                <span class="alynt-pg-out-of-stock">
                    <?php _e('Out of stock', 'alynt-products-grid'); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>
