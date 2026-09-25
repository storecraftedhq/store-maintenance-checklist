<?php
/**
 * STMC_Check_Orders_Overdue_Action_Scheduler check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Orders_Overdue_Action_Scheduler extends STMC_Check_Base {

	public function id(): string {
		return 'orders.overdue_action_scheduler';
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
		return __( 'Overdue pending Action Scheduler jobs', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->overdue_as_count;
		if ( $count <= 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Cron or queue lag: pending actions past their scheduled date.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %d: overdue count */
					__( '%d overdue pending Action Scheduler jobs.', 'store-maintenance-checklist-for-woocommerce' ),
					$count
				),
				'count'   => $count,
			),
			__( 'Scheduled Actions', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-status&tab=action-scheduler&status=past-due'
		);
	}
}
