<?php
/**
 * STMC_Check_Catalog_Policy_Pages_Unpublished check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Warns when Privacy or Refunds policy pages are assigned but not published.
 */
final class STMC_Check_Catalog_Policy_Pages_Unpublished extends STMC_Check_Base {

	public function id(): string {
		return 'catalog.policy_pages_unpublished';
	}

	public function area(): string {
		return 'catalog';
	}

	public function runner(): string {
		return 'sync';
	}

	public function severity(): string {
		return 'warning';
	}

	public function title(): string {
		return __( 'Privacy or refunds policy page not published', 'store-maintenance-checklist' );
	}

	public function evaluate( STMC_Check_Snapshot $snapshot ): array {
		$labels  = array(
			'privacy' => __( 'Privacy Policy', 'store-maintenance-checklist' ),
			'refunds' => __( 'Refund and Returns Policy', 'store-maintenance-checklist' ),
		);
		$bad     = array();
		$samples = array();
		$base    = rtrim( $snapshot->admin_url, '/' );

		foreach ( $labels as $key => $default_label ) {
			$page   = is_array( $snapshot->policy_pages[ $key ] ?? null ) ? $snapshot->policy_pages[ $key ] : array();
			$id     = (int) ( $page['id'] ?? 0 );
			$status = (string) ( $page['status'] ?? '' );
			if ( $id <= 0 || 'publish' === $status ) {
				continue;
			}
			$title     = trim( (string) ( $page['title'] ?? '' ) );
			$label     = '' !== $title ? $title : $default_label;
			$bad[]     = $label;
			$samples[] = array(
				'label' => '#' . $id,
				'url'   => $base . '/post.php?post=' . $id . '&action=edit',
			);
		}

		if ( empty( $bad ) ) {
			return $this->pass();
		}

		return $this->open(
			$snapshot,
			__( 'Payment gateways and customers expect Privacy and Refunds policy pages to be publicly published.', 'store-maintenance-checklist' ),
			array(
				'summary' => sprintf(
					/* translators: %s: comma-separated page titles */
					__( 'Not published: %s', 'store-maintenance-checklist' ),
					implode( ', ', $bad )
				),
				'count'   => count( $bad ),
				'samples' => $samples,
			),
			__( 'Pages', 'store-maintenance-checklist' ),
			'edit.php?post_type=page'
		);
	}
}
