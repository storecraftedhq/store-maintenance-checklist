<?php
/**
 * STMC_Check_Email_Weak_From_Address check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

final class STMC_Check_Email_Weak_From_Address extends STMC_Check_Base {

	public function id(): string {
		return 'email.weak_from_address';
	}

	public function area(): string {
		return 'email';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'info';
	}

	public function title(): string {
		return __( 'Order emails may use a weak From address', 'store-maintenance-checklist-for-woocommerce' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$from = strtolower( trim( $snapshot->email_from ) );
		$weak = ( 0 === strpos( $from, 'wordpress@' ) )
			|| false !== strpos( $from, '@localhost' )
			|| 'wordpress@' === substr( $from, 0, 11 );

		if ( ! $weak ) {
			return $this->pass();
		}
		return $this->open(
			$snapshot,
			__( 'Addresses like wordpress@ often land in spam.', 'store-maintenance-checklist-for-woocommerce' ),
			array(
				'summary' => sprintf(
					/* translators: %s: from address */
					__( 'Current From address: %s', 'store-maintenance-checklist-for-woocommerce' ),
					$snapshot->email_from
				),
				'count'   => 1,
			),
			__( 'Emails', 'store-maintenance-checklist-for-woocommerce' ),
			'admin.php?page=wc-settings&tab=email'
		);
	}
}
