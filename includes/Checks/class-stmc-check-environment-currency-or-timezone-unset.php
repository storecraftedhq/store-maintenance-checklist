<?php
/**
 * STMC_Check_Environment_Currency_Or_Timezone_Unset check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Currency_Or_Timezone_Unset extends STMC_Check_Base {

	public function id(): string {
		return 'environment.currency_or_timezone_unset';
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
		return __( 'Store currency or site timezone is unset', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$currency    = trim( (string) $snapshot->woocommerce_currency );
		$tz          = trim( (string) $snapshot->timezone_string );
		$offset      = trim( (string) $snapshot->gmt_offset );
		$currency_ok = '' !== $currency;
		$timezone_ok = '' !== $tz || '' !== $offset;
		if ( $currency_ok && $timezone_ok ) {
			return $this->pass();
		}
		$missing = array();
		if ( ! $currency_ok ) {
			$missing[] = __( 'currency', 'store-maintenance-checklist' );
		}
		if ( ! $timezone_ok ) {
			$missing[] = __( 'timezone', 'store-maintenance-checklist' );
		}
		return $this->open(
			$snapshot,
			__( 'Unset currency or timezone leads to wrong money display or order timestamps.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: %s: comma-separated missing fields */
					__( 'Unset: %s.', 'store-maintenance-checklist' ),
					implode( ', ', $missing )
				),
				'count'   => count( $missing ),
			),
			__( 'General', 'store-maintenance-checklist' ),
			'options-general.php'
		);
	}
}
