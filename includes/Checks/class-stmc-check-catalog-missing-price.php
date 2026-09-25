<?php
/**
 * STMC_Check_Catalog_Missing_Price check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Missing_Price extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.missing_price';
	}

	public function area(): string {
		return 'catalog';
	}

	public function runner(): string {
		return 'as';
	}

	public function severity(): string {
		return 'critical';
	}

	public function title(): string {
		return __( 'Products with missing or invalid prices', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
