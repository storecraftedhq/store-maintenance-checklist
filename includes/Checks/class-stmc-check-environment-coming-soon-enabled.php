<?php
/**
 * STMC_Check_Environment_Coming_Soon_Enabled check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Coming_Soon_Enabled extends STMC_Check_Base {

	public function id(): string {
		return 'environment.coming_soon_enabled';
	}

	public function area(): string {
		return 'environment';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'critical';
	}

	public function title(): string {
		return __( 'WooCommerce Coming soon mode is enabled', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		if ( empty( $snapshot->coming_soon_enabled ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Coming soon mode can hide the storefront from shoppers.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'woocommerce_coming_soon is enabled.', 'store-maintenance-checklist' ),
				'count'   => 1,
			),
			__( 'Settings', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=site-visibility'
		);
	}
}
