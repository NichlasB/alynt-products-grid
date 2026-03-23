# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] - 2026-03-23

### Added
- Add URL state management for filter combinations
- Add AJAX handler class for async product filtering
- Add settings documentation

### Changed
- Enhance products grid with URL state and AJAX support
- Improve frontend grid components
- Update README with new documentation

### Fixed
- Improve product grid stability

## [1.1.0] - 2026-03-22

### Added
- Add request error handling module for AJAX error categorization and recovery
- Add empty-state.php partial for consistent no-results display
- Add translatable PHP and JavaScript strings for grid notices and cart feedback
- Add ARIA labels, roles, and live regions for improved accessibility
- Add reduced-motion support and modal focus handling

### Changed
- Refactor frontend grid components for better error recovery
- Improve responsive breakpoints and feedback styling
- Split plugin architecture into focused include classes
- Modularize frontend source files and reorganize template partials

### Fixed
- Accessibility: improve focus states and disabled button behavior

## [1.0.1] - 2026-03-20

### Added
- Development tooling baseline for npm, Composer, PHPCS, PHPUnit, and release automation.
- GitHub release workflow for Alynt Plugin Updater compatibility.
- WordPress.org distribution metadata and project infrastructure files.

### Changed
- Added plugin header metadata for PHP, license, and GitHub updater compatibility.
- Added activation and deactivation hook infrastructure.

## [1.0.0] - 2026-03-20

### Added
- WooCommerce product grid shortcode with AJAX filtering, search, pagination, and responsive layout.
