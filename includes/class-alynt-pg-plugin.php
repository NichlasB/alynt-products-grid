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
	 * Shortcode renderer instance.
	 *
	 * @var ALYNT_PG_Shortcode_Renderer
	 */
	private $shortcode_renderer;

	/**
	 * AJAX handler instance.
	 *
	 * @var ALYNT_PG_Ajax_Handler
	 */
	private $ajax_handler;

	/**
	 * Sets up plugin services and hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$products_query_service   = new ALYNT_PG_Products_Query_Service();
		$this->shortcode_renderer = new ALYNT_PG_Shortcode_Renderer( $products_query_service );
		$this->ajax_handler       = new ALYNT_PG_Ajax_Handler( $products_query_service );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_alynt_pg_filter_products', array( $this->ajax_handler, 'ajax_filter_products' ) );
		add_action( 'wp_ajax_nopriv_alynt_pg_filter_products', array( $this->ajax_handler, 'ajax_filter_products' ) );
		add_action( 'wp_ajax_alynt_pg_get_category_counts', array( $this->ajax_handler, 'ajax_get_category_counts' ) );
		add_action( 'wp_ajax_nopriv_alynt_pg_get_category_counts', array( $this->ajax_handler, 'ajax_get_category_counts' ) );
	}

	/**
	 * Registers runtime hooks that must run on init.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		add_shortcode( 'alynt_products_grid', array( $this->shortcode_renderer, 'render_shortcode' ) );
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
	 * Enqueues frontend assets when the shortcode is present.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'alynt_products_grid' ) ) {
			$style_path  = 'assets/css/style.css';
			$style_file  = ALYNT_PG_PLUGIN_DIR . $style_path;
			$script_path = 'assets/js/script.js';
			$script_file = ALYNT_PG_PLUGIN_DIR . $script_path;

			if ( file_exists( ALYNT_PG_PLUGIN_DIR . 'assets/dist/frontend/index.css' ) ) {
				$style_path = 'assets/dist/frontend/index.css';
				$style_file = ALYNT_PG_PLUGIN_DIR . $style_path;
			}

			if ( file_exists( ALYNT_PG_PLUGIN_DIR . 'assets/dist/frontend/index.js' ) ) {
				$script_path = 'assets/dist/frontend/index.js';
				$script_file = ALYNT_PG_PLUGIN_DIR . $script_path;
			}

			wp_enqueue_style(
				'alynt-pg-style',
				ALYNT_PG_PLUGIN_URL . $style_path,
				array(),
				file_exists( $style_file ) ? (string) filemtime( $style_file ) : ALYNT_PG_VERSION
			);

			wp_enqueue_script( 'wc-add-to-cart' );

			wp_enqueue_script(
				'alynt-pg-script',
				ALYNT_PG_PLUGIN_URL . $script_path,
				array( 'jquery', 'wc-add-to-cart' ),
				file_exists( $script_file ) ? (string) filemtime( $script_file ) : ALYNT_PG_VERSION,
				true
			);

			wp_localize_script(
				'alynt-pg-script',
				'alynt_pg_ajax',
				array(
					'ajax_url'                        => admin_url( 'admin-ajax.php' ),
					'nonce'                           => wp_create_nonce( 'alynt_pg_nonce' ),
					'i18n_failed_to_load'             => __( 'Failed to load products', 'alynt-products-grid' ),
					'i18n_previous'                   => __( '« Previous', 'alynt-products-grid' ),
					'i18n_next'                       => __( 'Next »', 'alynt-products-grid' ),
					/* translators: 1: range start, 2: range end, 3: total number of products. */
					'i18n_results_count_singular'     => __( '%1$s - %2$s of %3$s product', 'alynt-products-grid' ),
					/* translators: 1: range start, 2: range end, 3: total number of products. */
					'i18n_results_count_plural'       => __( '%1$s - %2$s of %3$s products', 'alynt-products-grid' ),
					'i18n_adding'                     => __( 'Adding...', 'alynt-products-grid' ),
					'i18n_error_add_to_cart'          => __( 'Error adding product to cart', 'alynt-products-grid' ),
					'i18n_view_cart'                  => __( 'View cart', 'alynt-products-grid' ),
					'i18n_added_successfully'         => __( 'Product added to cart successfully!', 'alynt-products-grid' ),
					'i18n_failed_add_to_cart'         => __( 'Failed to add product to cart', 'alynt-products-grid' ),
					/* translators: aria-label for the close button on error notifications. */
					'i18n_close_notification'         => __( 'Close notification', 'alynt-products-grid' ),
					/* translators: aria-label for the add-to-cart success modal dialog. */
					'i18n_cart_notification'          => __( 'Cart notification', 'alynt-products-grid' ),
					/* translators: %s is a page number; used as aria-label on pagination buttons. */
					'i18n_page_label'                 => __( 'Page %s', 'alynt-products-grid' ),
					/* translators: aria-label for the previous page pagination button. */
					'i18n_prev_page'                  => __( 'Previous page', 'alynt-products-grid' ),
					/* translators: aria-label for the next page pagination button. */
					'i18n_next_page'                  => __( 'Next page', 'alynt-products-grid' ),
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
						'cart_redirect_after_add' => get_option( 'woocommerce_cart_redirect_after_add' ),
					)
				);
			}
		}
	}
}
