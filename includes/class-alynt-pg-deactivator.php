<?php
/**
 * Plugin deactivator.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin deactivation.
 *
 * @since 1.0.0
 */
class ALYNT_PG_Deactivator {
	/**
	 * Runs deactivation tasks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
