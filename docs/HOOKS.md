# Hooks Reference

This plugin does not currently define any custom WordPress actions or filters.

---

## WordPress Core Hooks Registered

The following core WordPress and WooCommerce hooks are registered by this plugin:

### Actions

| Hook | Priority | Callback | Description |
|------|----------|----------|-------------|
| `init` | 10 | `ALYNT_PG_Plugin::init()` | Registers the `[alynt_products_grid]` shortcode and loads the text domain. |
| `wp_enqueue_scripts` | 10 | `ALYNT_PG_Plugin::enqueue_scripts()` | Enqueues CSS and JS assets on pages that contain the shortcode. |
| `wp_ajax_alynt_pg_filter_products` | 10 | `ALYNT_PG_Ajax_Handler::ajax_filter_products()` | Handles logged-in AJAX product filter requests. |
| `wp_ajax_nopriv_alynt_pg_filter_products` | 10 | `ALYNT_PG_Ajax_Handler::ajax_filter_products()` | Handles logged-out AJAX product filter requests. |
| `wp_ajax_alynt_pg_get_category_counts` | 10 | `ALYNT_PG_Ajax_Handler::ajax_get_category_counts()` | Handles logged-in AJAX category count requests. |
| `wp_ajax_nopriv_alynt_pg_get_category_counts` | 10 | `ALYNT_PG_Ajax_Handler::ajax_get_category_counts()` | Handles logged-out AJAX category count requests. |

### Filters (consumed, not defined)

| Hook | Where Used | Description |
|------|------------|-------------|
| `active_plugins` | `alynt-products-grid.php` | Used to check whether WooCommerce is active before initializing the plugin. |
| `posts_where` | `ALYNT_PG_Products_Query_Service::get_products_data()`, `get_category_counts()` | Temporarily attached and removed per-query to restrict WP_Query results to title-only matches. |
| `woocommerce_add_to_cart_redirect` | `ALYNT_PG_Plugin::enqueue_scripts()` | Used to pass the cart redirect URL to the `wc_add_to_cart_params` JavaScript object. |
