<?php
/**
 * STMC_Check_Payments_Gateway_Test_Mode check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Payments_Gateway_Test_Mode extends STMC_Check_Base {

	/**
	 * {@inheritdoc}
	 */
	public function id(): string {
		return 'payments.gateway_test_mode';
	}

	/**
	 * {@inheritdoc}
	 */
	public function area(): string {
		return 'payments';
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
		return 'critical';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title(): string {
		return __( 'Payment gateway in test / sandbox mode', 'store-maintenance-checklist' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$in_test = array();
		foreach ( $snapshot->enabled_gateways as $gateway ) {
			if ( ! is_array( $gateway ) ) {
				continue;
			}
			// Only fail when test_mode is reliably true; null/unknown does not fail open.
			if ( true === ( $gateway['test_mode'] ?? null ) ) {
				$in_test[] = (string) ( $gateway['id'] ?? '' );
			}
		}
		if ( empty( $in_test ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Live customers may not be charged while a gateway reports test/sandbox mode.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: %s: gateway ids */
					__( 'Gateways in test mode: %s', 'store-maintenance-checklist' ),
					implode( ', ', $in_test )
				),
				'count'   => count( $in_test ),
				'samples' => $in_test,
			),
			__( 'Payments', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=checkout'
		);
	}
}
