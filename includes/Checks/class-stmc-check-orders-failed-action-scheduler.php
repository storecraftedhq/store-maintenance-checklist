<?php
/**
 * STMC_Check_Orders_Failed_Action_Scheduler check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Orders_Failed_Action_Scheduler extends STMC_Check_Base {

	public function id(): string {
		return 'orders.failed_action_scheduler';
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
		return __( 'Failed Action Scheduler jobs', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->failed_as_count;
		if ( $count <= 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Background work may be broken (emails, webhooks).', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: %d: failed action count */
					__( '%d failed Action Scheduler jobs.', 'store-maintenance-checklist' ),
					$count
				),
				'count'   => $count,
			),
			__( 'Scheduled Actions', 'store-maintenance-checklist' ),
			'admin.php?page=wc-status&tab=action-scheduler&status=failed'
		);
	}
}
