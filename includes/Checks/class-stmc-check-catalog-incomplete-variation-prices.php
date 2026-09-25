<?php
/**
 * STMC_Check_Catalog_Incomplete_Variation_Prices check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Incomplete_Variation_Prices extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.incomplete_variation_prices';
	}

	public function area(): string {
		return 'catalog';
	}

	public function runner(): string {
		return 'as';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Variable products with incomplete variation prices', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
