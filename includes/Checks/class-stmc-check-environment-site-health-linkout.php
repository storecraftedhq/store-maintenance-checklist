<?php
/**
 * STMC_Check_Environment_Site_Health_Linkout check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Site_Health_Linkout extends STMC_Check_Base {

	public function id(): string {
		return 'environment.site_health_linkout';
	}

	public function area(): string {
		return 'environment';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'info';
	}

	public function title(): string {
		return __( 'Review Site Health for environment details', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		return $this->open(
			$snapshot,
			__( 'Also review Site Health for broader environment details.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'Soft nudge to open Site Health.', 'store-maintenance-checklist-for-woocommerce' ),
				'count'   => 1,
			),
			__( 'Site Health', 'store-maintenance-checklist-for-woocommerce' ),
			'site-health.php',
			array( 'score_excluded' => true )
		);
	}
}
