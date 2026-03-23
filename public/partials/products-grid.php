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

$results_total     = isset( $products_data['total'] ) ? (int) $products_data['total'] : 0;
$current_page      = isset( $products_data['current_page'] ) ? max( 1, (int) $products_data['current_page'] ) : 1;
$products_per_page = isset( $atts['per_page'] ) ? max( 1, (int) $atts['per_page'] ) : 12;
$total_pages       = isset( $products_data['pages'] ) ? (int) $products_data['pages'] : 0;

if ( empty( $empty_state_title ) ) {
	$empty_state_title = __( 'No products found.', 'alynt-products-grid' );
}

if ( empty( $empty_state_message ) ) {
	$empty_state_message = __( 'Try a different search term or reset the filters to see more products.', 'alynt-products-grid' );
}

$filter_mode            = isset( $atts['filter_mode'] ) ? (string) $atts['filter_mode'] : 'default';
$show_filters_container = 'none' !== $filter_mode;
$show_category_filters  = 'default' === $filter_mode;
$show_search            = 'none' !== $filter_mode;
$search_input_id        = sprintf( 'alynt-pg-search-%s', sanitize_html_class( $grid_signature ) );
$reset_button_label     = 'search' === $filter_mode
	? __( 'Clear', 'alynt-products-grid' )
	: __( 'Reset', 'alynt-products-grid' );
