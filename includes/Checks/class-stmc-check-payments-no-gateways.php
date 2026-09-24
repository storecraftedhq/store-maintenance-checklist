<?php
/**
 * STMC_Check_Payments_No_Gateways check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Payments_No_Gateways extends STMC_Check_Base {

	public function id(): string {
		return 'payments.no_gateways';
	}

	public function area(): string {
		return 'payments';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'critical';
	}

	public function title(): string {
		return __( 'No enabled payment gateways', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		if ( ! empty( $snapshot->enabled_gateways ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'The store cannot take payment until at least one gateway is enabled.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'Enabled gateway count = 0', 'store-maintenance-checklist' ),
				'count'   => 0,
			),
			__( 'Payments', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=checkout'
		);
	}
}
