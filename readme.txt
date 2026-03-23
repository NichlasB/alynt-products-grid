=== Alynt Products Grid ===
Contributors: alynt
Tags: woocommerce, products, grid, shortcode, ajax
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display WooCommerce products in a responsive AJAX-powered grid with category filtering, search, and pagination.

== Description ==

Alynt Products Grid provides a shortcode for rendering WooCommerce products in a mobile-responsive grid with advanced filtering.

Features include:
- Responsive product grid with configurable columns
- AJAX category filtering and search
- URL state updates for sharing filter combinations
- Pagination with previous/next controls and ellipsis
- Out-of-stock handling and WooCommerce add-to-cart integration

Use the shortcode `[alynt_products_grid]` to display the grid.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Make sure WooCommerce is installed and active.
4. Add the `[alynt_products_grid]` shortcode to a page or post.

== Frequently Asked Questions ==

= Does this require WooCommerce? =

Yes. The plugin depends on WooCommerce and will show an admin notice if WooCommerce is not active.

= Can I limit the grid to certain categories? =

Yes. Use the shortcode attributes to pass category IDs or slugs.

== Changelog ==

= 1.2.0 =
* Added URL state management for sharing filter combinations.
* Added AJAX handler class for async product filtering.
* Added settings documentation.
* Enhanced products grid with improved frontend components.
* Improved grid stability.

= 1.1.0 =
* Added request error handling module for AJAX error categorization and recovery.
* Added empty-state partial for consistent no-results display.
* Added full localization support for grid interface strings.
* Added ARIA labels, roles, and live regions for accessibility.
* Added reduced-motion support and modal focus handling.
* Refactored frontend grid components for better error recovery.
* Improved responsive breakpoints and feedback styling.
* Split plugin architecture into focused include classes.

= 1.0.1 =
* Added project tooling, testing scaffolding, and GitHub release automation.
* Added updater metadata for Alynt Plugin Updater compatibility.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.0 =
No breaking changes. Includes URL state management and improved AJAX support.

= 1.1.0 =
No breaking changes. Includes error handling improvements, localization support, and accessibility enhancements.
