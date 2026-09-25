<?php
/**
 * STMC_Check_Orders_Stuck_On_Hold check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Orders_Stuck_On_Hold extends STMC_Check_Base {

	public function id(): string {
		return 'orders.stuck_on_hold';
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
		return __( 'Orders stuck On hold', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->stuck_on_hold_count;
		if ( $count <= 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Fulfilment backlog: on-hold orders older than 7 days.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'on-hold orders older than 7 days.', 'store-maintenance-checklist-for-woocommerce' ),
				'count'   => $count,
				'samples' => $this->order_id_samples( $snapshot->stuck_on_hold_samples, $snapshot->admin_url ),
			),
			__( 'Orders', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-orders&status=wc-on-hold'
		);
	}
}
