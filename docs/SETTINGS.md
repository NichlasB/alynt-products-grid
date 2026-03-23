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
| `filter_mode` | string | `'default'` | `sanitize_key`, validated against allowlist | Controls which filter UI is displayed. See [Filter Modes](#filter-modes) below. |
| `breakpoint_5` | integer | `1200` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 5 to 4 columns. Only relevant when `columns` is 5. |
| `breakpoint_4` | integer | `992` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 4 to 3 columns. |
| `breakpoint_3` | integer | `768` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 3 to 2 columns. |
| `breakpoint_2` | integer | `576` | `esc_attr` (output only) | Viewport width (px) at which the grid transitions from 2 to 1 column. |

---

## Filter Modes

The `filter_mode` attribute controls which filtering UI is rendered above the product grid. If the attribute is omitted or set to an unrecognised value, `default` is used.

| Value | Category buttons | Search field | Reset/Clear button | Button label |
|-------|-----------------|--------------|-------------------|--------------|
| `default` | Yes | Yes | Yes | Reset |
| `search` | No | Yes | Yes | Clear |
| `none` | No | No | No | — |

### Behaviour notes

- **`default`** — Full filter UI with category buttons, search, and a Reset button. Category counts update dynamically via AJAX. This is the behaviour that existed before `filter_mode` was introduced.
- **`search`** — Shows only the search input and a button labelled **Clear**. Category count requests are suppressed entirely. The `search` URL parameter is preserved for shareability.
- **`none`** — The entire filter container is removed from the DOM. No category or search state is tracked. Pagination continues to work normally.

### Examples

```
[alynt_products_grid]
[alynt_products_grid filter_mode="default"]
[alynt_products_grid filter_mode="search"]
[alynt_products_grid filter_mode="none"]
```

---

## Localized JavaScript Data

The following data is passed to the frontend script via `wp_localize_script` under the `alynt_pg_ajax` object:

| Key | Value | Description |
|-----|-------|-------------|
| `ajax_url` | `admin_url( 'admin-ajax.php' )` | WordPress AJAX endpoint URL. |
| `nonce` | `wp_create_nonce( 'alynt_pg_nonce' )` | Nonce verified on all AJAX requests via `check_ajax_referer`. |
