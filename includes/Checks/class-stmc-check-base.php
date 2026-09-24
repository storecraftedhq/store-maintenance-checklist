<?php
/**
 * Shared helpers for STMC_Check implementations.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Abstract check with common metadata helpers.
 */
abstract class STMC_Check_Base implements STMC_Check {

	/**
	 * {@inheritdoc}
	 */
	abstract public function id(): string;

	/**
	 * {@inheritdoc}
	 */
	abstract public function area(): string;

	/**
	 * {@inheritdoc}
	 */
	abstract public function runner(): string;

	/**
	 * {@inheritdoc}
	 */
	abstract public function severity(): string;

	/**
	 * {@inheritdoc}
	 */
	abstract public function title(): string;

	/**
	 * {@inheritdoc}
	 */
	abstract public function evaluate( STMC_Check_Snapshot $snapshot ): array;

	/**
	 * Open finding with primary action.
	 *
	 * @param STMC_Check_Snapshot  $snapshot Snapshot.
	 * @param string               $why      Why copy.
	 * @param array<string, mixed> $evidence Evidence.
	 * @param string               $label    Action label.
	 * @param string               $path     Admin path after admin_url, or absolute http(s) URL.
	 * @param array<string, mixed> $extra    Extra Finding fields.
	 * @return array<string, mixed>
	 */
	protected function open( STMC_Check_Snapshot $snapshot, string $why, array $evidence, string $label, string $path, array $extra = array() ): array {
		$external                = (bool) preg_match( '#^https?://#i', $path );
		$url                     = $external
			? $path
			: rtrim( $snapshot->admin_url, '/' ) . '/' . ltrim( $path, '/' );
		$extra['primary_action'] = array(
			'label'    => $label,
			'url'      => $url,
			'external' => $external,
		);
		return STMC_Finding::from_check( $this, 'open', $why, $evidence, $extra );
	}

	/**
	 * Passed finding.
	 *
	 * @return array<string, mixed>
	 */
	protected function pass(): array {
		return STMC_Finding::passed( $this );
	}

	/**
	 * Whether a price string is missing/invalid.
	 *
	 * @param string $price Price.
	 */
	public static function price_missing( string $price ): bool {
		$price = trim( $price );
		if ( '' === $price ) {
			return true;
		}
		return ! is_numeric( $price );
	}

	/**
	 * Whether a product description is empty after stripping markup/whitespace.
	 *
	 * @param string $html Description HTML or plain text.
	 */
	public static function description_missing( string $html ): bool {
		if ( function_exists( 'wp_strip_all_tags' ) ) {
			$text = wp_strip_all_tags( $html );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- unit-test fallback when WP is unavailable.
			$text = strip_tags( $html );
		}
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/[\x{00A0}\x{200B}\x{FEFF}]/u', '', $text );
		return '' === trim( (string) $text );
	}

	/**
	 * Build REST sample objects from order IDs.
	 *
	 * @param array<int, int|string> $ids       Order IDs.
	 * @param string                 $admin_url Admin base URL.
	 * @return array<int, array{label: string, url: string}>
	 */
	protected function order_id_samples( array $ids, string $admin_url ): array {
		$samples = array();
		$base    = rtrim( $admin_url, '/' );

		foreach ( array_slice( $ids, 0, 3 ) as $id ) {
			$order_id = (int) $id;
			if ( $order_id <= 0 ) {
				continue;
			}

			$url = $base . '/admin.php?page=wc-orders&action=edit&id=' . $order_id;
			if ( function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
				if ( $order && is_object( $order ) && method_exists( $order, 'get_edit_order_url' ) ) {
					$url = (string) $order->get_edit_order_url();
				}
			}

			$samples[] = array(
				'label' => '#' . $order_id,
				'url'   => $url,
			);
		}

		return $samples;
	}
}
