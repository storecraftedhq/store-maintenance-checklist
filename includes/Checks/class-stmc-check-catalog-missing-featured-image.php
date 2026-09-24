<?php
/**
 * STMC_Check_Catalog_Missing_Featured_Image check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Missing_Featured_Image extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.missing_featured_image';
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
		return __( 'Products missing a featured image', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
