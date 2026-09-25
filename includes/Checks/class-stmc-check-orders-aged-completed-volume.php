<?php
/**
 * STMC_Check_Orders_Aged_Completed_Volume check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Orders_Aged_Completed_Volume extends STMC_Check_Base {

	public function id(): string {
		return 'orders.aged_completed_volume';
	}

	public function area(): string {
		return 'orders';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Large volume of aged completed orders', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->aged_completed_count;
		if ( $count < 10000 ) {
			return $this->pass();
		}
		$url = defined( 'STMC_URL_AUTO_ARCHIVE' )
			? STMC_URL_AUTO_ARCHIVE
			: 'https://storecrafted.com/product/auto-archive-old-orders-for-woocommerce/';
		return $this->open(
			$snapshot,
			__( 'Admin and report performance can suffer with large aged completed order volumes.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %d: order count */
					__( '%d completed orders older than 24 months.', 'store-maintenance-checklist-for-woocommerce' ),
					$count
				),
				'count'   => $count,
			),
			__( 'Orders', 'store-maintenance-checklist-for-woocommerce' ),
			'edit.php?post_type=shop_order',
			array(
				'further_tools' => array(
					array(
						'label' => __( 'Auto Archive Old Orders for WooCommerce', 'store-maintenance-checklist-for-woocommerce' ),
						'url'   => $url,
					),
				),
			)
		);
	}
}
