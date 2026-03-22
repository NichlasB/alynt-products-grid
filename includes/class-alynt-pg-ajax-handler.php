<?php
/**
 * AJAX handler.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles AJAX endpoints for the products grid.
 *
 * @since 1.0.0
 */
class ALYNT_PG_Ajax_Handler {
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
	 * Returns filtered product markup and pagination data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_filter_products() {
		$this->validate_ajax_request();
		$this->validate_ajax_permissions();

		try {
			$this->ensure_woocommerce_ajax_context();
			$this->ensure_customer_groups_context();

			$grid_context = $this->get_request_grid_context();
			$categories   = $this->products_query_service->normalize_category_ids( $this->get_request_categories() );
			$categories   = $this->filter_request_categories( $categories, $this->get_grid_visible_categories( $grid_context ) );
			$search       = $this->get_request_search();
			$page         = $this->get_request_page();
			$per_page     = $this->get_grid_per_page( $grid_context );

			$products_data = $this->products_query_service->get_products_data(
				array(
					'categories'            => $categories,
					'restricted_categories' => $this->get_grid_restricted_categories( $grid_context ),
					'search'                => $search,
					'page'                  => $page,
					'per_page'              => $per_page,
				)
			);

			if ( is_wp_error( $products_data ) ) {
				$this->send_ajax_error(
					$products_data->get_error_code(),
					$products_data->get_error_message(),
					500
				);
			}

			wp_send_json_success(
				array(
					'products_html' => $this->render_product_cards_html( $products_data['products'] ),
					'total'         => $products_data['total'],
					'pages'         => $products_data['pages'],
					'current_page'  => $products_data['current_page'],
				)
			);
		} catch ( Throwable $throwable ) {
			$this->log_error( sprintf( 'Product filter AJAX request failed: %s', $throwable->getMessage() ) );
			$this->send_ajax_error(
				'alynt_pg_filter_failed',
				__( 'We could not load the products right now. Please try again.', 'alynt-products-grid' ),
				500
			);
		}
	}

