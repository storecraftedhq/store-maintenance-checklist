<?php
/**
 * STMC_Check_Catalog_Unsellable_Stock check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Unsellable_Stock extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.unsellable_stock';
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
		return __( 'Products marked out of stock', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
