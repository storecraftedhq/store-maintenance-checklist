<?php
/**
 * STMC_Check_Environment_Db_Below_Recommended check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Db_Below_Recommended extends STMC_Check_Base {

	public const MIN_MYSQL   = '8.0';
	public const MIN_MARIADB = '10.6';

	public function id(): string {
		return 'environment.db_below_recommended';
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
		return __( 'Database version below WooCommerce recommendation', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$type    = strtolower( trim( (string) $snapshot->db_server_type ) );
		$version = $this->normalize_version( (string) $snapshot->db_server_version );
		if ( '' === $type || '' === $version ) {
			return $this->pass();
		}

		$is_mariadb = ( 'mariadb' === $type );
		$min        = $is_mariadb ? self::MIN_MARIADB : self::MIN_MYSQL;
		$label      = $is_mariadb ? 'MariaDB' : 'MySQL';

		if ( version_compare( $version, $min, '>=' ) ) {
			return $this->pass();
		}

		return $this->open(
			$snapshot,
			__( 'WooCommerce recommends MySQL 8.0+ or MariaDB 10.6+ for security and performance.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: 1: MySQL or MariaDB, 2: current version, 3: recommended minimum */
					__( '%1$s %2$s is below the recommended minimum of %3$s.', 'store-maintenance-checklist-for-woocommerce' ),
					$label,
					$version,
					$min
				),
				'count'   => 1,
			),
			__( 'Status', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-status'
		);
	}

	/**
	 * Strip vendor suffixes from VERSION() output (e.g. 10.6.12-MariaDB).
	 */
	private function normalize_version( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( preg_match( '/^(\d+(?:\.\d+)*)/', $raw, $matches ) ) {
			return $matches[1];
		}
		return '';
	}
}