	/**
	 * Returns category counts for the current filter state.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_get_category_counts() {
		$this->validate_ajax_request();
		$this->validate_ajax_permissions();

		try {
			$grid_context   = $this->get_request_grid_context();
			$categories     = $this->products_query_service->normalize_category_ids( $this->get_request_categories() );
			$visible_categories = $this->get_grid_visible_categories( $grid_context );
			$categories     = $this->filter_request_categories( $categories, $visible_categories );
			$search         = $this->get_request_search();
			$all_categories = ! empty( $visible_categories ) ? $visible_categories : $this->get_request_all_categories();
			$counts         = $this->products_query_service->get_category_counts( $categories, $search, $all_categories );

			if ( is_wp_error( $counts ) ) {
				$this->send_ajax_error(
					$counts->get_error_code(),
					$counts->get_error_message(),
					500
				);
			}

			wp_send_json_success( $counts );
		} catch ( Throwable $throwable ) {
			$this->log_error( sprintf( 'Category counts AJAX request failed: %s', $throwable->getMessage() ) );
			$this->send_ajax_error(
				'alynt_pg_category_counts_failed',
				__( 'We could not update the filters right now. Please try again.', 'alynt-products-grid' ),
				500
			);
		}
	}

	/**
	 * Renders product card HTML for AJAX responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $products Product data array.
	 * @return string
	 * @throws Throwable When template rendering fails.
	 */
	private function render_product_cards_html( $products ) {
		if ( empty( $products ) ) {
			return $this->render_empty_state_html(
				__( 'No products found.', 'alynt-products-grid' ),
				__( 'Try a different search term or reset the filters to see more products.', 'alynt-products-grid' )
			);
		}

		ob_start();

		try {
			foreach ( $products as $product ) {
				include ALYNT_PG_PLUGIN_DIR . 'public/partials/product-card.php';
			}

			return ob_get_clean();
		} catch ( Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
	}

	/**
	 * Retrieves sanitized category input from the current AJAX request.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string>
	 */
	private function get_request_categories() {
		$raw_categories = filter_input( INPUT_POST, 'categories', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		return $this->sanitize_request_list( $raw_categories, 100 );
	}

	/**
	 * Retrieves the sanitized search term from the current AJAX request.
	 *
	 * @since 1.0.2
	 *
	 * @return string
	 */
	private function get_request_search() {
		$search = filter_input( INPUT_POST, 'search', FILTER_DEFAULT );

		if ( ! is_string( $search ) ) {
			return '';
		}

		return alynt_pg_normalize_search_term( wp_unslash( $search ) );
	}

	/**
	 * Retrieves the sanitized page number from the current AJAX request.
	 *
	 * @since 1.0.2
	 *
	 * @return int
	 */
	private function get_request_page() {
		$page = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );

		if ( ! is_int( $page ) ) {
			return 1;
		}

		return max( 1, $page );
	}

	/**
	 * Retrieves the sanitized per-page value from the current AJAX request.
	 *
	 * @since 1.0.2
	 *
	 * @return int
	 */
	private function get_request_per_page() {
		$per_page = filter_input( INPUT_POST, 'per_page', FILTER_VALIDATE_INT );

		if ( ! is_int( $per_page ) ) {
			return 12;
		}

		return min( 100, max( 1, $per_page ) );
	}

	/**
	 * Retrieves the sanitized category-count context from the current AJAX request.
	 *
	 * @since 1.0.2
	 *
	 * @return array<int>
	 */
	private function get_request_all_categories() {
		$all_categories = filter_input( INPUT_POST, 'all_categories', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		return $this->sanitize_request_int_list( $all_categories, 500 );
	}

	/**
	 * Retrieves the sanitized grid context from the current AJAX request.
	 *
	 * @since 1.0.2
	 *
	 * @return array
	 */
	private function get_request_grid_context() {
		$raw_grid_context = filter_input( INPUT_POST, 'grid_context', FILTER_UNSAFE_RAW );

		if ( ! is_string( $raw_grid_context ) || '' === $raw_grid_context ) {
			return array();
		}

		$grid_signature = $this->get_request_grid_signature();

		if ( '' === $grid_signature ) {
			$this->send_ajax_error(
				'alynt_pg_invalid_context',
				__( 'We could not verify this product grid request. Refresh the page and try again.', 'alynt-products-grid' ),
				400
			);
		}

		$decoded_grid_context = json_decode( wp_unslash( $raw_grid_context ), true );

		if ( ! is_array( $decoded_grid_context ) ) {
			$this->send_ajax_error(
				'alynt_pg_invalid_context',
				__( 'We could not verify this product grid request. Refresh the page and try again.', 'alynt-products-grid' ),
				400
			);
		}

		$grid_context = array(
			'per_page'              => min( 100, max( 1, intval( $decoded_grid_context['per_page'] ?? 12 ) ) ),
			'visible_categories'    => $this->sanitize_request_int_list( $decoded_grid_context['visible_categories'] ?? array(), 500 ),
			'restricted_categories' => $this->sanitize_request_int_list( $decoded_grid_context['restricted_categories'] ?? array(), 500 ),
		);

		if ( ! hash_equals( $this->sign_grid_context( $grid_context ), $grid_signature ) ) {
			$this->send_ajax_error(
				'alynt_pg_invalid_context',
				__( 'We could not verify this product grid request. Refresh the page and try again.', 'alynt-products-grid' ),
				400
			);
		}

		return $grid_context;
	}

	/**
	 * Retrieves the sanitized grid signature from the current AJAX request.
	 *
	 * @since 1.0.2
	 *
	 * @return string
	 */
	private function get_request_grid_signature() {
		$grid_signature = filter_input( INPUT_POST, 'grid_signature', FILTER_DEFAULT );

		if ( ! is_string( $grid_signature ) ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $grid_signature ) );
	}

	/**
	 * Retrieves the visible categories from the grid context.
	 *
	 * @since 1.0.2
	 *
	 * @param array $grid_context Grid context.
	 * @return array<int>
	 */
	private function get_grid_visible_categories( $grid_context ) {
		if ( ! is_array( $grid_context ) || ! isset( $grid_context['visible_categories'] ) ) {
			return array();
		}

		return $this->sanitize_request_int_list( $grid_context['visible_categories'], 500 );
	}

	/**
	 * Retrieves the restricted categories from the grid context.
	 *
	 * @since 1.0.2
	 *
	 * @param array $grid_context Grid context.
	 * @return array<int>
	 */
	private function get_grid_restricted_categories( $grid_context ) {
		if ( ! is_array( $grid_context ) || ! isset( $grid_context['restricted_categories'] ) ) {
			return array();
		}

		return $this->sanitize_request_int_list( $grid_context['restricted_categories'], 500 );
	}

	/**
	 * Retrieves the per-page value from the grid context.
	 *
	 * @since 1.0.2
	 *
	 * @param array $grid_context Grid context.
	 * @return int
	 */
	private function get_grid_per_page( $grid_context ) {
		if ( is_array( $grid_context ) && isset( $grid_context['per_page'] ) ) {
			return min( 100, max( 1, intval( $grid_context['per_page'] ) ) );
		}

		return $this->get_request_per_page();
	}

	/**
	 * Filters the request categories by the allowed categories.
	 *
	 * @since 1.0.2
	 *
	 * @param array<int> $categories Request categories.
	 * @param array<int> $allowed_categories Allowed categories.
	 * @return array<int>
	 */
	private function filter_request_categories( $categories, $allowed_categories ) {
		$categories         = $this->sanitize_request_int_list( $categories, 500 );
		$allowed_categories = $this->sanitize_request_int_list( $allowed_categories, 500 );

		if ( empty( $allowed_categories ) ) {
			return $categories;
		}

		return array_values( array_intersect( $categories, $allowed_categories ) );
	}

	/**
	 * Sanitizes a list of values.
	 *
	 * @since 1.0.2
	 *
	 * @param array $values List of values.
	 * @param int $limit Limit of values.
	 * @return array
	 */
	private function sanitize_request_list( $values, $limit ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		return array_map(
			'sanitize_text_field',
			array_slice( array_map( 'wp_unslash', $values ), 0, max( 1, intval( $limit ) ) )
		);
	}

	/**
	 * Sanitizes a list of integers.
	 *
	 * @since 1.0.2
	 *
	 * @param array $values List of integers.
	 * @param int $limit Limit of values.
	 * @return array<int>
	 */
	private function sanitize_request_int_list( $values, $limit ) {
		if ( ! is_array( $values ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( 'intval', array_slice( $values, 0, max( 1, intval( $limit ) ) ) )
				)
			)
		);
	}

