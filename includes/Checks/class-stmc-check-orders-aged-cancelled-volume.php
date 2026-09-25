<?php
/**
 * STMC_Check_Orders_Aged_Cancelled_Volume check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Orders_Aged_Cancelled_Volume extends STMC_Check_Base {

	/**
	 * {@inheritdoc}
	 */
	public function id(): string {
		return 'orders.aged_cancelled_volume';
	}

	/**
	 * {@inheritdoc}
	 */
	public function area(): string {
		return 'orders';
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
		return 'info';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title(): string {
		return __( 'Large volume of aged cancelled orders', 'store-maintenance-checklist-for-woocommerce' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->aged_cancelled_count;
		if ( $count < 5000 ) {
			return $this->pass();
		}
		$url = defined( 'STMC_URL_AUTO_ARCHIVE' )
			? STMC_URL_AUTO_ARCHIVE
			: 'https://storecrafted.com/product/auto-archive-old-orders-for-woocommerce/';
		return $this->open(
			$snapshot,
			__( 'Aged cancelled orders add ops clutter over time.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %d: order count */
					__( '%d cancelled orders older than 24 months.', 'store-maintenance-checklist-for-woocommerce' ),
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
