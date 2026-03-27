=== Alynt Products Grid ===
Contributors: alynt
Tags: woocommerce, products, grid, shortcode, ajax
Requires at least: 5.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display WooCommerce products in a responsive AJAX-powered grid with filtering, search, and pagination.

== Description ==

Alynt Products Grid adds a shortcode for rendering WooCommerce products in a mobile-friendly, AJAX-powered grid.

It is useful when you want to place a browsable product section on a landing page, homepage, or custom content layout without sending visitors through the default shop archive experience.

Features include:

* Responsive product grid with configurable columns
* AJAX-powered category filtering and pagination
* Search with debounced requests
* Shareable URLs that preserve active filters and search terms
* Support for category allowlists and highlighted special categories
* Dynamic category counts
* WooCommerce add-to-cart integration
* Out-of-stock handling
* Custom responsive breakpoints

Basic shortcode:

`[alynt_products_grid]`

Example with attributes:

`[alynt_products_grid columns="4" categories="electronics,clothing" special="sale,featured" per_page="16"]`

Available shortcode attributes:

* `columns` - Maximum columns to display, from 1 to 5
* `categories` - Comma-separated category IDs or slugs to include
* `special` - Comma-separated category IDs or slugs to highlight
* `per_page` - Number of products per page, maximum 100
* `filter_mode` - `default`, `search`, or `none`
* `breakpoint_5` - Width where 5 columns reduce to 4
* `breakpoint_4` - Width where 4 columns reduce to 3
* `breakpoint_3` - Width where 3 columns reduce to 2
* `breakpoint_2` - Width where 2 columns reduce to 1

Filter modes:

* `default` shows category buttons, search, and reset controls
* `search` shows only search and clear controls
* `none` removes the filter UI entirely

== Installation ==

1. Upload the `alynt-products-grid` folder to `/wp-content/plugins/`.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Make sure WooCommerce is installed and active.
4. Add the `[alynt_products_grid]` shortcode to a page or post.

== Frequently Asked Questions ==

= Does this require WooCommerce? =

Yes. The plugin depends on WooCommerce and will show an admin notice if WooCommerce is not active.

= Can I limit the grid to specific categories? =

Yes. Use the `categories` attribute with category IDs or slugs.

Example:

`[alynt_products_grid categories="tinctures,teas,oils"]`

= Can I show only the search field and hide category buttons? =

Yes. Use `filter_mode="search"`.

Example:

`[alynt_products_grid filter_mode="search"]`

= Can I remove all filters and just show the grid? =

Yes. Use `filter_mode="none"`. Pagination still works normally.

= Does the plugin preserve filter state in the URL? =

Yes. Active categories and search terms are pushed into the browser URL so filtered views can be shared or revisited.

= Can I change the number of columns on different screen sizes? =

Yes. Use the `columns` attribute together with the breakpoint attributes to control when the grid collapses from 5 to 4, 4 to 3, 3 to 2, and 2 to 1 column.

== Changelog ==

= 1.2.1 =

* Documentation improvements and formatting enhancements.

= 1.2.0 =

* Added shareable URL state management for filter combinations.
* Improved AJAX product filtering support and frontend behavior.
* Expanded documentation and release workflow support.
* Improved grid stability.

= 1.1.0 =

* Added request error handling and a consistent empty-state partial.
* Added localization support for interface strings.
* Improved accessibility with ARIA labels, live regions, and reduced-motion support.
* Refactored the plugin into more focused include classes and frontend modules.
* Improved responsive breakpoints and feedback styling.

= 1.0.1 =

* Added project tooling, testing scaffolding, and release automation.
* Added updater metadata compatibility.

= 1.0.0 =

* Initial release.

== Upgrade Notice ==

= 1.2.1 =

No breaking changes. Documentation improvements and formatting enhancements.

= 1.2.0 =

No breaking changes. This release adds shareable URL state management and improves AJAX filtering behavior.

= 1.1.0 =

No breaking changes. This release improves accessibility, localization, and frontend error handling.
