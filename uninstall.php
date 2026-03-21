<?php
/**
 * Uninstall Alynt Products Grid
 *
 * @package Alynt_Products_Grid
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Currently, this plugin doesn't store any persistent data that needs cleanup.
// If in the future you add options, transients, or custom data, clean them up here.
