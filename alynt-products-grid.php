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
define( 'ALYNT_PG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALYNT_PG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ALYNT_PG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

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
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo wp_kses_post( '<div class="notice notice-error"><p><strong>Alynt Products Grid</strong> requires WooCommerce to be installed and active.</p></div>' );
		}
	);
	return;
}

// Initialize the plugin.
new Alynt_Products_Grid();
