<?php
/**
 * STMC_Check_Environment_Wp_Below_Recommended check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Wp_Below_Recommended extends STMC_Check_Base {

	public const MIN_VERSION = '6.9';

	public function id(): string {
		return 'environment.wp_below_recommended';
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
		return __( 'WordPress version below WooCommerce recommendation', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$version = (string) $snapshot->wp_version;
		if ( '' === $version || version_compare( $version, self::MIN_VERSION, '>=' ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'WooCommerce recommends WordPress 6.9 or greater for security and compatibility.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: 1: current WordPress version, 2: recommended minimum */
					__( 'WordPress %1$s is below the recommended minimum of %2$s.', 'store-maintenance-checklist' ),
					$version,
					self::MIN_VERSION
				),
				'count'   => 1,
			),
			__( 'Updates', 'store-maintenance-checklist' ),
			'update-core.php'
		);
	}
}
