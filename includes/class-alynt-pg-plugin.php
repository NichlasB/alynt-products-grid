<?php
/**
 * Main plugin orchestrator.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates shortcode, AJAX, and asset loading.
 *
 * @since 1.0.0
 */
class ALYNT_PG_Plugin {
	/**
	 * Products query service instance.
	 *
	 * @var ALYNT_PG_Products_Query_Service|null
	 */
	private $products_query_service = null;

	/**
	 * Shortcode renderer instance.
	 *
	 * @var ALYNT_PG_Shortcode_Renderer|null
	 */
	private $shortcode_renderer = null;

	/**
	 * AJAX handler instance.
	 *
	 * @var ALYNT_PG_Ajax_Handler|null
	 */
	private $ajax_handler = null;

	/**
	 * Cached asset metadata for the current request.
	 *
	 * @var array<string, array<string, string>>|null
	 */
	private static $asset_metadata = null;

	/**
	 * Sets up plugin services and hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_alynt_pg_filter_products', array( $this, 'ajax_filter_products' ) );
		add_action( 'wp_ajax_nopriv_alynt_pg_filter_products', array( $this, 'ajax_filter_products' ) );
		add_action( 'wp_ajax_alynt_pg_get_category_counts', array( $this, 'ajax_get_category_counts' ) );
		add_action( 'wp_ajax_nopriv_alynt_pg_get_category_counts', array( $this, 'ajax_get_category_counts' ) );
		add_action( 'save_post_product', array( $this, 'clear_product_categories_cache' ) );
		add_action( 'created_product_cat', array( $this, 'clear_product_categories_cache' ) );
		add_action( 'edited_product_cat', array( $this, 'clear_product_categories_cache' ) );
		add_action( 'delete_product_cat', array( $this, 'clear_product_categories_cache' ) );
	}

	/**
	 * Registers runtime hooks that must run on init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_shortcode( 'alynt_products_grid', array( $this->get_shortcode_renderer(), 'render_shortcode' ) );
	}

	/**
	 * Loads plugin translation files.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'alynt-products-grid', false, dirname( ALYNT_PG_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * Returns the shared products query service, instantiating it lazily.
	 *
	 * @since 1.0.2
	 *
	 * @return ALYNT_PG_Products_Query_Service
	 */
	private function get_products_query_service() {
		if ( null === $this->products_query_service ) {
			$this->products_query_service = new ALYNT_PG_Products_Query_Service();
		}

		return $this->products_query_service;
	}

	/**
	 * Returns the shortcode renderer, instantiating it lazily.
	 *
	 * @since 1.0.2
	 *
	 * @return ALYNT_PG_Shortcode_Renderer
	 */
	private function get_shortcode_renderer() {
		if ( null === $this->shortcode_renderer ) {
			$this->shortcode_renderer = new ALYNT_PG_Shortcode_Renderer( $this->get_products_query_service() );
		}

		return $this->shortcode_renderer;
	}

	/**
	 * Returns the AJAX handler, instantiating it lazily.
	 *
	 * @since 1.0.2
	 *
	 * @return ALYNT_PG_Ajax_Handler
	 */
	private function get_ajax_handler() {
		if ( null === $this->ajax_handler ) {
			$this->ajax_handler = new ALYNT_PG_Ajax_Handler( $this->get_products_query_service() );
		}

		return $this->ajax_handler;
	}

