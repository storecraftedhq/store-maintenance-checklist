<?php
/**
 * STMC_Check_Payments_Taxes_Enabled_No_Rates check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Payments_Taxes_Enabled_No_Rates extends STMC_Check_Base {

	public function id(): string {
		return 'payments.taxes_enabled_no_rates';
	}

	public function area(): string {
		return 'payments';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Taxes enabled but no tax rates configured', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		if ( ! $snapshot->taxes_enabled || (int) $snapshot->tax_rates_count > 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Tax calculation is turned on, but no rates exist for WooCommerce to apply.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'woocommerce_calc_taxes is enabled and the tax rates table is empty.', 'store-maintenance-checklist-for-woocommerce' ),
				'count'   => 0,
			),
			__( 'Tax', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-settings&tab=tax'
		);
	}
}
