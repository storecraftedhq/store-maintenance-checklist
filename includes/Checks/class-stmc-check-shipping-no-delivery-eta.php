<?php
/**
 * STMC_Check_Shipping_No_Delivery_Eta check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Shipping_No_Delivery_Eta extends STMC_Check_Base {

	public function id(): string {
		return 'shipping.no_delivery_eta';
	}

	public function area(): string {
		return 'shipping';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'info';
	}

	public function title(): string {
		return __( 'No delivery time messaging for physical shipping', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		if ( ! $this->has_physical_shipping_methods( $snapshot ) ) {
			return $this->pass();
		}
		if ( $snapshot->has_delivery_eta_signal ) {
			return $this->pass();
		}

		$url = defined( 'STMC_URL_EXPECTED_DELIVERY' )
			? STMC_URL_EXPECTED_DELIVERY
			: 'https://storecrafted.com/product/expected-delivery-times-for-woocommerce/';

		return $this->open(
			$snapshot,
			__( 'Shoppers often abandon or contact support when physical shipping offers no delivery time guidance.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'Physical shipping methods are configured, but no delivery ETA or shipping-time messaging was detected.', 'store-maintenance-checklist' ),
			),
			__( 'Shipping', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=shipping',
			array(
				'further_tools' => array(
					array(
						'label' => __( 'Expected Delivery Times for WooCommerce', 'store-maintenance-checklist' ),
						'url'   => $url,
					),
				),
			)
		);
	}

	/**
	 * @param STMC_Check_Snapshot $snapshot Snapshot.
	 */
	private function has_physical_shipping_methods( STMC_Check_Snapshot $snapshot ): bool {
		foreach ( $snapshot->shipping_zones as $zone ) {
			foreach ( (array) ( $zone['methods'] ?? array() ) as $method ) {
				$id = is_array( $method ) ? (string) ( $method['id'] ?? '' ) : (string) $method;
				if ( '' !== $id && 'local_pickup' !== $id ) {
					return true;
				}
			}
		}
		return false;
	}
}
