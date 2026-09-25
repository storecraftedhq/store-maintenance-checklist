<?php
/**
 * STMC_Check_Catalog_Downloadable_Missing_File check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Downloadable_Missing_File extends STMC_Check_Base {

	/**
	 * {@inheritdoc}
	 */
	public function id(): string {
		return 'catalog.downloadable_missing_file';
	}

	/**
	 * {@inheritdoc}
	 */
	public function area(): string {
		return 'catalog';
	}

	/**
	 * {@inheritdoc}
	 */
	public function runner(): string {
		return 'as';
	}

	/**
	 * {@inheritdoc}
	 */
	public function severity(): string {
		return 'critical';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title(): string {
		return __( 'Downloadable products missing files', 'store-maintenance-checklist-for-woocommerce' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		unset( $snapshot );
		return $this->pass();
	}
}
