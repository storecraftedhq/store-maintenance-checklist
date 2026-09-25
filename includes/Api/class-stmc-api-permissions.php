<?php
/**
 * REST capability gate.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Shared permission callback for stmc/v1.
 */
final class STMC_Api_Permissions {

	/**
	 * Require manage_woocommerce.
	 *
	 * @return bool|WP_Error
	 */
	public static function check() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		return new WP_Error(
			'stmc_forbidden',
			__( 'You do not have permission to manage the maintenance checklist.', 'store-maintenance-checklist-for-woocommerce' ),
			array( 'status' => 403 )
		);
	}
}
