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
 */
class ALYNT_PG_Deactivator {
	/**
	 * Runs deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
