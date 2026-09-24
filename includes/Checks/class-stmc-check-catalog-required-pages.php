<?php
/**
 * STMC_Check_Catalog_Required_Pages check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Required_Pages extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.required_pages';
	}

	public function area(): string {
		return 'catalog';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'critical';
	}

	public function title(): string {
		return __( 'Required WooCommerce pages missing or mis-assigned', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$missing = array();
		foreach ( array( 'shop', 'cart', 'checkout', 'myaccount' ) as $key ) {
			if ( empty( $snapshot->wc_pages[ $key ] ) ) {
				$missing[] = $key;
			}
		}
		if ( empty( $missing ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Cart, checkout, or account pages are missing or not assigned in WooCommerce settings.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: %s: comma-separated page keys */
					__( 'Missing or unassigned pages: %s', 'store-maintenance-checklist' ),
					implode( ', ', $missing )
				),
				'count'   => count( $missing ),
				'samples' => $missing,
			),
			__( 'Advanced', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=advanced'
		);
	}
}
