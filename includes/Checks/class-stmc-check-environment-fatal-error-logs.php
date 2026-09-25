<?php
/**
 * STMC_Check_Environment_Fatal_Error_Logs check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Fatal_Error_Logs extends STMC_Check_Base {

	public function id(): string {
		return 'environment.fatal_error_logs';
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
		return __( 'WooCommerce fatal error logs present', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->fatal_error_log_count;
		if ( $count <= 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Unresolved PHP fatals can break checkout and admin screens. Review and clear the fatal-errors log after fixing the cause.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %d: number of non-empty fatal-errors log files */
					__( '%d non-empty fatal-errors log file(s) found.', 'store-maintenance-checklist-for-woocommerce' ),
					$count
				),
				'count'   => $count,
			),
			__( 'Logs', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-status&tab=logs'
		);
	}
}
