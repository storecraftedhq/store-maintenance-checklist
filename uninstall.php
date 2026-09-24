<?php
/**
 * Uninstall Store Maintenance Checklist for WooCommerce.
 *
 * Deletes all plugin options, stmc_* transients, and Action Scheduler group `stmc`.
 * Does not delete WooCommerce or WordPress core data.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$stmc_option_keys = array(
	'stmc_settings',
	'stmc_ignores',
	'stmc_latest_results',
	'stmc_history',
	'stmc_scan_state',
);

foreach ( $stmc_option_keys as $stmc_option_key ) {
	delete_option( $stmc_option_key );
}

// Delete any remaining stmc_* options and stmc_* transients discovered in the options table.
if ( isset( $GLOBALS['stmc_test_options'] ) && is_array( $GLOBALS['stmc_test_options'] ) ) {
	foreach ( array_keys( $GLOBALS['stmc_test_options'] ) as $stmc_discovered_key ) {
		if ( ! is_string( $stmc_discovered_key ) ) {
			continue;
		}
		if ( 0 === strpos( $stmc_discovered_key, 'stmc_' )
			|| 0 === strpos( $stmc_discovered_key, '_transient_stmc_' )
			|| 0 === strpos( $stmc_discovered_key, '_transient_timeout_stmc_' )
		) {
			delete_option( $stmc_discovered_key );
		}
	}
} elseif ( isset( $GLOBALS['wpdb'] ) && is_object( $GLOBALS['wpdb'] ) ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup only.
	$wpdb->query(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'stmc\_%' OR option_name LIKE '\_transient\_stmc\_%' OR option_name LIKE '\_transient\_timeout\_stmc\_%'"
	);
}

if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'stmc/scan/catalog_batch', null, 'stmc' );
	as_unschedule_all_actions( 'stmc/scan/finalize', null, 'stmc' );
	as_unschedule_all_actions( 'stmc/scan/variation_batch', null, 'stmc' );
	// Clear any remaining actions in the plugin group.
	as_unschedule_all_actions( null, null, 'stmc' );
}
