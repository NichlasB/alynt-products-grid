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
		check_ajax_referer( 'alynt_pg_nonce', 'nonce' );

		$this->ensure_woocommerce_ajax_context();
		$this->ensure_customer_groups_context();

		$categories = $this->products_query_service->normalize_category_ids( $this->get_request_categories() );
		$search     = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$page       = intval( wp_unslash( $_POST['page'] ?? 1 ) );
		$per_page   = min( 100, max( 1, intval( wp_unslash( $_POST['per_page'] ?? 12 ) ) ) );

		$products_data = $this->products_query_service->get_products_data(
			array(
				'categories' => $categories,
				'search'     => $search,
				'page'       => $page,
				'per_page'   => $per_page,
			)
		);

		wp_send_json_success(
			array(
				'products_html' => $this->render_product_cards_html( $products_data['products'] ),
				'total'         => $products_data['total'],
				'pages'         => $products_data['pages'],
				'current_page'  => $products_data['current_page'],
			)
		);
	}

	/**
	 * Returns category counts for the current filter state.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_get_category_counts() {
		check_ajax_referer( 'alynt_pg_nonce', 'nonce' );

		$categories     = $this->products_query_service->normalize_category_ids( $this->get_request_categories() );
		$search         = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		$all_categories = ! empty( $_POST['all_categories'] ) ? array_map( 'intval', wp_unslash( $_POST['all_categories'] ) ) : array();

		wp_send_json_success(
			$this->products_query_service->get_category_counts( $categories, $search, $all_categories )
		);
	}

	/**
	 * Renders product card HTML for AJAX responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $products Product data array.
	 * @return string
	 */
	private function render_product_cards_html( $products ) {
		ob_start();
		foreach ( $products as $product ) {
			include ALYNT_PG_PLUGIN_DIR . 'public/partials/product-card.php';
		}
		return ob_get_clean();
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
		if ( ! is_array( $raw_categories ) ) {
			return array();
		}

		return array_map( 'sanitize_text_field', $raw_categories );
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
}
