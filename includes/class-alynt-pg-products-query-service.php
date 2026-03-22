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
 *
 * @since 1.0.0
 */
class ALYNT_PG_Products_Query_Service {
	/**
	 * Current title search term for the temporary posts_where filter.
	 *
	 * @var string
	 */
	private $title_search_term = '';

	/**
	 * Retrieves products and normalizes them for template consumption.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Query arguments.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_products_data( $args ) {
		$defaults = array(
			'categories'            => array(),
			'restricted_categories' => array(),
			'per_page'              => 12,
			'page'                  => 1,
			'search'                => '',
		);

		$args                          = wp_parse_args( $args, $defaults );
		$args['page']                  = max( 1, absint( $args['page'] ) );
		$args['per_page']              = min( 100, max( 1, absint( $args['per_page'] ) ) );
		$args['categories']            = array_values( array_unique( array_filter( array_map( 'intval', $args['categories'] ) ) ) );
		$args['restricted_categories'] = array_values( array_unique( array_filter( array_map( 'intval', $args['restricted_categories'] ) ) ) );
		$args['search']                = $this->normalize_search_term( $args['search'] );

		if ( ! empty( $args['restricted_categories'] ) && ! empty( $args['categories'] ) ) {
			$args['categories'] = array_values( array_intersect( $args['categories'], $args['restricted_categories'] ) );
		}

		$effective_categories = ! empty( $args['categories'] ) ? $args['categories'] : $args['restricted_categories'];
		$category_relation    = ! empty( $args['categories'] ) ? 'AND' : 'OR';

		$query_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => $args['per_page'],
			'paged'                  => $args['page'],
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'cache_results'          => true,
			'lazy_load_term_meta'    => false,
		);

		$title_search_filter = null;

		try {
			$tax_query = $this->get_visibility_tax_query();

			if ( ! empty( $effective_categories ) ) {
				$tax_query[] = $this->build_category_tax_query( $effective_categories, $category_relation );
			}

			if ( ! empty( $tax_query ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Product visibility and category filtering require taxonomy constraints.
				$query_args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query );
			}

			if ( ! empty( $args['search'] ) ) {
				$this->title_search_term = $args['search'];
				$title_search_filter     = array( $this, 'filter_posts_where_by_title' );
				add_filter( 'posts_where', $title_search_filter, 10, 2 );
				$query_args['alynt_pg_search_title_only'] = true;
			}

			$query = $this->run_products_query( $query_args );

			if ( $query->max_num_pages > 0 && $args['page'] > $query->max_num_pages ) {
				$query_args['paged'] = $query->max_num_pages;
				$args['page']        = $query->max_num_pages;
				$query               = $this->run_products_query( $query_args );
			}

			$products = array();
			if ( ! empty( $query->posts ) ) {
				foreach ( $query->posts as $post ) {
					$product = wc_get_product( $post );

					if ( $product ) {
						$products[] = $this->format_product_for_display( $product );
					}
				}
			}

			$current_page = $args['page'];

			if ( $query->max_num_pages > 0 ) {
				$current_page = min( $current_page, $query->max_num_pages );
			} else {
				$current_page = 1;
			}

			return array(
				'products'     => $products,
				'total'        => $query->found_posts,
				'pages'        => $query->max_num_pages,
				'current_page' => $current_page,
			);
		} catch ( Throwable $throwable ) {
			$this->log_error( sprintf( 'Products query failed: %s', $throwable->getMessage() ) );

			return new WP_Error(
				'alynt_pg_products_query_failed',
				__( 'We could not load the products right now. Please try again.', 'alynt-products-grid' )
			);
		} finally {
			if ( null !== $title_search_filter ) {
				remove_filter( 'posts_where', $title_search_filter, 10 );
				$this->title_search_term = '';
			}
		}
	}

	/**
	 * Returns an empty products response shape for safe rendering fallbacks.
	 *
	 * @since 1.0.2
	 *
	 * @return array<string, mixed>
	 */
	public function get_empty_products_data() {
		return array(
			'products'     => array(),
			'total'        => 0,
			'pages'        => 0,
			'current_page' => 1,
		);
	}

