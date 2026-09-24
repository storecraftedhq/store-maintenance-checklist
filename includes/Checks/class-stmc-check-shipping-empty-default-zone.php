<?php
/**
 * STMC_Check_Shipping_Empty_Default_Zone check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Shipping_Empty_Default_Zone extends STMC_Check_Base {

	public function id(): string {
		return 'shipping.empty_default_zone';
	}

	public function area(): string {
		return 'shipping';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Default / catch-all shipping zone has no methods', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		foreach ( $snapshot->shipping_zones as $zone ) {
			if ( empty( $zone['default'] ) ) {
				continue;
			}
			$methods = $zone['methods'] ?? array();
			if ( ! empty( $methods ) ) {
				return $this->pass();
			}
			return $this->open(
				$snapshot,
				__( 'Unmatched locations cannot ship when the catch-all zone has no methods.', 'store-maintenance-checklist' ),
				array(
					'summary' => sprintf(
						/* translators: %s: zone name */
						__( 'Default zone "%s" has 0 methods.', 'store-maintenance-checklist' ),
						(string) ( $zone['name'] ?? 'default' )
					),
					'count'   => 0,
					'samples' => array( (string) ( $zone['name'] ?? 'default' ) ),
				),
				__( 'Shipping', 'store-maintenance-checklist' ),
				'admin.php?page=wc-settings&tab=shipping'
			);
		}
		return $this->pass();
	}
}
