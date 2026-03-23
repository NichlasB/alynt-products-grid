# Alynt Products Grid WordPress Plugin

A powerful WooCommerce plugin that displays products in a mobile-responsive grid with advanced filtering capabilities via shortcode.

## Features

- **Responsive product grid** with customizable columns (up to 5)
- **Advanced filtering** with category buttons and search functionality
- **Dynamic category states** - categories with no matching products are greyed out and show (0)
- **AJAX-powered** - no page refreshes, smooth user experience
- **URL updates** - filters update the browser URL for shareability and SEO
- **Numbered pagination** with ellipsis for large page counts
- **Mobile-optimized** with customizable responsive breakpoints
- **Performance optimized** with cache-busting parameters
- **Out-of-stock handling** - shows "Out of stock" instead of add-to-cart button

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+

## Installation

1. Download the plugin files
2. Upload the `alynt-products-grid` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Ensure WooCommerce is installed and active

## Usage

### Basic Shortcode

```
[alynt_products_grid]
```

### Advanced Shortcode with Attributes

```
[alynt_products_grid 
    columns="4" 
    categories="electronics,clothing" 
    special="sale,featured"
    per_page="16" 
    breakpoint_5="1400"
    breakpoint_4="1200"
    breakpoint_3="900"
    breakpoint_2="600"]
```

## Shortcode Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `columns` | Integer | 4 | Maximum number of columns (1-5) |
| `categories` | String | '' | Comma-separated category IDs or slugs to include |
| `special` | String | '' | Comma-separated category IDs or slugs to highlight (appear first with distinct styling) |
| `per_page` | Integer | 12 | Number of products per page (max 100) |
| `filter_mode` | String | 'default' | Filter UI mode: `default` (full filters), `search` (search only), or `none` (no filters) |
| `breakpoint_5` | Integer | 1200 | Breakpoint for 5→4 columns (px) |
| `breakpoint_4` | Integer | 992 | Breakpoint for 4→3 columns (px) |
| `breakpoint_3` | Integer | 768 | Breakpoint for 3→2 columns (px) |
| `breakpoint_2` | Integer | 576 | Breakpoint for 2→1 columns (px) |

## Examples

### Display only specific categories:
```
[alynt_products_grid categories="123,456,789"]
[alynt_products_grid categories="electronics,clothing,accessories"]
```

### Highlight special categories (appear first with distinct styling):
```
[alynt_products_grid special="sale,featured"]
[alynt_products_grid categories="electronics,clothing" special="sale"]
```

### 3-column grid with 20 products per page:
```
[alynt_products_grid columns="3" per_page="20"]
```

### Search-only mode (no category buttons):
```
[alynt_products_grid filter_mode="search"]
```

### No filters at all:
```
[alynt_products_grid filter_mode="none"]
```

### Custom responsive breakpoints:
```
[alynt_products_grid 
    breakpoint_5="1400" 
    breakpoint_4="1100" 
    breakpoint_3="800" 
    breakpoint_2="500"]
```

## Product Card Elements

Each product card displays (in order):

1. **Product image** (300x300px container)
2. **Product categories** as small tags
3. **Product title** (linked to product page)
4. **Price and add-to-cart button** side by side
   - Or "Out of stock" message if unavailable

## Grid Behavior

### Filtering
- **Category buttons**: Click to filter by categories (multiple selection supported)
- **Special categories**: Highlighted categories appear first (after "All" button) with darker styling
- **Search field**: Real-time search with 300ms debounce
- **Reset button**: Clears all filters and search
- **Dynamic states**: Categories with no matching products are greyed out with (0) count

### Filter Modes

The `filter_mode` attribute controls which filter UI is shown:

| Mode | Category buttons | Search | Button |
|------|-----------------|--------|--------|
| `default` | Yes | Yes | Reset |
| `search` | No | Yes | Clear |
| `none` | No | No | — |

### URL Updates
- Filters automatically update the browser URL with SEO-friendly category slugs
- Example: `?categories=organic-herbs,tinctures&search=chamomile`
- URLs can be shared to maintain filter state
- Browser back/forward buttons work correctly
- Backwards compatible with old ID-based URLs

### Pagination
- Numbered pagination with Previous/Next buttons
- Ellipsis (…) for large page counts
- Auto-scroll to grid top when changing pages

## Styling & Customization

