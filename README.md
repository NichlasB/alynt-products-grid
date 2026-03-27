# Alynt Products Grid

Alynt Products Grid is a WooCommerce plugin for WordPress that renders products in a responsive, AJAX-powered grid with category filtering, search, and pagination.

It is built for shortcode-driven storefront sections where you want a cleaner product browsing experience without relying on the default WooCommerce archive templates.

## Highlights

- Responsive product grid with up to 5 columns
- AJAX filtering and pagination without full page reloads
- Category buttons with live product counts
- Debounced search input
- Shareable URLs that preserve active filters and search terms
- Support for category allowlists and highlighted "special" categories
- Custom responsive breakpoints
- WooCommerce add-to-cart integration
- Out-of-stock handling
- Translation-ready strings and language files

## Requirements

- WordPress 5.0 or later
- WooCommerce 3.0 or later
- PHP 7.4 or later

## Installation

1. Copy the `alynt-products-grid` folder into `/wp-content/plugins/`.
2. Activate the plugin from the WordPress admin Plugins screen.
3. Make sure WooCommerce is installed and active.
4. Add the shortcode to a page, post, or other content area.

## Quick Start

Basic usage:

```text
[alynt_products_grid]
```

Example with custom settings:

```text
[alynt_products_grid columns="4" categories="electronics,clothing" special="sale,featured" per_page="16"]
```

## Shortcode Reference

### Available Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `columns` | integer | `4` | Maximum columns to display. Clamped from 1 to 5. |
| `categories` | string | `''` | Comma-separated category IDs or slugs to include in the grid. |
| `special` | string | `''` | Comma-separated category IDs or slugs to highlight and sort near the front of the filter list. |
| `per_page` | integer | `12` | Products per page. Maximum 100. |
| `filter_mode` | string | `'default'` | Filter UI mode: `default`, `search`, or `none`. |
| `breakpoint_5` | integer | `1200` | Width where 5 columns reduce to 4. |
| `breakpoint_4` | integer | `992` | Width where 4 columns reduce to 3. |
| `breakpoint_3` | integer | `768` | Width where 3 columns reduce to 2. |
| `breakpoint_2` | integer | `576` | Width where 2 columns reduce to 1. |

### Filter Modes

| Mode | Category Buttons | Search Field | Reset/Clear Button |
|------|------------------|--------------|--------------------|
| `default` | Yes | Yes | Yes |
| `search` | No | Yes | Yes |
| `none` | No | No | No |

### Usage Examples

Only show products from selected categories:

```text
[alynt_products_grid categories="organic-herbs,tinctures,teas"]
```

Highlight special categories:

```text
[alynt_products_grid categories="supplements,teas,oils" special="featured,sale"]
```

Show 3 columns and 20 products per page:

```text
[alynt_products_grid columns="3" per_page="20"]
```

Search-only mode:

```text
[alynt_products_grid filter_mode="search"]
```

Grid with no filter UI:

```text
[alynt_products_grid filter_mode="none"]
```

Custom responsive breakpoints:

```text
[alynt_products_grid columns="5" breakpoint_5="1400" breakpoint_4="1100" breakpoint_3="820" breakpoint_2="560"]
```

## How It Works

### Frontend Behavior

- Category buttons support multi-select filtering.
- Category counts update dynamically based on the current result set.
- Search requests are debounced before sending AJAX calls.
- Pagination updates the grid in place and scrolls back to the top of the component.
- Filter state is pushed into the browser URL so links can be shared or revisited.

### Product Card Output

Each product card can include:

- Product image
- Product category labels
- Product title linked to the product page
- Product price
- Add-to-cart button when purchasable
- Out-of-stock message when unavailable

## Styling

The plugin ships with frontend styles, but the markup is intentionally class-based so theme overrides are straightforward.

Common CSS selectors:

```css
.alynt-pg-container
.alynt-pg-filters
.alynt-pg-category-btn
.alynt-pg-category-btn.active
.alynt-pg-category-btn.disabled
.alynt-pg-category-special
.alynt-pg-search
.alynt-pg-reset-btn
.alynt-pg-products-grid
.alynt-pg-product-card
.alynt-pg-pagination
```

Example overrides:

```css
.alynt-pg-category-btn.active,
.alynt-pg-page-btn.active {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

.alynt-pg-add-to-cart-btn {
    background: #15803d;
}

.alynt-pg-reset-btn {
    background: #b91c1c;
}
```

## Performance Notes

- Requests are handled through WordPress AJAX endpoints.
- Search input is debounced to reduce unnecessary requests.
- Per-page values are capped to avoid oversized product queries.
- Enqueued assets use versioned loading for cache busting.

## Troubleshooting

### The grid does not appear

- Confirm WooCommerce is active.
- Confirm the shortcode is present in rendered page content.
- Check the browser console for JavaScript errors.

### Filters return unexpected results

- Verify the category slugs or IDs used in the shortcode.
- Confirm products are assigned to the expected WooCommerce categories.
- Check product visibility, catalog status, and stock state.

### Styles look broken

- Check for theme or page-builder CSS overrides.
- Confirm plugin CSS is being loaded on the page.
- Inspect `.alynt-pg-*` selectors in browser dev tools.

## Development

### Project Structure

```text
alynt-products-grid/
|- alynt-products-grid.php
|- uninstall.php
|- assets/
|  |- css/
|  |- js/
|  `- src/
|- docs/
|  |- HOOKS.md
|  `- SETTINGS.md
|- includes/
|- languages/
|- public/
|  `- partials/
|- templates/
|- tests/
|- composer.json
|- package.json
|- readme.txt
`- README.md
```

### Useful Commands

Install PHP dependencies:

```bash
composer install
```

Install Node dependencies:

```bash
npm install
```

Build frontend assets:

```bash
npm run build
```

Watch frontend assets during development:

```bash
npm run dev
```

Run PHP coding standards:

```bash
npm run lint
```

Run automated tests:

```bash
npm run test
```

Generate the translation template:

```bash
npm run pot
```

## Documentation

- `docs/SETTINGS.md` documents shortcode attributes and localized frontend data.
- `docs/HOOKS.md` lists the WordPress and WooCommerce hooks used by the plugin.
- `readme.txt` contains the WordPress.org-style metadata and changelog.

## Changelog

### 1.2.0

- Added URL state management for filter combinations.
- Added AJAX handler improvements for product filtering.
- Expanded documentation and release workflow support.
- Improved frontend grid behavior and stability.

### 1.1.0

- Added error handling and empty-state improvements.
- Added localization support for interface strings.
- Improved accessibility with ARIA and motion-related refinements.
- Refactored the plugin into more focused classes and frontend modules.

### 1.0.1

- Added project tooling and release automation.
- Added updater metadata compatibility.

### 1.0.0

- Initial release.

## License

GPL v2 or later. See `LICENSE` for details.
