<?php
/**
 * STMC_Check_Catalog_Missing_Short_Description check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Missing_Short_Description extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.missing_short_description';
	}

	public function area(): string {
		return 'catalog';
	}

	public function runner(): string {
		return 'as';
	}

	public function severity(): string {
		return 'info';
	}

	public function title(): string {
		return __( 'Products missing a short description', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
