<?php
/**
 * STMC_Check_Environment_Plain_Permalinks check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Plain_Permalinks extends STMC_Check_Base {

	public function id(): string {
		return 'environment.plain_permalinks';
	}

	public function area(): string {
		return 'environment';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Pretty permalinks are disabled', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$structure = trim( (string) $snapshot->permalink_structure );
		if ( '' !== $structure ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Plain ?p= URLs hurt shareability, caching, and common storefront setups.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'Permalink structure is set to Plain (empty). Choose a pretty structure such as Post name.', 'store-maintenance-checklist-for-woocommerce' ),
			),
			__( 'Permalinks', 'store-maintenance-checklist-for-woocommerce' ),
			'options-permalink.php'
		);
	}
}
