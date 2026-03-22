<?php
/**
 * Plugin Name: Alynt Products Grid
 * Plugin URI: https://alynt.com
 * Description: A WooCommerce mobile-responsive products grid with advanced filtering via shortcode.
 * Version: 1.0.1
 * Author: Alynt
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 6.8.2
 * WC requires at least: 3.0
 * WC tested up to: 10.0.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: alynt-products-grid
 * Domain Path: /languages
 * GitHub Plugin URI: NichlasB/alynt-products-grid
 *
 * @package Alynt_Products_Grid
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ALYNT_PG_VERSION', '1.0.1' );
define( 'ALYNT_PG_MIN_WP_VERSION', '5.0' );
define( 'ALYNT_PG_MIN_PHP_VERSION', '7.4' );
define( 'ALYNT_PG_PRODUCT_CATEGORIES_TRANSIENT', 'alynt_pg_product_categories' );
define( 'ALYNT_PG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALYNT_PG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALYNT_PG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

if ( ! function_exists( 'alynt_pg_enqueue_frontend_assets' ) ) {
	function alynt_pg_enqueue_frontend_assets() {
		wp_enqueue_style( 'alynt-pg-style' );
		wp_enqueue_script( 'alynt-pg-script' );
	}
}

if ( ! function_exists( 'alynt_pg_sign_grid_context' ) ) {
	function alynt_pg_sign_grid_context( $grid_context ) {
		return wp_hash( (string) wp_json_encode( $grid_context ), 'nonce' );
	}
}

if ( ! function_exists( 'alynt_pg_normalize_search_term' ) ) {
	function alynt_pg_normalize_search_term( $search ) {
		if ( ! is_string( $search ) ) {
			return '';
		}

		$search = sanitize_text_field( $search );
		$search = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $search );

		if ( ! is_string( $search ) ) {
			return '';
		}

		$search = preg_replace( '/\s+/u', ' ', trim( $search ) );

		if ( ! is_string( $search ) ) {
			return '';
		}

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $search, 0, 200 );
		}

		return substr( $search, 0, 200 );
	}
}

if ( ! function_exists( 'alynt_pg_render_minimum_requirements_notice' ) ) {
	function alynt_pg_render_minimum_requirements_notice() {
		echo wp_kses_post(
			sprintf(
				'<div class="notice notice-error"><p>%s</p></div>',
				sprintf(
					esc_html__( 'Alynt Products Grid requires WordPress %1$s or later and PHP %2$s or later.', 'alynt-products-grid' ),
					ALYNT_PG_MIN_WP_VERSION,
					ALYNT_PG_MIN_PHP_VERSION
				)
			)
		);
	}
}

if ( ! function_exists( 'alynt_pg_render_woocommerce_missing_notice' ) ) {
	function alynt_pg_render_woocommerce_missing_notice() {
		echo wp_kses_post(
			sprintf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'Alynt Products Grid requires WooCommerce to be installed and active.', 'alynt-products-grid' )
			)
		);
	}
}

if ( ! function_exists( 'alynt_pg_is_woocommerce_active' ) ) {
	function alynt_pg_is_woocommerce_active() {
		if ( ! function_exists( 'is_plugin_active' ) || ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_multisite() && is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
			return true;
		}

		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}
}

if ( version_compare( PHP_VERSION, ALYNT_PG_MIN_PHP_VERSION, '<' ) ) {
	add_action( 'admin_notices', 'alynt_pg_render_minimum_requirements_notice' );
	return;
}

$alynt_pg_wp_version = isset( $GLOBALS['wp_version'] ) ? $GLOBALS['wp_version'] : null;

if ( null !== $alynt_pg_wp_version && version_compare( $alynt_pg_wp_version, ALYNT_PG_MIN_WP_VERSION, '<' ) ) {
	add_action( 'admin_notices', 'alynt_pg_render_minimum_requirements_notice' );
	return;
}

require_once ALYNT_PG_PLUGIN_DIR . 'includes/class-alynt-pg-activator.php';
require_once ALYNT_PG_PLUGIN_DIR . 'includes/class-alynt-pg-deactivator.php';
require_once ALYNT_PG_PLUGIN_DIR . 'includes/class-alynt-pg-products-query-service.php';
require_once ALYNT_PG_PLUGIN_DIR . 'includes/class-alynt-pg-shortcode-renderer.php';
require_once ALYNT_PG_PLUGIN_DIR . 'includes/class-alynt-pg-ajax-handler.php';
require_once ALYNT_PG_PLUGIN_DIR . 'includes/class-alynt-pg-plugin.php';
require_once ALYNT_PG_PLUGIN_DIR . 'includes/class-alynt-products-grid.php';

register_activation_hook( __FILE__, array( 'ALYNT_PG_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALYNT_PG_Deactivator', 'deactivate' ) );

// Check if WooCommerce is active.
if ( ! alynt_pg_is_woocommerce_active() ) {
	add_action( 'admin_notices', 'alynt_pg_render_woocommerce_missing_notice' );
	return;
}

// Initialize the plugin.
new Alynt_Products_Grid();