	/**
	 * Signs the grid context.
	 *
	 * @since 1.0.2
	 *
	 * @param array $grid_context Grid context.
	 * @return string
	 */
	private function sign_grid_context( $grid_context ) {
		return alynt_pg_sign_grid_context( $grid_context );
	}

	/**
	 * Ensures WooCommerce frontend context is initialized for AJAX pricing logic.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function ensure_woocommerce_ajax_context() {
		$current_user_id = get_current_user_id();

		if ( class_exists( 'WC' ) ) {
			WC()->frontend_includes();
			if ( is_null( WC()->cart ) ) {
				WC()->cart = new WC_Cart();
			}
			if ( is_null( WC()->customer ) ) {
				WC()->customer = new WC_Customer( $current_user_id, true );
			}
			if ( is_null( WC()->session ) ) {
				WC()->initialize_session();
			}
		}
	}

	/**
	 * Ensures customer-group pricing hooks are loaded during AJAX requests.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function ensure_customer_groups_context() {
		if ( class_exists( 'WCCG_Public' ) ) {
			global $wp_filter;
			$has_filter = isset( $wp_filter['woocommerce_get_price_html'] ) && ! empty( $wp_filter['woocommerce_get_price_html']->callbacks );

			if ( ! $has_filter ) {
				WCCG_Public::instance();
			}
		}
	}

	/**
	 * Validates the incoming AJAX nonce without triggering a bare -1 response.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	private function validate_ajax_request() {
		if ( false === check_ajax_referer( 'alynt_pg_nonce', 'nonce', false ) ) {
			$this->send_ajax_error(
				'alynt_pg_session_expired',
				__( 'Your session expired. Refresh the page and try again.', 'alynt-products-grid' ),
				403
			);
		}
	}

	/**
	 * Validates read access for public-facing AJAX requests.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	private function validate_ajax_permissions() {
		if ( is_user_logged_in() && ! current_user_can( 'read' ) ) {
			$this->send_ajax_error(
				'alynt_pg_forbidden',
				__( 'Permission denied.', 'alynt-products-grid' ),
				403
			);
		}
	}

	/**
	 * Sends a structured AJAX error response.
	 *
	 * @since 1.0.2
	 *
	 * @param string $code    Machine-readable error code.
	 * @param string $message User-facing error message.
	 * @param int    $status  HTTP status code.
	 * @return void
	 */
	private function send_ajax_error( $code, $message, $status = 400 ) {
		wp_send_json_error(
			array(
				'code'    => $code,
				'message' => $message,
			),
			$status
		);
	}

	/**
	 * Renders the shared empty-state partial for AJAX responses.
	 *
	 * @since 1.0.2
	 *
	 * @param string $empty_state_title   Empty-state title.
	 * @param string $empty_state_message Empty-state guidance.
	 * @return string
	 */
	private function render_empty_state_html( $empty_state_title, $empty_state_message ) {
		$empty_state_title   = (string) $empty_state_title;
		$empty_state_message = (string) $empty_state_message;

		ob_start();
		include ALYNT_PG_PLUGIN_DIR . 'public/partials/empty-state.php';
		return ob_get_clean();
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
