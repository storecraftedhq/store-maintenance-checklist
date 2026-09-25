<?php
/**
 * STMC_Check_Email_New_Order_Recipient_Missing check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Email_New_Order_Recipient_Missing extends STMC_Check_Base {

	/**
	 * {@inheritdoc}
	 */
	public function id(): string {
		return 'email.new_order_recipient_missing';
	}

	/**
	 * {@inheritdoc}
	 */
	public function area(): string {
		return 'email';
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
		return 'warning';
	}

	/**
	 * {@inheritdoc}
	 */
	public function title(): string {
		return __( 'New order recipient missing', 'store-maintenance-checklist-for-woocommerce' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$email     = $snapshot->emails['new_order'] ?? array();
		$recipient = trim( (string) ( $email['recipient'] ?? '' ) );
		if ( '' !== $recipient ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'New order alerts go nowhere without a recipient.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => __( 'New order recipient is empty.', 'store-maintenance-checklist-for-woocommerce' ),
				'count'   => 1,
			),
			__( 'Emails', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-settings&tab=email&section=new_order'
		);
	}
}
