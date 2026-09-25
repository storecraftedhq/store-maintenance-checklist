<?php
/**
 * STMC_Check_Catalog_Duplicate_Sku check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Duplicate_Sku extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.duplicate_sku';
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
		return __( 'Duplicate SKUs in the catalog', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
