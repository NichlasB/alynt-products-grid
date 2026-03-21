<?php
/**
 * Plugin activator.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin activation.
 *
 * @since 1.0.0
 */
class ALYNT_PG_Activator {
	/**
	 * Runs activation tasks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate() {
		flush_rewrite_rules();
	}
}
