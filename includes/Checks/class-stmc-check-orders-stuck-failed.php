<?php
/**
 * STMC_Check_Orders_Stuck_Failed check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Warns when Failed orders are older than 7 days.
 */
final class STMC_Check_Orders_Stuck_Failed extends STMC_Check_Base {

	public function id(): string {
		return 'orders.stuck_failed';
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
		return __( 'Orders stuck Failed', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->stuck_failed_count;
		if ( $count <= 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Unresolved payment failures: failed orders older than 7 days.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'failed orders older than 7 days.', 'store-maintenance-checklist' ),
				'count'   => $count,
				'samples' => $this->order_id_samples( $snapshot->stuck_failed_samples, $snapshot->admin_url ),
			),
			__( 'Orders', 'store-maintenance-checklist' ),
			'admin.php?page=wc-orders&status=wc-failed'
		);
	}
}
