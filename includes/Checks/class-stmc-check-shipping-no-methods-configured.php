<?php
/**
 * STMC_Check_Shipping_No_Methods_Configured check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Shipping_No_Methods_Configured extends STMC_Check_Base {

	public function id(): string {
		return 'shipping.no_methods_configured';
	}

	public function area(): string {
		return 'shipping';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'critical';
	}

	public function title(): string {
		return __( 'No shipping methods configured', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$total = 0;
		foreach ( $snapshot->shipping_zones as $zone ) {
			$total += count( $zone['methods'] ?? array() );
		}
		if ( $total > 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Physical checkout is often blocked when no shipping methods exist.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'No shipping methods in any zone.', 'store-maintenance-checklist' ),
				'count'   => 0,
			),
			__( 'Shipping', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=shipping'
		);
	}
}