	/**
	 * Converts mixed category slugs and IDs into normalized term IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $categories Category identifiers.
	 * @return array<int>
	 */
	public function normalize_category_ids( $categories ) {
		if ( empty( $categories ) ) {
			return array();
		}

		$category_ids   = array();
		$category_slugs = array();

		foreach ( $categories as $cat ) {
			$cat = is_string( $cat ) ? trim( $cat ) : $cat;

			if ( is_numeric( $cat ) ) {
				$category_ids[] = intval( $cat );
				continue;
			}

			if ( is_string( $cat ) && '' !== $cat ) {
				$category_slugs[] = sanitize_title( $cat );
			}
		}

		if ( ! empty( $category_slugs ) ) {
			$category_ids = array_merge( $category_ids, $this->get_category_ids_by_slugs( $category_slugs ) );
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $category_ids ) ) ) );
	}

	/**
	 * Calculates counts for each category in the current filter context.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $categories     Selected category IDs.
	 * @param string $search         Search term.
	 * @param array  $all_categories All category IDs.
	 * @return array<int, int>|WP_Error
	 */
	public function get_category_counts( $categories, $search, $all_categories ) {
		$all_category_ids = array_values( array_unique( array_filter( array_map( 'intval', $all_categories ) ) ) );
		$category_counts  = array_fill_keys( $all_category_ids, 0 );

		if ( empty( $category_counts ) ) {
			return array();
		}

		$selected_category_ids = array_values( array_unique( array_filter( array_map( 'intval', $categories ) ) ) );
		$normalized_search     = $this->normalize_search_term( $search );
		$cache_key             = $this->build_cache_key(
			'category_counts',
			array(
				'categories'     => $selected_category_ids,
				'search'         => $normalized_search,
				'all_categories' => $all_category_ids,
			)
		);
		$cached_counts         = get_transient( $cache_key );

		if ( is_array( $cached_counts ) ) {
			return array_replace( $category_counts, array_map( 'intval', $cached_counts ) );
		}

		try {
			$results = $this->get_category_count_results( $selected_category_ids, $normalized_search, $all_category_ids );

			foreach ( $results as $result ) {
				$term_id = isset( $result['term_id'] ) ? (int) $result['term_id'] : 0;

				if ( isset( $category_counts[ $term_id ] ) ) {
					$category_counts[ $term_id ] = isset( $result['product_count'] ) ? (int) $result['product_count'] : 0;
				}
			}

			set_transient( $cache_key, $category_counts, 10 * MINUTE_IN_SECONDS );

			return $category_counts;
		} catch ( Throwable $throwable ) {
			$this->log_error( sprintf( 'Category counts query failed: %s', $throwable->getMessage() ) );

			return new WP_Error(
				'alynt_pg_category_counts_failed',
				__( 'We could not update the filters right now. Please try again.', 'alynt-products-grid' )
			);
		}
	}

	/**
	 * Builds the category tax query for the current filter set.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $categories Category IDs.
	 * @param string $relation   Tax query relation.
	 * @return array
	 */
	private function build_category_tax_query( $categories, $relation = 'AND' ) {
		$categories = array_values( array_unique( array_filter( array_map( 'intval', $categories ) ) ) );

		if ( empty( $categories ) ) {
			return array();
		}

		if ( count( $categories ) === 1 || 'AND' !== $relation ) {
			return array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $categories,
				'operator' => 'IN',
			);
		}

		$category_tax_query = array( 'relation' => $relation );
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

	private function get_visibility_tax_query() {
		$excluded_visibility_terms = $this->get_excluded_visibility_term_ids();

		if ( empty( $excluded_visibility_terms ) ) {
			return array();
		}

		return array(
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $excluded_visibility_terms,
				'operator' => 'NOT IN',
			),
		);
	}

	private function get_excluded_visibility_term_ids() {
		if ( ! function_exists( 'wc_get_product_visibility_term_ids' ) ) {
			return array();
		}

		$product_visibility_term_ids = wc_get_product_visibility_term_ids();
		$excluded_visibility_terms   = array();

		if ( isset( $product_visibility_term_ids['exclude-from-catalog'] ) ) {
			$excluded_visibility_terms[] = (int) $product_visibility_term_ids['exclude-from-catalog'];
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' ) && isset( $product_visibility_term_ids['outofstock'] ) ) {
			$excluded_visibility_terms[] = (int) $product_visibility_term_ids['outofstock'];
		}

		return array_values( array_unique( array_filter( $excluded_visibility_terms ) ) );
	}

	private function normalize_search_term( $search ) {
		return alynt_pg_normalize_search_term( $search );
	}

	/**
	 * Filters queries to match product titles only for plugin-managed searches.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $where    Current WHERE clause.
	 * @param WP_Query $wp_query Query instance.
	 * @return string
	 */
	public function filter_posts_where_by_title( $where, $wp_query ) {
		global $wpdb;

		if ( $wp_query->get( 'alynt_pg_search_title_only' ) && '' !== $this->title_search_term ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $this->title_search_term ) . '%' );
		}

		return $where;
	}

	/**
	 * Formats a WooCommerce product for template rendering.
	 *
	 * @since 1.0.2
	 *
	 * @param WC_Product $product WooCommerce product instance.
	 * @return array<string, mixed>
	 */
	private function format_product_for_display( $product ) {
		$product_categories = get_the_terms( $product->get_id(), 'product_cat' );
		$supports_ajax_add_to_cart = $product->is_purchasable() && $product->is_in_stock() && $product->supports( 'ajax_add_to_cart' );

		if ( is_wp_error( $product_categories ) ) {
			$this->log_error(
				sprintf(
					'Failed to load categories for product %d: %s',
					$product->get_id(),
					$product_categories->get_error_message()
				)
			);
			$product_categories = array();
		}

		return array(
			'id'              => $product->get_id(),
			'title'           => $product->get_name(),
			'price'           => $product->get_price_html(),
			'image'           => wp_get_attachment_image_src( $product->get_image_id(), 'medium' ),
			'categories'      => $product_categories,
			'permalink'       => $product->get_permalink(),
			'in_stock'        => $product->is_in_stock(),
			'can_ajax_add_to_cart' => $supports_ajax_add_to_cart,
			'add_to_cart_url' => $product->add_to_cart_url(),
		);
	}

	/**
	 * Executes the main products query and primes caches for the result set.
	 *
	 * @param array $query_args Query arguments.
	 * @return WP_Query
	 */
	private function run_products_query( $query_args ) {
		$query = new WP_Query( $query_args );

		if ( ! empty( $query->posts ) ) {
			$this->prime_product_caches( $query->posts );
		}

		return $query;
	}

	/**
	 * Primes meta and term caches for a product result set.
	 *
	 * @param array $posts Queried product posts.
	 * @return void
	 */
	private function prime_product_caches( $posts ) {
		$post_ids = array_values( array_unique( array_filter( array_map( 'intval', wp_list_pluck( $posts, 'ID' ) ) ) ) );

		if ( empty( $post_ids ) ) {
			return;
		}

		update_postmeta_cache( $post_ids );
		update_object_term_cache( $post_ids, 'product' );

		$thumbnail_ids = array_values( array_unique( array_filter( array_map( 'get_post_thumbnail_id', $post_ids ) ) ) );

		if ( ! empty( $thumbnail_ids ) ) {
			update_postmeta_cache( $thumbnail_ids );
		}
	}

	/**
	 * Resolves product category slugs in a single cached query.
	 *
	 * @param array $category_slugs Product category slugs.
	 * @return array<int>
	 */
	private function get_category_ids_by_slugs( $category_slugs ) {
		$category_slugs = array_values( array_unique( array_filter( array_map( 'sanitize_title', $category_slugs ) ) ) );

		if ( empty( $category_slugs ) ) {
			return array();
		}

		$cache_key           = $this->build_cache_key( 'category_slug_ids', $category_slugs );
		$cached_category_ids = get_transient( $cache_key );

		if ( is_array( $cached_category_ids ) ) {
			return array_values( array_unique( array_filter( array_map( 'intval', $cached_category_ids ) ) ) );
		}

		$category_ids = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'slug'       => $category_slugs,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $category_ids ) ) {
			$this->log_error( sprintf( 'Failed to resolve category slugs: %s', $category_ids->get_error_message() ) );
			return array();
		}

		$category_ids = array_values( array_unique( array_filter( array_map( 'intval', $category_ids ) ) ) );
		set_transient( $cache_key, $category_ids, DAY_IN_SECONDS );

		return $category_ids;
	}

	/**
	 * Returns aggregated product counts per category for the current filter context.
	 *
	 * @param array  $selected_category_ids Selected category IDs.
	 * @param string $search                Search term.
	 * @param array  $all_category_ids      All visible category IDs.
	 * @return array<int, array<string, string>>
	 * @throws RuntimeException When the category count query fails.
	 */
	private function get_category_count_results( $selected_category_ids, $search, $all_category_ids ) {
		global $wpdb;

		$query_params           = array();
		$matching_products_join = '';
		$target_placeholders    = implode( ', ', array_fill( 0, count( $all_category_ids ), '%d' ) );
		$excluded_visibility_terms = $this->get_excluded_visibility_term_ids();

		if ( ! empty( $selected_category_ids ) ) {
			$selected_placeholders  = implode( ', ', array_fill( 0, count( $selected_category_ids ), '%d' ) );
			$matching_products_join = "
				INNER JOIN (
					SELECT tr_selected.object_id
					FROM {$wpdb->term_relationships} tr_selected
					INNER JOIN {$wpdb->term_taxonomy} tt_selected
						ON tr_selected.term_taxonomy_id = tt_selected.term_taxonomy_id
					WHERE tt_selected.taxonomy = %s
						AND tt_selected.term_id IN ({$selected_placeholders})
					GROUP BY tr_selected.object_id
					HAVING COUNT(DISTINCT tt_selected.term_id) = %d
				) matching_products
					ON matching_products.object_id = p.ID
			";

			$query_params[] = 'product_cat';
			foreach ( $selected_category_ids as $selected_category_id ) {
				$query_params[] = $selected_category_id;
			}
			$query_params[] = count( $selected_category_ids );
		}

		$sql = "
			SELECT tt.term_id, COUNT(DISTINCT p.ID) AS product_count
			FROM {$wpdb->posts} p
			{$matching_products_join}
			INNER JOIN {$wpdb->term_relationships} tr
				ON p.ID = tr.object_id
			INNER JOIN {$wpdb->term_taxonomy} tt
				ON tr.term_taxonomy_id = tt.term_taxonomy_id
			WHERE p.post_type = %s
				AND p.post_status = %s
		";

		$query_params[] = 'product';
		$query_params[] = 'publish';

		if ( '' !== $search ) {
			$sql           .= ' AND p.post_title LIKE %s';
			$query_params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( ! empty( $excluded_visibility_terms ) ) {
			$visibility_placeholders = implode( ', ', array_fill( 0, count( $excluded_visibility_terms ), '%d' ) );
			$sql                    .= "
				AND p.ID NOT IN (
					SELECT tr_visibility.object_id
					FROM {$wpdb->term_relationships} tr_visibility
					WHERE tr_visibility.term_taxonomy_id IN ({$visibility_placeholders})
				)
			";

			foreach ( $excluded_visibility_terms as $excluded_visibility_term ) {
				$query_params[] = $excluded_visibility_term;
			}
		}

		$sql .= "
				AND tt.taxonomy = %s
				AND tt.term_id IN ({$target_placeholders})
			GROUP BY tt.term_id
		";

		$query_params[] = 'product_cat';
		foreach ( $all_category_ids as $all_category_id ) {
			$query_params[] = $all_category_id;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are assembled above and bound in the prepare() call below.
		$prepared_sql = $wpdb->prepare( $sql, $query_params );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregated category counts are prepared above and cached in a versioned transient before this query can run again.
		$results = $wpdb->get_results( $prepared_sql, ARRAY_A );

		if ( null === $results ) {
			$last_error = '' !== $wpdb->last_error ? $wpdb->last_error : __( 'Could not calculate category counts.', 'alynt-products-grid' );
			throw new RuntimeException( esc_html( $last_error ) );
		}

		return $results;
	}

	/**
	 * Builds a transient cache key tied to the current cache version.
	 *
	 * @param string $prefix Cache key prefix.
	 * @param mixed  $data   Data used to build the hash.
	 * @return string
	 */
	private function build_cache_key( $prefix, $data ) {
		$cache_version = (string) get_option( 'alynt_pg_cache_version', '1' );
		$cache_hash    = md5(
			(string) wp_json_encode(
				array(
					'version' => $cache_version,
					'data'    => $data,
				)
			)
		);

		return sprintf( 'alynt_pg_%s_%s', $prefix, $cache_hash );
	}

	/**
	 * Logs plugin-specific server-side errors.
	 *
	 * @since 1.0.2
	 *
	 * @param string $message Message to log.
	 * @return void
	 */
	private function log_error( $message ) {
		error_log( sprintf( '[Alynt Products Grid] %s', $message ) );
	}
}
