<?php
/**
 * Products grid partial.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$category_ids = wp_list_pluck( $categories, 'term_id' );
$category_map = array_combine( wp_list_pluck( $categories, 'slug' ), $category_ids );
if ( false === $category_map ) {
	$category_map = array();
}
?>
<div class="alynt-pg-container"
	data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
	data-per-page="<?php echo esc_attr( $atts['per_page'] ); ?>"
	data-breakpoint-5="<?php echo esc_attr( $atts['breakpoint_5'] ); ?>"
	data-breakpoint-4="<?php echo esc_attr( $atts['breakpoint_4'] ); ?>"
	data-breakpoint-3="<?php echo esc_attr( $atts['breakpoint_3'] ); ?>"
	data-breakpoint-2="<?php echo esc_attr( $atts['breakpoint_2'] ); ?>">

	<div class="alynt-pg-filters">
		<div class="alynt-pg-category-filters">
			<button class="alynt-pg-category-btn active" data-category="all">
				<?php esc_html_e( 'All', 'alynt-products-grid' ); ?>
			</button>
			<?php
			$special_cats = array();
			$regular_cats = array();

			foreach ( $categories as $category ) {
				if ( in_array( $category->term_id, $special_categories, true ) ) {
					$special_cats[] = $category;
				} else {
					$regular_cats[] = $category;
				}
			}

			usort(
				$special_cats,
				function ( $a, $b ) {
					return strcmp( $a->name, $b->name );
				}
			);
			usort(
				$regular_cats,
				function ( $a, $b ) {
					return strcmp( $a->name, $b->name );
				}
			);

			foreach ( $special_cats as $category ) :
				?>
				<button class="alynt-pg-category-btn alynt-pg-category-special"
						data-category="<?php echo esc_attr( $category->slug ); ?>"
						data-category-id="<?php echo esc_attr( $category->term_id ); ?>">
					<?php echo esc_html( $category->name ); ?>
					<span class="alynt-pg-category-count"><?php echo '(' . esc_html( $category->count ) . ')'; ?></span>
				</button>
				<?php
			endforeach;

			foreach ( $regular_cats as $category ) :
				?>
				<button class="alynt-pg-category-btn"
						data-category="<?php echo esc_attr( $category->slug ); ?>"
						data-category-id="<?php echo esc_attr( $category->term_id ); ?>">
					<?php echo esc_html( $category->name ); ?>
					<span class="alynt-pg-category-count"><?php echo '(' . esc_html( $category->count ) . ')'; ?></span>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="alynt-pg-search-wrapper">
			<input type="text"
					class="alynt-pg-search"
					placeholder="<?php echo esc_attr__( 'Search products...', 'alynt-products-grid' ); ?>">
		</div>

		<button class="alynt-pg-reset-btn">
			<?php esc_html_e( 'Reset', 'alynt-products-grid' ); ?>
		</button>
	</div>

	<div class="alynt-pg-results-count">
		<span class="alynt-pg-showing">
			<?php
			$start = ( $products_data['current_page'] - 1 ) * $atts['per_page'] + 1;
			$end   = min( $products_data['current_page'] * $atts['per_page'], $products_data['total'] );
			/* translators: 1: range start, 2: range end, 3: total number of products. */
			$results_count_label = _n( '%1$s - %2$s of %3$s product', '%1$s - %2$s of %3$s products', (int) $products_data['total'], 'alynt-products-grid' );
			echo esc_html( sprintf( $results_count_label, absint( $start ), absint( $end ), absint( $products_data['total'] ) ) );
			?>
		</span>
	</div>

	<div class="alynt-pg-spinner" style="display: none;">
		<div class="alynt-pg-spinner-inner"></div>
	</div>

	<div class="alynt-pg-products-grid" style="--columns: <?php echo esc_attr( $atts['columns'] ); ?>;">
		<?php if ( ! empty( $products_data['products'] ) ) : ?>
			<?php foreach ( $products_data['products'] as $product ) : ?>
				<?php include ALYNT_PG_PLUGIN_DIR . 'public/partials/product-card.php'; ?>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="alynt-pg-no-products">
				<p><?php esc_html_e( 'No products found.', 'alynt-products-grid' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $products_data['pages'] > 1 ) : ?>
		<div class="alynt-pg-pagination">
			<?php
			$current_page = $products_data['current_page'];
			$total_pages  = $products_data['pages'];

			if ( $current_page > 1 ) :
				?>
				<button class="alynt-pg-page-btn alynt-pg-prev" data-page="<?php echo esc_attr( $current_page - 1 ); ?>">
					<?php esc_html_e( '« Previous', 'alynt-products-grid' ); ?>
				</button>
				<?php
			endif;

			$start_page = max( 1, $current_page - 2 );
			$end_page   = min( $total_pages, $current_page + 2 );

			if ( $start_page > 1 ) :
				?>
				<button class="alynt-pg-page-btn" data-page="1">1</button>
				<?php if ( $start_page > 2 ) : ?>
					<span class="alynt-pg-ellipsis">...</span>
					<?php
				endif;
			endif;

			for ( $i = $start_page; $i <= $end_page; $i++ ) :
				?>
				<button class="alynt-pg-page-btn <?php echo esc_attr( $i === $current_page ? 'active' : '' ); ?>"
						data-page="<?php echo esc_attr( $i ); ?>">
					<?php echo esc_html( $i ); ?>
				</button>
				<?php
			endfor;

			if ( $end_page < $total_pages ) :
				if ( $end_page < $total_pages - 1 ) :
					?>
					<span class="alynt-pg-ellipsis">...</span>
				<?php endif; ?>
				<button class="alynt-pg-page-btn" data-page="<?php echo esc_attr( $total_pages ); ?>">
					<?php echo esc_html( $total_pages ); ?>
				</button>
				<?php
			endif;

			if ( $current_page < $total_pages ) :
				?>
				<button class="alynt-pg-page-btn alynt-pg-next" data-page="<?php echo esc_attr( $current_page + 1 ); ?>">
					<?php esc_html_e( 'Next »', 'alynt-products-grid' ); ?>
				</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<input type="hidden" class="alynt-pg-all-categories"
			value="<?php echo esc_attr( wp_json_encode( $category_ids ) ); ?>">
	<input type="hidden" class="alynt-pg-category-map"
			value="<?php echo esc_attr( wp_json_encode( $category_map ) ); ?>">
</div>