	/**
	 * Handles the filtered products AJAX action.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function ajax_filter_products() {
		$this->get_ajax_handler()->ajax_filter_products();
	}

	/**
	 * Handles the category counts AJAX action.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function ajax_get_category_counts() {
		$this->get_ajax_handler()->ajax_get_category_counts();
	}

	/**
	 * Clears cached category data when products or terms change.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function clear_product_categories_cache() {
		delete_transient( ALYNT_PG_PRODUCT_CATEGORIES_TRANSIENT );
		update_option( 'alynt_pg_cache_version', (string) microtime( true ), false );
	}

	/**
	 * Enqueues frontend assets when the shortcode is present.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$asset_metadata = $this->get_asset_metadata();

		wp_register_style(
			'alynt-pg-style',
			$asset_metadata['style']['url'],
			array(),
			$asset_metadata['style']['version']
		);

		wp_register_script(
			'alynt-pg-script',
			$asset_metadata['script']['url'],
			array( 'jquery', 'wc-add-to-cart' ),
			$asset_metadata['script']['version'],
			true
		);

		wp_localize_script(
			'alynt-pg-script',
			'alynt_pg_ajax',
			array(
				'ajax_url'                    => admin_url( 'admin-ajax.php' ),
				'nonce'                       => wp_create_nonce( 'alynt_pg_nonce' ),
				'i18n_failed_to_load'         => __( 'We could not load the products right now. Please try again.', 'alynt-products-grid' ),
				'i18n_counts_failed'          => __( 'Filter counts could not be updated. You can keep browsing or try again.', 'alynt-products-grid' ),
				'i18n_invalid_context'        => __( 'We could not verify this product grid request. Refresh the page and try again.', 'alynt-products-grid' ),
				'i18n_session_expired'        => __( 'Your session expired. Refresh the page and try again.', 'alynt-products-grid' ),
				'i18n_offline_error'          => __( 'You appear to be offline. Check your connection and try again.', 'alynt-products-grid' ),
				'i18n_network_error'          => __( 'We could not reach the server. Please try again.', 'alynt-products-grid' ),
				'i18n_timeout_error'          => __( 'This is taking longer than expected. Please try again.', 'alynt-products-grid' ),
				'i18n_server_error'           => __( 'The server is temporarily unavailable. Please try again in a few minutes.', 'alynt-products-grid' ),
				'i18n_unexpected_error'       => __( 'Something unexpected happened. Please try again.', 'alynt-products-grid' ),
				'i18n_retry'                  => __( 'Retry', 'alynt-products-grid' ),
				'i18n_previous'               => __( '« Previous', 'alynt-products-grid' ),
				'i18n_next'                   => __( 'Next »', 'alynt-products-grid' ),
				/* translators: 1: range start, 2: range end, 3: total number of products. */
				'i18n_results_count_singular' => __( '%1$s - %2$s of %3$s product', 'alynt-products-grid' ),
				/* translators: 1: range start, 2: range end, 3: total number of products. */
				'i18n_results_count_plural'   => __( '%1$s - %2$s of %3$s products', 'alynt-products-grid' ),
				'i18n_results_count_empty'    => __( 'No products found.', 'alynt-products-grid' ),
				'i18n_no_products_title'      => __( 'No products found.', 'alynt-products-grid' ),
				'i18n_no_products_message'    => __( 'Try a different search term or reset the filters to see more products.', 'alynt-products-grid' ),
				'i18n_no_products_message_search' => __( 'Try a different search term or clear the search to see more products.', 'alynt-products-grid' ),
				'i18n_no_products_message_none' => __( 'Try browsing another page to see more products.', 'alynt-products-grid' ),
				'i18n_adding'                 => __( 'Adding...', 'alynt-products-grid' ),
				'i18n_error_add_to_cart'      => __( 'We could not add this product to your cart. Please try again.', 'alynt-products-grid' ),
				'i18n_choose_options'         => __( 'This product needs to be configured on its product page before it can be added to your cart.', 'alynt-products-grid' ),
				'i18n_view_cart'              => __( 'View cart', 'alynt-products-grid' ),
				'i18n_view_product'           => __( 'View product', 'alynt-products-grid' ),
				'i18n_added_successfully'     => __( 'Product added to cart successfully.', 'alynt-products-grid' ),
				'i18n_failed_add_to_cart'     => __( 'We could not reach the cart. Please try again.', 'alynt-products-grid' ),
				'i18n_close_notification'     => __( 'Close notification', 'alynt-products-grid' ),
				'i18n_cart_notification'      => __( 'Cart notification', 'alynt-products-grid' ),
				/* translators: %s is a page number; used as the pagination button label. */
				'i18n_page_label'             => __( 'Page %s', 'alynt-products-grid' ),
				'i18n_prev_page'              => __( 'Previous page', 'alynt-products-grid' ),
				'i18n_next_page'              => __( 'Next page', 'alynt-products-grid' ),
			)
		);

		if ( ! wp_script_is( 'wc-add-to-cart-params', 'done' ) ) {
			wp_localize_script(
				'alynt-pg-script',
				'wc_add_to_cart_params',
				array(
					'ajax_url'                => WC()->ajax_url(),
					'wc_ajax_url'             => WC_AJAX::get_endpoint( '%%endpoint%%' ),
					'i18n_view_cart'          => esc_attr__( 'View cart', 'woocommerce' ),
					'cart_url'                => apply_filters( 'woocommerce_add_to_cart_redirect', wc_get_cart_url(), null ),
					'is_cart'                 => is_cart(),
					'cart_redirect_after_add' => get_option( 'woocommerce_cart_redirect_after_add', 'no' ),
				)
			);
		}
	}

	public function enqueue_frontend_assets() {
		alynt_pg_enqueue_frontend_assets();
	}

	/**
	 * Returns asset metadata for script and style loading.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_asset_metadata() {
		if ( null !== self::$asset_metadata ) {
			return self::$asset_metadata;
		}

		$style_path  = 'assets/css/style.css';
		$script_path = 'assets/js/script.js';

		self::$asset_metadata = array(
			'style'  => $this->build_asset_metadata( $style_path ),
			'script' => $this->build_asset_metadata( $script_path ),
		);

		return self::$asset_metadata;
	}

	/**
	 * Builds URL and version data for a plugin asset.
	 *
	 * @param string $relative_path Relative asset path.
	 * @return array<string, string>
	 */
	private function build_asset_metadata( $relative_path ) {
		$absolute_path = ALYNT_PG_PLUGIN_DIR . $relative_path;

		return array(
			'url'     => ALYNT_PG_PLUGIN_URL . $relative_path,
			'version' => is_readable( $absolute_path ) ? (string) filemtime( $absolute_path ) : ALYNT_PG_VERSION,
		);
	}
}
