<?php
/**
 * History change markers vs previous scan (check matrix §8).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Annotates findings with new / resolved / unchanged / worsened markers.
 */
final class STMC_History_Diff {

	/**
	 * Severity rank for worsened detection.
	 */
	private const RANK = array(
		'info'     => 1,
		'warning'  => 2,
		'critical' => 3,
	);

	/**
	 * Annotate current findings with change markers vs previous.
	 *
	 * @param array<int, array<string, mixed>> $current  Current findings (open + passed + ignored).
	 * @param array<int, array<string, mixed>> $previous Previous findings from last terminal scan.
	 * @return array<int, array<string, mixed>>
	 */
	public function annotate( array $current, array $previous ): array {
		if ( empty( $previous ) ) {
			foreach ( $current as $i => $finding ) {
				$current[ $i ]['change'] = null;
			}
			return $current;
		}

		$prev_active = $this->active_severity_map( $previous );

		foreach ( $current as $i => $finding ) {
			$id     = (string) ( $finding['id'] ?? '' );
			$status = (string) ( $finding['status'] ?? '' );
			$sev    = $this->severity( $finding );

			if ( $this->is_active( $status ) ) {
				if ( ! isset( $prev_active[ $id ] ) ) {
					$current[ $i ]['change'] = 'new';
				} elseif ( $this->rank( $sev ) > $this->rank( $prev_active[ $id ] ) ) {
					$current[ $i ]['change'] = 'worsened';
				} else {
					$current[ $i ]['change'] = 'unchanged';
				}
				continue;
			}

			if ( isset( $prev_active[ $id ] ) ) {
				$current[ $i ]['change'] = 'resolved';
			} else {
				$current[ $i ]['change'] = null;
			}
		}

		return $current;
	}

	/**
	 * Aggregate change counts for history rows.
	 *
	 * @param array<int, array<string, mixed>> $annotated Annotated current findings.
	 * @param array<int, array<string, mixed>> $previous  Previous findings.
	 * @return array{new: int, resolved: int, unchanged: int, worsened: int}
	 */
	public function counts( array $annotated, array $previous ): array {
		$counts = array(
			'new'       => 0,
			'resolved'  => 0,
			'unchanged' => 0,
			'worsened'  => 0,
		);

		if ( empty( $previous ) ) {
			return $counts;
		}

		$marked_resolved = array();

		foreach ( $annotated as $finding ) {
			$change = $finding['change'] ?? null;
			if ( ! is_string( $change ) || ! isset( $counts[ $change ] ) ) {
				continue;
			}
			++$counts[ $change ];
			if ( 'resolved' === $change ) {
				$marked_resolved[ (string) ( $finding['id'] ?? '' ) ] = true;
			}
		}

		$curr_active = $this->active_severity_map( $annotated );
		foreach ( $this->active_severity_map( $previous ) as $id => $_sev ) {
			if ( isset( $curr_active[ $id ] ) || isset( $marked_resolved[ $id ] ) ) {
				continue;
			}
			++$counts['resolved'];
		}

		return $counts;
	}

	/**
	 * @param array<int, array<string, mixed>> $findings Findings.
	 * @return array<string, string> check_id => severity
	 */
	private function active_severity_map( array $findings ): array {
		$map = array();
		foreach ( $findings as $finding ) {
			$status = (string) ( $finding['status'] ?? '' );
			if ( ! $this->is_active( $status ) ) {
				continue;
			}
			$id = (string) ( $finding['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}
			$map[ $id ] = $this->severity( $finding );
		}
		return $map;
	}

	/**
	 * @param string $status Finding status.
	 */
	private function is_active( string $status ): bool {
		return in_array( $status, array( 'open', 'ignored' ), true );
	}

	/**
	 * @param array<string, mixed> $finding Finding.
	 */
	private function severity( array $finding ): string {
		return (string) ( $finding['display_severity'] ?? $finding['severity'] ?? 'info' );
	}

	/**
	 * @param string $severity Severity.
	 */
	private function rank( string $severity ): int {
		return self::RANK[ $severity ] ?? 0;
	}
}
