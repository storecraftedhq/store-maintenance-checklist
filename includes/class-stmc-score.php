<?php
/**
 * Score and metric summary service.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Computes checklist score from findings (contract §6).
 */
final class STMC_Score {

	/**
	 * Check IDs that never add a penalty.
	 */
	public const UNSCORED_IDS = array(
		'environment.site_health_linkout',
		'environment.wc_status_linkout',
	);

	/**
	 * Penalties by display severity.
	 */
	private const PENALTIES = array(
		'passed'   => 0,
		'info'     => 3,
		'warning'  => 8,
		'critical' => 15,
	);

	/**
	 * Build score summary from findings.
	 *
	 * @param array<int, array<string, mixed>> $findings Findings from the scan.
	 * @param array<string, mixed>             $ignores  Ignore map (check_id => meta).
	 * @param bool                             $provisional Provisional / partial catalog flag.
	 * @return array<string, mixed>
	 */
	public function summarize( array $findings, array $ignores = array(), bool $provisional = false ): array {
		$penalty  = 0;
		$open     = 0;
		$critical = 0;
		$warning  = 0;
		$info     = 0;
		$passed   = 0;
		$ignored  = 0;

		foreach ( $findings as $finding ) {
			$id     = (string) ( $finding['id'] ?? '' );
			$status = (string) ( $finding['status'] ?? 'open' );

			if ( 'ignored' === $status || isset( $ignores[ $id ] ) ) {
				++$ignored;
				continue;
			}

			$display = (string) ( $finding['display_severity'] ?? $finding['severity'] ?? 'passed' );

			if ( 'passed' === $status || 'passed' === $display ) {
				++$passed;
				continue;
			}

			$score_excluded = ! empty( $finding['score_excluded'] )
				|| in_array( $id, self::UNSCORED_IDS, true );

			if ( ! $score_excluded ) {
				$penalty += self::PENALTIES[ $display ] ?? 0;
			}

			if ( 'open' === $status && in_array( $display, array( 'critical', 'warning', 'info' ), true ) ) {
				++$open;
				if ( 'critical' === $display ) {
					++$critical;
				} elseif ( 'warning' === $display ) {
					++$warning;
				} else {
					++$info;
				}
			}
		}

		return array(
			'score'       => max( 0, 100 - $penalty ),
			'provisional' => $provisional,
			'open'        => $open,
			'critical'    => $critical,
			'warning'     => $warning,
			'info'        => $info,
			'passed'      => $passed,
			'ignored'     => $ignored,
		);
	}
}