?>
<div class="alynt-pg-container"
	data-columns="<?php echo esc_attr( $atts['columns'] ); ?>"
	data-per-page="<?php echo esc_attr( $atts['per_page'] ); ?>"
	data-breakpoint-5="<?php echo esc_attr( $atts['breakpoint_5'] ); ?>"
	data-breakpoint-4="<?php echo esc_attr( $atts['breakpoint_4'] ); ?>"
	data-breakpoint-3="<?php echo esc_attr( $atts['breakpoint_3'] ); ?>"
	data-breakpoint-2="<?php echo esc_attr( $atts['breakpoint_2'] ); ?>">

	<?php if ( ! empty( $grid_notice_message ) ) : ?>
		<div class="alynt-pg-notification alynt-pg-notification-<?php echo esc_attr( $grid_notice_type ); ?>" role="<?php echo esc_attr( 'error' === $grid_notice_type ? 'alert' : 'status' ); ?>">
			<span class="alynt-pg-notification-message"><?php echo esc_html( $grid_notice_message ); ?></span>
		</div>
	<?php endif; ?>

	<?php if ( $show_filters_container ) : ?>
		<div class="alynt-pg-filters">
			<?php if ( $show_category_filters ) : ?>
				<div class="alynt-pg-category-filters" role="group" aria-label="<?php esc_attr_e( 'Filter by category', 'alynt-products-grid' ); ?>">
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
			<?php endif; ?>

			<?php if ( $show_search ) : ?>
				<div class="alynt-pg-search-wrapper">
					<label class="screen-reader-text" for="<?php echo esc_attr( $search_input_id ); ?>">
						<?php esc_html_e( 'Search products', 'alynt-products-grid' ); ?>
					</label>
					<input type="text"
							id="<?php echo esc_attr( $search_input_id ); ?>"
							class="alynt-pg-search"
							placeholder="<?php echo esc_attr__( 'Search products...', 'alynt-products-grid' ); ?>">
				</div>
			<?php endif; ?>

			<button class="alynt-pg-reset-btn">
				<?php echo esc_html( $reset_button_label ); ?>
			</button>
		</div>
	<?php endif; ?>

	<div class="alynt-pg-results-count" aria-live="polite" aria-atomic="true">
		<span class="alynt-pg-showing">
			<?php
			if ( $results_total > 0 ) {
				$start = ( $current_page - 1 ) * $products_per_page + 1;
				$end   = min( $current_page * $products_per_page, $results_total );
				/* translators: 1: range start, 2: range end, 3: total number of products. */
				$results_count_label = _n( '%1$s - %2$s of %3$s product', '%1$s - %2$s of %3$s products', $results_total, 'alynt-products-grid' );
				echo esc_html( sprintf( $results_count_label, absint( $start ), absint( $end ), absint( $results_total ) ) );
			} else {
				esc_html_e( 'No products found.', 'alynt-products-grid' );
			}
			?>
		</span>
	</div>

	<div class="alynt-pg-spinner" role="status" aria-label="<?php esc_attr_e( 'Loading products', 'alynt-products-grid' ); ?>" style="display: none;">
		<div class="alynt-pg-spinner-inner" aria-hidden="true"></div>
	</div>

	<div class="alynt-pg-products-grid" role="region" aria-label="<?php esc_attr_e( 'Products', 'alynt-products-grid' ); ?>" style="--columns: <?php echo esc_attr( $atts['columns'] ); ?>;">
		<?php if ( ! empty( $products_data['products'] ) ) : ?>
			<?php foreach ( $products_data['products'] as $product ) : ?>
				<?php include ALYNT_PG_PLUGIN_DIR . 'public/partials/product-card.php'; ?>
			<?php endforeach; ?>
		<?php else : ?>
			<?php include ALYNT_PG_PLUGIN_DIR . 'public/partials/empty-state.php'; ?>
		<?php endif; ?>
	</div>

	<div class="alynt-pg-pagination">
		<?php
		if ( $total_pages > 1 ) :
			if ( $current_page > 1 ) :
				?>
				<button class="alynt-pg-page-btn alynt-pg-prev" data-page="<?php echo esc_attr( $current_page - 1 ); ?>" aria-label="<?php esc_attr_e( 'Previous page', 'alynt-products-grid' ); ?>">
					<?php esc_html_e( 'Previous', 'alynt-products-grid' ); ?>
				</button>
				<?php
			endif;

			$start_page = max( 1, $current_page - 2 );
			$end_page   = min( $total_pages, $current_page + 2 );

			if ( $start_page > 1 ) :
				?>
				<button class="alynt-pg-page-btn" data-page="1" aria-label="<?php esc_attr_e( 'Page 1', 'alynt-products-grid' ); ?>">1</button>
				<?php if ( $start_page > 2 ) : ?>
					<span class="alynt-pg-ellipsis">...</span>
					<?php
				endif;
			endif;

			for ( $i = $start_page; $i <= $end_page; $i++ ) :
				?>
				<button class="alynt-pg-page-btn <?php echo esc_attr( $i === $current_page ? 'active' : '' ); ?>"
					data-page="<?php echo esc_attr( $i ); ?>"
					<?php /* translators: %s is a page number. */ ?>
					aria-label="<?php echo esc_attr( sprintf( __( 'Page %s', 'alynt-products-grid' ), $i ) ); ?>"
					<?php echo $i === $current_page ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html( $i ); ?>
			</button>
				<?php
			endfor;

			if ( $end_page < $total_pages ) :
				if ( $end_page < $total_pages - 1 ) :
					?>
					<span class="alynt-pg-ellipsis">...</span>
				<?php endif; ?>
				<?php /* translators: %s is a page number. */ ?>
				<button class="alynt-pg-page-btn" data-page="<?php echo esc_attr( $total_pages ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Page %s', 'alynt-products-grid' ), $total_pages ) ); ?>">
				<?php echo esc_html( $total_pages ); ?>
			</button>
				<?php
			endif;

			if ( $current_page < $total_pages ) :
				?>
				<button class="alynt-pg-page-btn alynt-pg-next" data-page="<?php echo esc_attr( $current_page + 1 ); ?>" aria-label="<?php esc_attr_e( 'Next page', 'alynt-products-grid' ); ?>">
					<?php esc_html_e( 'Next', 'alynt-products-grid' ); ?>
				</button>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<input type="hidden" class="alynt-pg-all-categories"
			value="<?php echo esc_attr( wp_json_encode( $category_ids ) ); ?>">
	<input type="hidden" class="alynt-pg-category-map"
			value="<?php echo esc_attr( wp_json_encode( $category_map ) ); ?>">
	<input type="hidden" class="alynt-pg-grid-context"
			value="<?php echo esc_attr( wp_json_encode( $grid_context ) ); ?>">
	<input type="hidden" class="alynt-pg-grid-signature"
			value="<?php echo esc_attr( $grid_signature ); ?>">
</div>
