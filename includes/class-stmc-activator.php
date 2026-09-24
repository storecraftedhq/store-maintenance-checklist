<?php
/**
 * Activation handler.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Runs on plugin activation.
 */
final class STMC_Activator {

	/**
	 * Ensure WooCommerce is present; otherwise deactivate and die.
	 */
	public static function activate(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( plugin_basename( STMC_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'Store Maintenance Checklist for WooCommerce requires WooCommerce.', 'store-maintenance-checklist' ),
				esc_html__( 'Plugin activation error', 'store-maintenance-checklist' ),
				array( 'back_link' => true )
			);
		}
	}
}
