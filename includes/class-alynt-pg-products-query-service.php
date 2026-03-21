<?php
/**
 * Products query service.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce product query preparation and formatting.
 */
class ALYNT_PG_Products_Query_Service {
	/**
	 * Retrieves products and normalizes them for template consumption.
	 *
	 * @param array $args Query arguments.
	 * @return array<string, mixed>
	 */
	public function get_products_data( $args ) {
		$defaults = array(
			'categories' => array(),
			'per_page'   => 12,
			'page'       => 1,
			'search'     => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $args['per_page'],
			'paged'          => $args['page'],
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $args['categories'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Product category filtering requires taxonomy constraints.
			$query_args['tax_query'] = $this->build_category_tax_query( $args['categories'] );
		}

		$title_search_filter = null;
		if ( ! empty( $args['search'] ) ) {
			$search_term         = sanitize_text_field( $args['search'] );
			$title_search_filter = $this->get_title_search_filter( $search_term );
			add_filter( 'posts_where', $title_search_filter, 10, 2 );
			$query_args['search_title_only'] = true;
		}

		$query = new WP_Query( $query_args );

		if ( null !== $title_search_filter ) {
			remove_filter( 'posts_where', $title_search_filter, 10 );
		}

		$products = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );

				if ( $product ) {
					$products[] = array(
						'id'              => $product->get_id(),
						'title'           => $product->get_name(),
						'price'           => $product->get_price_html(),
						'image'           => wp_get_attachment_image_src( $product->get_image_id(), 'medium' ),
						'categories'      => wp_get_post_terms( $product->get_id(), 'product_cat' ),
						'permalink'       => $product->get_permalink(),
						'in_stock'        => $product->is_in_stock(),
						'add_to_cart_url' => $product->add_to_cart_url(),
					);
				}
			}
		}

		wp_reset_postdata();

		return array(
			'products'     => $products,
			'total'        => $query->found_posts,
			'pages'        => $query->max_num_pages,
			'current_page' => $args['page'],
		);
	}

	/**
	 * Converts mixed category slugs and IDs into normalized term IDs.
	 *
	 * @param array $categories Category identifiers.
	 * @return array<int>
	 */
	public function normalize_category_ids( $categories ) {
		if ( empty( $categories ) ) {
			return array();
		}

		$category_ids = array();
		foreach ( $categories as $cat ) {
			$cat = is_string( $cat ) ? trim( $cat ) : $cat;
			if ( is_numeric( $cat ) ) {
				$category_ids[] = intval( $cat );
			} else {
				$term = get_term_by( 'slug', sanitize_text_field( $cat ), 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$category_ids[] = $term->term_id;
				}
			}
		}

		return $category_ids;
	}

	/**
	 * Calculates counts for each category in the current filter context.
	 *
	 * @param array  $categories     Selected category IDs.
	 * @param string $search         Search term.
	 * @param array  $all_categories All category IDs.
	 * @return array<int, int>
	 */
	public function get_category_counts( $categories, $search, $all_categories ) {
		$category_counts = array();

		foreach ( $all_categories as $cat_id ) {
			$test_categories = $categories;

			if ( ! in_array( $cat_id, $test_categories, true ) ) {
				$test_categories[] = $cat_id;
			}

			$query_args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);

			if ( ! empty( $test_categories ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Product category counts depend on taxonomy constraints.
				$query_args['tax_query'] = $this->build_category_tax_query( $test_categories );
			}

			$title_search_filter = null;
			if ( ! empty( $search ) ) {
				$title_search_filter = $this->get_title_search_filter( $search );
				add_filter( 'posts_where', $title_search_filter, 10, 2 );
				$query_args['search_title_only'] = true;
			}

			$query                      = new WP_Query( $query_args );
			$category_counts[ $cat_id ] = $query->found_posts;

			if ( null !== $title_search_filter ) {
				remove_filter( 'posts_where', $title_search_filter, 10 );
			}
		}

		return $category_counts;
	}

	/**
	 * Builds the category tax query for the current filter set.
	 *
	 * @param array $categories Category IDs.
	 * @return array
	 */
	private function build_category_tax_query( $categories ) {
		if ( count( $categories ) === 1 ) {
			return array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $categories,
					'operator' => 'IN',
				),
			);
		}

		$category_tax_query = array( 'relation' => 'AND' );
		foreach ( $categories as $category_id ) {
			$category_tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => array( $category_id ),
				'operator' => 'IN',
			);
		}

		return $category_tax_query;
	}

	/**
	 * Builds a title-only posts_where filter callback.
	 *
	 * @param string $search_term Search term.
	 * @return Closure
	 */
	private function get_title_search_filter( $search_term ) {
		return function ( $where, $wp_query ) use ( $search_term ) {
			global $wpdb;
			if ( $wp_query->get( 'search_title_only' ) ) {
				$where .= " AND {$wpdb->posts}.post_title LIKE '%" . esc_sql( $wpdb->esc_like( $search_term ) ) . "%'";
			}
			return $where;
		};
	}
}
