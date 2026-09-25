<?php
/**
 * STMC_Check_Environment_Https_Off_Production check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Https_Off_Production extends STMC_Check_Base {

	public function id(): string {
		return 'environment.https_off_production';
	}

	public function area(): string {
		return 'environment';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'critical';
	}

	public function title(): string {
		return __( 'Site not served over HTTPS', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		if ( ! $snapshot->treat_as_production ) {
			return $this->pass();
		}
		$https = $snapshot->is_ssl || ( 0 === stripos( $snapshot->home_url, 'https://' ) );
		if ( $https ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Checkout and payments risk when the site is not served over HTTPS.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %s: home URL */
					__( 'Home URL scheme is not HTTPS (%s).', 'store-maintenance-checklist-for-woocommerce' ),
					$snapshot->home_url
				),
				'count'   => 1,
			),
			__( 'Settings', 'store-maintenance-checklist-for-woocommerce' ),
			'options-general.php'
		);
	}
}
