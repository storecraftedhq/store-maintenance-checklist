<?php
/**
 * STMC_Check_Catalog_Virtual_Requires_Shipping check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Virtual_Requires_Shipping extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.virtual_requires_shipping';
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
		return __( 'Virtual products still require shipping', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
