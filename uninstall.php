<?php
/**
 * Uninstall Alynt Products Grid
 *
 * @package Alynt_Products_Grid
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Currently, this plugin doesn't store any persistent data that needs cleanup.
// If in the future you add options, transients, or custom data, clean them up here.

// Example cleanup code (uncomment if needed):
// delete_option('alynt_pg_settings');
// delete_transient('alynt_pg_cache');
