<?php
/**
 * Option persistence for settings, ignores, results, history, scan state.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Reads/writes contracted stmc_* options.
 */
final class STMC_Options {

	public const OPTION_SETTINGS       = 'stmc_settings';
	public const OPTION_IGNORES        = 'stmc_ignores';
	public const OPTION_LATEST_RESULTS = 'stmc_latest_results';
	public const OPTION_HISTORY        = 'stmc_history';
	public const OPTION_SCAN_STATE     = 'stmc_scan_state';

	private const HISTORY_CAP = 10;

	/**
	 * Default settings when option missing.
	 *
	 * @return array<string, mixed>
	 */
	public function default_settings(): array {
		return array(
			'force_production_severity' => false,
			'show_further_tools'        => true,
			'max_products'              => 1000,
			'max_variations'            => 2000,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		$stored = get_option( self::OPTION_SETTINGS, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( $this->default_settings(), $stored );
	}

	/**
	 * Merge and clamp settings.
	 *
	 * @param array<string, mixed> $partial Partial settings.
	 * @return array<string, mixed>
	 */
	public function update_settings( array $partial ): array {
		$settings = $this->get_settings();

		if ( array_key_exists( 'force_production_severity', $partial ) ) {
			$settings['force_production_severity'] = (bool) $partial['force_production_severity'];
		}
		if ( array_key_exists( 'show_further_tools', $partial ) ) {
			$settings['show_further_tools'] = (bool) $partial['show_further_tools'];
		}
		if ( array_key_exists( 'max_products', $partial ) ) {
			$settings['max_products'] = $this->clamp_bound( (int) $partial['max_products'], 100, 10000 );
		}
		if ( array_key_exists( 'max_variations', $partial ) ) {
			$settings['max_variations'] = $this->clamp_bound( (int) $partial['max_variations'], 100, 20000 );
		}

		update_option( self::OPTION_SETTINGS, $settings );
		return $settings;
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public function get_ignores(): array {
		$ignores = get_option( self::OPTION_IGNORES, array() );
		return is_array( $ignores ) ? $ignores : array();
	}

	/**
	 * Persist an ignore for a check id.
	 *
	 * @param string $check_id Check id.
	 * @param string $reason   Optional reason (max 200).
	 */
	public function ignore_check( string $check_id, string $reason = '' ): void {
		$ignores = $this->get_ignores();
		$reason  = sanitize_text_field( wp_strip_all_tags( $reason ) );
		if ( mb_strlen( $reason, 'UTF-8' ) > 200 ) {
			$reason = mb_substr( $reason, 0, 200, 'UTF-8' );
		}

		$ignores[ $check_id ] = array(
			'reason'     => $reason,
			'ignored_at' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
		update_option( self::OPTION_IGNORES, $ignores );
	}

	/**
	 * Restore a previously ignored check.
	 *
	 * @param string $check_id Check id.
	 */
	public function restore_check( string $check_id ): void {
		$ignores = $this->get_ignores();
		unset( $ignores[ $check_id ] );
		update_option( self::OPTION_IGNORES, $ignores );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_latest_results(): array {
		$results = get_option( self::OPTION_LATEST_RESULTS, array() );
		return is_array( $results ) ? $results : array();
	}

	/**
	 * @param array<string, mixed> $payload Latest terminal payload.
	 */
	public function set_latest_results( array $payload ): void {
		update_option( self::OPTION_LATEST_RESULTS, $payload );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_history(): array {
		$history = get_option( self::OPTION_HISTORY, array() );
		return is_array( $history ) ? $history : array();
	}

	/**
	 * Push a history summary to the front; cap at 10.
	 *
	 * @param array<string, mixed> $item History row.
	 */
	public function prepend_history( array $item ): void {
		$history = $this->get_history();
		array_unshift( $history, $item );
		$history = array_slice( $history, 0, self::HISTORY_CAP );
		update_option( self::OPTION_HISTORY, $history );
	}

	/**
	 * Clear history summaries only.
	 */
	public function clear_history(): void {
		update_option( self::OPTION_HISTORY, array() );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_scan_state(): array {
		$state = get_option( self::OPTION_SCAN_STATE, array() );
		return is_array( $state ) ? $state : array();
	}

	/**
	 * @param array<string, mixed> $state Scan machine state.
	 */
	public function set_scan_state( array $state ): void {
		update_option( self::OPTION_SCAN_STATE, $state, false );
	}

	/**
	 * Clear active scan state.
	 */
	public function clear_scan_state(): void {
		delete_option( self::OPTION_SCAN_STATE );
	}

	/**
	 * Clamp to min/max then snap to nearest step of 100.
	 *
	 * @param int $value Raw value.
	 * @param int $min   Minimum.
	 * @param int $max   Maximum.
	 * @return int
	 */
	private function clamp_bound( int $value, int $min, int $max ): int {
		$value = (int) ( round( $value / 100 ) * 100 );
		if ( $value < $min ) {
			return $min;
		}
		if ( $value > $max ) {
			return $max;
		}
		return $value;
	}
}
