<?php
/**
 * STMC_Check_Environment_Wc_Status_Linkout check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Wc_Status_Linkout extends STMC_Check_Base {

	/**
	 * {@inheritdoc}
	 */
	public function id(): string {
		return 'environment.wc_status_linkout';
	}

	/**
	 * {@inheritdoc}
	 */
	public function area(): string {
		return 'environment';
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
		return __( 'Review WooCommerce Status for system details', 'store-maintenance-checklist' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		return $this->open(
			$snapshot,
			__( 'Also review WooCommerce Status for DB updates, templates, and system details.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'Soft nudge to open WooCommerce Status.', 'store-maintenance-checklist' ),
				'count'   => 1,
			),
			__( 'Status', 'store-maintenance-checklist' ),
			'admin.php?page=wc-status',
			array( 'score_excluded' => true )
		);
	}
}
