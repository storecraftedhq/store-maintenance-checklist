<?php
/**
 * STMC_Check_Environment_Memory_Below_Recommended check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Environment_Memory_Below_Recommended extends STMC_Check_Base {

	public const MIN_BYTES = 268435456; // 256 MB.

	public function id(): string {
		return 'environment.memory_below_recommended';
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
		return __( 'WordPress memory limit below recommendation', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$bytes = (int) $snapshot->wp_memory_limit_bytes;
		if ( $bytes <= 0 || $bytes >= self::MIN_BYTES ) {
			return $this->pass();
		}
		$url = defined( 'STMC_URL_WC_MEMORY_LIMIT_DOCS' )
			? STMC_URL_WC_MEMORY_LIMIT_DOCS
			: 'https://woocommerce.com/document/increasing-the-wordpress-memory-limit/';

		return $this->open(
			$snapshot,
			__( 'WooCommerce recommends a WordPress memory limit of 256 MB or greater.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: 1: current memory limit in MB, 2: recommended minimum in MB */
					__( 'WP_MEMORY_LIMIT is about %1$d MB (recommended minimum %2$d MB).', 'store-maintenance-checklist' ),
					(int) floor( $bytes / ( 1024 * 1024 ) ),
					256
				),
				'count'   => 1,
			),
			__( 'Guide', 'store-maintenance-checklist' ),
			$url
		);
	}
}
