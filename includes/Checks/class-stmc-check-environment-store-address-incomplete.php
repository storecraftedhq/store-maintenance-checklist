<?php
/**
 * STMC_Check_Environment_Store_Address_Incomplete check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Store_Address_Incomplete extends STMC_Check_Base {

	public function id(): string {
		return 'environment.store_address_incomplete';
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
		return __( 'Store address is incomplete', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$address = is_array( $snapshot->store_address ) ? $snapshot->store_address : array();
		$line1   = trim( (string) ( $address['address_1'] ?? '' ) );
		$city    = trim( (string) ( $address['city'] ?? '' ) );
		$post    = trim( (string) ( $address['postcode'] ?? '' ) );
		if ( '' !== $line1 && '' !== $city && '' !== $post ) {
			return $this->pass();
		}
		$missing = array();
		if ( '' === $line1 ) {
			$missing[] = __( 'address line 1', 'store-maintenance-checklist-for-woocommerce' );
		}
		if ( '' === $city ) {
			$missing[] = __( 'city', 'store-maintenance-checklist-for-woocommerce' );
		}
		if ( '' === $post ) {
			$missing[] = __( 'postcode', 'store-maintenance-checklist-for-woocommerce' );
		}
		return $this->open(
			$snapshot,
			__( 'Tax and shipping rates use the store base location. A complete address keeps those settings reliable.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %s: comma-separated missing fields */
					__( 'Store address is missing: %s.', 'store-maintenance-checklist-for-woocommerce' ),
					implode( ', ', $missing )
				),
				'count'   => count( $missing ),
			),
			__( 'General', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-settings&tab=general'
		);
	}
}
