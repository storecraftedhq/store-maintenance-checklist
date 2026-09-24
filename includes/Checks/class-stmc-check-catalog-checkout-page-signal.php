<?php
/**
 * STMC_Check_Catalog_Checkout_Page_Signal check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Checkout_Page_Signal extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.checkout_page_signal';
	}

	public function area(): string {
		return 'catalog';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Checkout page may not be a valid checkout', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$page = $snapshot->checkout_page;
		$id   = (int) ( $page['id'] ?? 0 );
		$ok   = $id > 0 && ( ! empty( $page['has_checkout_block'] ) || ! empty( $page['has_shortcode'] ) );
		if ( $ok ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'The assigned checkout page may be empty or missing the checkout block/shortcode.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'Checkout page signal failed heuristics.', 'store-maintenance-checklist' ),
				'count'   => 1,
				'samples' => array( $id ),
			),
			__( 'Checkout', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=advanced'
		);
	}
}
