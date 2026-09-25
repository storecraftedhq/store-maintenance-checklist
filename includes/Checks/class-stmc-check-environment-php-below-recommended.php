<?php
/**
 * STMC_Check_Environment_Php_Below_Recommended check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Php_Below_Recommended extends STMC_Check_Base {

	public const MIN_VERSION = '8.3';

	public function id(): string {
		return 'environment.php_below_recommended';
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
		return __( 'PHP version below WooCommerce recommendation', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$version = (string) $snapshot->php_version;
		if ( '' === $version || version_compare( $version, self::MIN_VERSION, '>=' ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'WooCommerce recommends PHP 8.3 or greater for security and extension compatibility.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: 1: current PHP version, 2: recommended minimum */
					__( 'PHP %1$s is below the recommended minimum of %2$s.', 'store-maintenance-checklist-for-woocommerce' ),
					$version,
					self::MIN_VERSION
				),
				'count'   => 1,
			),
			__( 'Status', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-status'
		);
	}
}