### CSS Classes

The plugin uses these main CSS classes for customization:

```css
.alynt-pg-container          /* Main container */
.alynt-pg-filters            /* Filter section */
.alynt-pg-category-btn       /* Category buttons */
.alynt-pg-category-btn.active   /* Active category */
.alynt-pg-category-btn.disabled /* Disabled category */
.alynt-pg-category-special   /* Special category buttons */
.alynt-pg-search            /* Search input */
.alynt-pg-reset-btn         /* Reset button */
.alynt-pg-products-grid     /* Products grid */
.alynt-pg-product-card      /* Individual product card */
.alynt-pg-pagination        /* Pagination container */
```

### Color Customization

The plugin uses neutral colors that can be easily customized:

```css
/* Primary color (buttons, links) */
.alynt-pg-category-btn.active,
.alynt-pg-page-btn.active {
    background: #your-primary-color;
    border-color: #your-primary-color;
}

/* Add to cart button */
.alynt-pg-add-to-cart-btn {
    background: #your-success-color;
}

/* Reset button */
.alynt-pg-reset-btn {
    background: #your-danger-color;
}
```

## Performance Considerations

### Cache Compatibility
- CSS/JS files include cache-busting timestamps
- AJAX requests are not cached (as expected for dynamic content)
- Compatible with GridPane Redis caching

### Optimization Features
- Maximum 100 products per page limit
- Debounced search (300ms delay)
- Efficient database queries with proper indexing
- Minimal DOM manipulation during updates

## Browser Support

- Chrome/Chromium 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Plugin not working
1. Ensure WooCommerce is active
2. Check for JavaScript errors in browser console
3. Verify shortcode syntax

### Categories not filtering correctly
1. Check category IDs/slugs are correct
2. Ensure products are assigned to categories
3. Verify WooCommerce product visibility settings

### Styling issues
1. Check for theme CSS conflicts
2. Ensure plugin CSS is loading
3. Use browser dev tools to inspect elements

## Development

### File Structure
```
alynt-products-grid/
├── alynt-products-grid.php          # Main plugin file
├── uninstall.php                    # Uninstall handler
├── assets/
│   ├── css/
│   │   └── style.css                # Frontend styles
│   ├── js/
│   │   └── script.js               # Frontend JavaScript
│   └── src/                        # Source files for build
├── docs/
│   ├── HOOKS.md                    # Hook reference
│   └── SETTINGS.md                 # Shortcode attribute reference
├── includes/
│   ├── class-alynt-products-grid.php          # Backwards-compat wrapper
│   ├── class-alynt-pg-activator.php           # Activation handler
│   ├── class-alynt-pg-deactivator.php         # Deactivation handler
│   ├── class-alynt-pg-plugin.php              # Main plugin orchestrator
│   ├── class-alynt-pg-ajax-handler.php        # AJAX endpoints
│   ├── class-alynt-pg-shortcode-renderer.php  # Shortcode rendering
│   └── class-alynt-pg-products-query-service.php # Product queries
├── languages/                       # Translation files
├── public/
│   └── partials/
│       ├── products-grid.php        # Main grid template
│       └── product-card.php         # Product card template
├── templates/                       # Legacy template wrappers
└── README.md                        # This file
```

### Hooks & Filters

This plugin does not currently expose custom action or filter hooks. See [docs/HOOKS.md](docs/HOOKS.md) for details.

## Changelog

### Version 1.1.0
- **Filter mode control**: New `filter_mode` shortcode attribute (`default`, `search`, `none`) to control which filter UI is rendered
  - `search` mode shows only the search input with a **Clear** button; category count requests are suppressed
  - `none` mode removes the entire filter container; pagination remains functional
  - `default` (or no attribute) preserves all existing behavior

### Version 1.0.1
- **Improved URL format**: URLs now use SEO-friendly category slugs instead of IDs
  - Example: `?categories=organic-herbs,tinctures` instead of `?categories=66,198`
  - Backwards compatible with old ID-based URLs
- Enhanced shareability and search engine visibility

### Version 1.0.0
- Initial release
- Full responsive grid with AJAX filtering
- Category and search filtering
- URL state management
- Mobile optimization

## Support

For support, please check:
1. This README file
2. WordPress admin error logs
3. Browser console for JavaScript errors

## License

This plugin is licensed under GPL v2 or later.
