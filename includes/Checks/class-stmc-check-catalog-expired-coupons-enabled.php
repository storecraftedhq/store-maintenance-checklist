<?php
/**
 * STMC_Check_Catalog_Expired_Coupons_Enabled check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Catalog_Expired_Coupons_Enabled extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.expired_coupons_enabled';
	}

	public function area(): string {
		return 'catalog';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'info';
	}

	public function title(): string {
		return __( 'Expired coupons still published', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$count = (int) $snapshot->expired_enabled_coupon_count;
		if ( $count <= 0 ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Expired coupons still published clutter marketing ops and confuse staff.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: %d: coupon count */
					__( '%d expired coupons are still published.', 'store-maintenance-checklist' ),
					$count
				),
				'count'   => $count,
				'samples' => $this->coupon_samples( $snapshot->expired_enabled_coupon_samples, $snapshot->admin_url ),
			),
			__( 'Coupons', 'store-maintenance-checklist' ),
			'edit.php?post_type=shop_coupon'
		);
	}

	/**
	 * @param array<int, int|string|array<string, mixed>> $rows      Coupon IDs or {id,code} rows.
	 * @param string                                      $admin_url Admin base URL.
	 * @return array<int, array{label: string, url: string}>
	 */
	private function coupon_samples( array $rows, string $admin_url ): array {
		$samples = array();
		$base    = rtrim( $admin_url, '/' );

		foreach ( array_slice( $rows, 0, 3 ) as $row ) {
			$id   = 0;
			$code = '';
			if ( is_array( $row ) ) {
				$id   = (int) ( $row['id'] ?? 0 );
				$code = (string) ( $row['code'] ?? '' );
			} else {
				$id = (int) $row;
			}
			if ( $id <= 0 ) {
				continue;
			}
			$label     = '' !== $code ? $code : ( '#' . $id );
			$samples[] = array(
				'label' => $label,
				'url'   => $base . '/post.php?post=' . $id . '&action=edit',
			);
		}

		return $samples;
	}
}
