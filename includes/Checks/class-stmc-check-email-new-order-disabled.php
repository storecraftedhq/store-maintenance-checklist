<?php
/**
 * STMC_Check_Email_New_Order_Disabled check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Email_New_Order_Disabled extends STMC_Check_Base {

	public function id(): string {
		return 'email.new_order_disabled';
	}

	public function area(): string {
		return 'email';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Admin “New order” email disabled', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$email = $snapshot->emails['new_order'] ?? array( 'enabled' => true );
		if ( ! empty( $email['enabled'] ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Merchants may miss new orders.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'new_order enabled=false', 'store-maintenance-checklist' ),
				'count'   => 1,
			),
			__( 'Emails', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=email'
		);
	}
}
