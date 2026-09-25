<?php
/**
 * STMC_Check_Environment_Updates_Available check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Updates_Available extends STMC_Check_Base {

	public function id(): string {
		return 'environment.updates_available';
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
		return __( 'WordPress or WooCommerce updates available', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$wp = ! empty( $snapshot->wp_update_available );
		$wc = ! empty( $snapshot->wc_update_available );
		if ( ! $wp && ! $wc ) {
			return $this->pass();
		}
		$parts = array();
		if ( $wp ) {
			$parts[] = __( 'WordPress', 'store-maintenance-checklist-for-woocommerce' );
		}
		if ( $wc ) {
			$parts[] = 'WooCommerce';
		}
		return $this->open(
			$snapshot,
			__( 'Pending updates increase security and compatibility risk.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %s: comma-separated list of update targets */
					__( 'Updates available for: %s.', 'store-maintenance-checklist-for-woocommerce' ),
					implode( ', ', $parts )
				),
				'count'   => count( $parts ),
			),
			__( 'Updates', 'store-maintenance-checklist-for-woocommerce' ),
			'update-core.php'
		);
	}
}
