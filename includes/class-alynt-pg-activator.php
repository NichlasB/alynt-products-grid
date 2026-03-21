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
 */
class ALYNT_PG_Activator {
	/**
	 * Runs activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		flush_rewrite_rules();
	}
}
