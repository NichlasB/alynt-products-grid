# Settings Reference

This plugin has no settings page and stores no options in the WordPress database. All configuration is done through shortcode attributes.

---

## Shortcode Attributes

Use the `[alynt_products_grid]` shortcode with the following attributes:

| Attribute | Type | Default | Sanitization | Description |
|-----------|------|---------|--------------|-------------|
| `columns` | integer | `4` | `intval`, clamped 1–5 | Maximum number of columns in the grid. |
| `categories` | string | `''` | `sanitize_text_field` per item, resolved via `normalize_category_ids()` | Comma-separated category IDs or slugs to restrict the grid to. Leave empty to show all categories. |
| `special` | string | `''` | `sanitize_text_field` per item, resolved via `normalize_category_ids()` | Comma-separated category IDs or slugs to highlight. These appear first after the "All" button and receive the `.alynt-pg-category-special` CSS class. |
| `per_page` | integer | `12` | `intval`, clamped 1–100 | Number of products displayed per page. Maximum is 100. |
| `breakpoint_5` | integer | `1200` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 5 to 4 columns. Only relevant when `columns` is 5. |
| `breakpoint_4` | integer | `992` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 4 to 3 columns. |
| `breakpoint_3` | integer | `768` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 3 to 2 columns. |
| `breakpoint_2` | integer | `576` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 2 to 1 column. |

---

## Localized JavaScript Data

The following data is passed to the frontend script via `wp_localize_script` under the `alynt_pg_ajax` object:

| Key | Value | Description |
|-----|-------|-------------|
| `ajax_url` | `admin_url( 'admin-ajax.php' )` | WordPress AJAX endpoint URL. |
| `nonce` | `wp_create_nonce( 'alynt_pg_nonce' )` | Nonce verified on all AJAX requests via `check_ajax_referer`. |
