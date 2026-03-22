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

$alynt_pg_delete_blog_data = static function () {
	global $wpdb;

	delete_option( 'alynt_pg_cache_version' );
	delete_transient( 'alynt_pg_product_categories' );

	$transient_prefixes = array(
		'alynt_pg_category_counts_',
		'alynt_pg_category_slug_ids_',
	);

	foreach ( $transient_prefixes as $transient_prefix ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $transient_prefix ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . $transient_prefix ) . '%'
			)
		);
	}
};

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'number' => 0,
			'fields' => 'ids',
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		$alynt_pg_delete_blog_data();
		restore_current_blog();
	}

	return;
}

$alynt_pg_delete_blog_data();
