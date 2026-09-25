<?php
/**
 * STMC_Check_Orders_Stuck_Pending check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Orders_Stuck_Pending extends STMC_Check_Base {

	public function id(): string {
		return 'orders.stuck_pending';
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
		return __( 'Orders stuck in Pending payment', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->stuck_pending_count;
		if ( $count <= 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Payment or ops backlog: pending payment orders older than 7 days.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'pending payment orders older than 7 days.', 'store-maintenance-checklist-for-woocommerce' ),
				'count'   => $count,
				'samples' => $this->order_id_samples( $snapshot->stuck_pending_samples, $snapshot->admin_url ),
			),
			__( 'Orders', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-orders&status=wc-pending'
		);
	}
}
