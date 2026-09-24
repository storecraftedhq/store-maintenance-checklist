<?php
/**
 * STMC_Check_Shipping_Zone_Without_Methods check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Shipping_Zone_Without_Methods extends STMC_Check_Base {

	/**
	 * {@inheritdoc}
	 */
	public function id(): string {
		return 'shipping.zone_without_methods';
	}

	/**
	 * {@inheritdoc}
	 */
	public function area(): string {
		return 'shipping';
	}

	/**
	 * {@inheritdoc}
	 */
	public function runner(): string {
		return 'sync';
	}

	/**
	 * {@inheritdoc}
	 */
	public function severity(): string {
		return 'warning';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title(): string {
		return __( 'One or more shipping zones have no methods', 'store-maintenance-checklist' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$empty = array();
		foreach ( $snapshot->shipping_zones as $zone ) {
			if ( ! empty( $zone['default'] ) ) {
				continue;
			}
			$methods = $zone['methods'] ?? array();
			if ( empty( $methods ) ) {
				$empty[] = (string) ( $zone['name'] ?? (string) ( $zone['id'] ?? '' ) );
			}
		}
		if ( empty( $empty ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Regional checkout gaps occur when a zone has no shipping methods.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: %s: zone names */
					__( 'Zones without methods: %s', 'store-maintenance-checklist' ),
					implode( ', ', $empty )
				),
				'count'   => count( $empty ),
				'samples' => array_slice( $empty, 0, 3 ),
			),
			__( 'Shipping', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=shipping'
		);
	}
}
