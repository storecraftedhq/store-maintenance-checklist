<?php
/**
 * STMC_Check_Orders_Hpos_Compat_Mode check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Orders_Hpos_Compat_Mode extends STMC_Check_Base {

	public function id(): string {
		return 'orders.hpos_compat_mode';
	}

	public function area(): string {
		return 'orders';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'HPOS compatibility mode still enabled', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		if ( ! $snapshot->hpos_compat_mode ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Compatibility mode adds dual-write cost and migration debt.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'HPOS compatibility mode is enabled.', 'store-maintenance-checklist-for-woocommerce' ),
				'count'   => 1,
			),
			__( 'Features', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-settings&tab=advanced&section=features'
		);
	}
}
