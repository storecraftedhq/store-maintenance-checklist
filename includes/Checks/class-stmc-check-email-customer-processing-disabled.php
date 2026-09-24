<?php
/**
 * STMC_Check_Email_Customer_Processing_Disabled check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Email_Customer_Processing_Disabled extends STMC_Check_Base {

	public function id(): string {
		return 'email.customer_processing_disabled';
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
		return __( 'Customer “Processing order” email disabled', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$email = $snapshot->emails['customer_processing_order'] ?? array( 'enabled' => true );
		if ( ! empty( $email['enabled'] ) ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Buyers may get no order confirmation email.', 'store-maintenance-checklist' ),
			array(
				'summary' => __( 'customer_processing_order enabled=false', 'store-maintenance-checklist' ),
				'count'   => 1,
			),
			__( 'Emails', 'store-maintenance-checklist' ),
			'admin.php?page=wc-settings&tab=email'
		);
	}
}
