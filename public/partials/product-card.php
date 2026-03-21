<?php
/**
 * Product card partial.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="alynt-pg-product-card">
	<div class="alynt-pg-product-image">
		<a href="<?php echo esc_url( $product['permalink'] ); ?>">
			<?php if ( $product['image'] ) : ?>
				<img src="<?php echo esc_url( $product['image'][0] ); ?>"
					alt="<?php echo esc_attr( $product['title'] ); ?>"
					width="300"
					height="300">
			<?php else : ?>
				<div class="alynt-pg-no-image">
					<span><?php esc_html_e( 'No Image', 'alynt-products-grid' ); ?></span>
				</div>
			<?php endif; ?>
		</a>
	</div>

	<div class="alynt-pg-product-categories">
		<?php if ( ! empty( $product['categories'] ) ) : ?>
			<?php foreach ( $product['categories'] as $category ) : ?>
				<span class="alynt-pg-product-category">
					<?php echo esc_html( $category->name ); ?>
				</span>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<h3 class="alynt-pg-product-title">
		<a href="<?php echo esc_url( $product['permalink'] ); ?>">
			<?php echo esc_html( $product['title'] ); ?>
		</a>
	</h3>

	<div class="alynt-pg-pricing-tier">
		<?php
		$price_html = $product['price'];
		$tier_label = '';

		if ( preg_match( '/<span[^>]*class="special-price-label"[^>]*>(.*?)<\/span>/i', $price_html, $matches ) ) {
			$tier_label       = wp_strip_all_tags( $matches[1] );
			$product['price'] = preg_replace( '/<span[^>]*class="special-price-label"[^>]*>.*?<\/span>/i', '', $price_html );
			$product['price'] = trim( $product['price'] );
		}

		if ( ! empty( $tier_label ) ) {
			echo '<span class="alynt-pg-tier-label">' . esc_html( $tier_label ) . '</span>';
		}
		?>
	</div>

	<div class="alynt-pg-product-footer">
		<div class="alynt-pg-product-price">
			<?php echo wp_kses_post( $product['price'] ); ?>
		</div>

		<div class="alynt-pg-product-actions">
			<?php if ( $product['in_stock'] ) : ?>
				<a href="<?php echo esc_url( $product['add_to_cart_url'] ); ?>"
					class="alynt-pg-add-to-cart-btn"
					data-product-id="<?php echo esc_attr( $product['id'] ); ?>">
					<?php esc_html_e( 'Add to cart', 'alynt-products-grid' ); ?>
				</a>
			<?php else : ?>
				<span class="alynt-pg-out-of-stock">
					<?php esc_html_e( 'Out of stock', 'alynt-products-grid' ); ?>
				</span>
			<?php endif; ?>
		</div>
	</div>
</div>
