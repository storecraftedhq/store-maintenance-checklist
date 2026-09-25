<?php
/**
 * STMC_Check_Environment_Debug_Display_Production check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Debug_Display_Production extends STMC_Check_Base {

	public function id(): string {
		return 'environment.debug_display_production';
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
		return __( 'WP_DEBUG_DISPLAY enabled on production', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		if ( ! $snapshot->treat_as_production ) {
			return $this->pass();
		}
		if ( ! $snapshot->debug_display ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Errors may be exposed to visitors when WP_DEBUG_DISPLAY is on.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'WP_DEBUG_DISPLAY is enabled.', 'store-maintenance-checklist-for-woocommerce' ),
				'count'   => 1,
			),
			__( 'Hosting', 'store-maintenance-checklist-for-woocommerce' ),
			'options-general.php'
		);
	}
}
