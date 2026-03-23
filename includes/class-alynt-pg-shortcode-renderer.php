<?php
/**
 * Shortcode renderer.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the products grid shortcode.
 *
 * @since 1.0.0
 */
class ALYNT_PG_Shortcode_Renderer {
	/**
	 * Cached product categories for the current request.
	 *
	 * @var array<int, WP_Term>|null
	 */
	private static $cached_categories = null;

	/**
	 * Products query service.
	 *
	 * @var ALYNT_PG_Products_Query_Service
	 */
	private $products_query_service;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param ALYNT_PG_Products_Query_Service $products_query_service Products query service instance.
	 */
	public function __construct( $products_query_service ) {
		$this->products_query_service = $products_query_service;
	}

	/**
	 * Renders the shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'columns'      => 4,
				'categories'   => '',
				'special'      => '',
				'per_page'     => 12,
				'breakpoint_5' => 1200,
				'breakpoint_4' => 992,
				'breakpoint_3' => 768,
				'breakpoint_2' => 576,
				'filter_mode'  => 'default',
			),
			$atts,
			'alynt_products_grid'
		);

		$atts['columns']     = min( 5, max( 1, intval( $atts['columns'] ) ) );
		$atts['per_page']    = min( 100, max( 1, intval( $atts['per_page'] ) ) );
		$atts['filter_mode'] = $this->normalize_filter_mode( $atts['filter_mode'] );
		$this->enqueue_frontend_assets();

		ob_start();
		$this->render_products_grid( $atts );
		return ob_get_clean();
	}

	/**
	 * Builds the data needed by the grid template.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts Parsed shortcode attributes.
	 * @return void
	 */
	private function render_products_grid( $atts ) {
		$grid_notice_message = '';
		$grid_notice_type    = 'info';
		$empty_state_title   = __( 'No products found.', 'alynt-products-grid' );
		$empty_state_message = $this->get_empty_state_message_for_filter_mode( $atts['filter_mode'] );

		$categories = $this->get_cached_categories();

		if ( is_wp_error( $categories ) ) {
			$this->log_error( sprintf( 'Failed to load product categories: %s', $categories->get_error_message() ) );

			$categories = array();

			if ( 'default' === $atts['filter_mode'] ) {
				$grid_notice_message = __( 'Category filters are temporarily unavailable. You can still browse the products below.', 'alynt-products-grid' );
			}
		}

		$restricted_categories = $this->parse_category_list( $atts['categories'] );
		$special_categories    = $this->parse_category_list( $atts['special'] );

		if ( ! empty( $restricted_categories ) ) {
			$categories = array_values(
				array_filter(
					$categories,
					function ( $cat ) use ( $restricted_categories ) {
						return in_array( $cat->term_id, $restricted_categories, true );
					}
				)
			);
		}

		$grid_context   = $this->build_grid_context( $atts, $categories, $restricted_categories );
		$grid_signature = $this->sign_grid_context( $grid_context );

		$products_data = $this->products_query_service->get_products_data(
			array(
				'categories'            => array(),
				'restricted_categories' => $restricted_categories,
				'per_page'              => $atts['per_page'],
				'page'                  => 1,
				'search'                => '',
			)
		);

		if ( is_wp_error( $products_data ) ) {
			$this->log_error( sprintf( 'Failed to load initial products grid: %s', $products_data->get_error_message() ) );

			$grid_notice_type    = 'error';
			$grid_notice_message = $products_data->get_error_message();
			$products_data       = $this->products_query_service->get_empty_products_data();
			$empty_state_title   = __( 'Products are temporarily unavailable.', 'alynt-products-grid' );
			$empty_state_message = __( 'Please refresh the page and try again. If the problem continues, contact support.', 'alynt-products-grid' );
		}

		include ALYNT_PG_PLUGIN_DIR . 'public/partials/products-grid.php';
	}

	/**
	 * Parses a comma-separated category list into normalized term IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param string $category_list Comma-separated category list.
	 * @return array<int>
	 */
	private function parse_category_list( $category_list ) {
		if ( empty( $category_list ) ) {
			return array();
		}

		return $this->products_query_service->normalize_category_ids( explode( ',', $category_list ) );
	}

	/**
	 * Normalizes the shortcode filter mode.
	 *
	 * @param string $filter_mode Requested filter mode.
	 * @return string
	 */
	private function normalize_filter_mode( $filter_mode ) {
		$filter_mode = is_string( $filter_mode ) ? sanitize_key( $filter_mode ) : 'default';

		if ( ! in_array( $filter_mode, array( 'default', 'none', 'search' ), true ) ) {
			return 'default';
		}

		return $filter_mode;
	}

	/**
	 * Returns empty-state guidance for the active filter mode.
	 *
	 * @param string $filter_mode Active filter mode.
	 * @return string
	 */
	private function get_empty_state_message_for_filter_mode( $filter_mode ) {
		switch ( $filter_mode ) {
			case 'search':
				return __( 'Try a different search term or clear the search to see more products.', 'alynt-products-grid' );
			case 'none':
				return __( 'Try browsing another page to see more products.', 'alynt-products-grid' );
			case 'default':
			default:
				return __( 'Try a different search term or reset the filters to see more products.', 'alynt-products-grid' );
		}
	}

	private function enqueue_frontend_assets() {
		alynt_pg_enqueue_frontend_assets();
	}

	private function build_grid_context( $atts, $categories, $restricted_categories ) {
		$visible_categories = array_values( array_unique( array_filter( array_map( 'intval', wp_list_pluck( $categories, 'term_id' ) ) ) ) );
		sort( $visible_categories );

		$restricted_categories = array_values( array_unique( array_filter( array_map( 'intval', $restricted_categories ) ) ) );
		sort( $restricted_categories );

		return array(
			'per_page'              => min( 100, max( 1, intval( $atts['per_page'] ) ) ),
			'visible_categories'    => $visible_categories,
			'restricted_categories' => $restricted_categories,
			'filter_mode'           => $this->normalize_filter_mode( $atts['filter_mode'] ?? 'default' ),
		);
	}

	private function sign_grid_context( $grid_context ) {
		return alynt_pg_sign_grid_context( $grid_context );
	}

	/**
	 * Returns cached product categories for filter rendering.
	 *
	 * @since 1.0.2
	 *
	 * @return array<int, WP_Term>|WP_Error
	 */
	private function get_cached_categories() {
		if ( null !== self::$cached_categories ) {
			return self::$cached_categories;
		}

		$cached_categories = get_transient( ALYNT_PG_PRODUCT_CATEGORIES_TRANSIENT );

		if ( is_array( $cached_categories ) ) {
			self::$cached_categories = $cached_categories;
			return self::$cached_categories;
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $categories ) ) {
			return $categories;
		}

		self::$cached_categories = $categories;
		set_transient( ALYNT_PG_PRODUCT_CATEGORIES_TRANSIENT, $categories, HOUR_IN_SECONDS );

		return self::$cached_categories;
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
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional server-side logging for recoverable runtime failures.
		error_log( sprintf( '[Alynt Products Grid] %s', $message ) );
	}
}
