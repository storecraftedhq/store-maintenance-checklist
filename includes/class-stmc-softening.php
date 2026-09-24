<?php
/**
 * Non-production severity softening (check matrix §4).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Downgrades severities when not treating the site as production.
 */
final class STMC_Softening {

	/**
	 * Map a production severity to its softened display severity.
	 *
	 * @param string $severity Production severity.
	 */
	public static function map( string $severity ): string {
		switch ( $severity ) {
			case 'critical':
				return 'warning';
			case 'warning':
				return 'info';
			default:
				return $severity;
		}
	}

	/**
	 * Apply softening (or leave production severities) and set display_severity.
	 *
	 * @param array<int, array<string, mixed>> $findings            Findings.
	 * @param bool                             $treat_as_production Whether production rules apply.
	 * @return array<int, array<string, mixed>>
	 */
	public function apply( array $findings, bool $treat_as_production ): array {
		foreach ( $findings as $i => $finding ) {
			$raw = (string) ( $finding['severity'] ?? 'passed' );

			if ( $treat_as_production || 'passed' === $raw ) {
				$findings[ $i ]['display_severity'] = $raw;
				continue;
			}

			$soft                               = self::map( $raw );
			$findings[ $i ]['severity_raw']     = $raw;
			$findings[ $i ]['severity']         = $soft;
			$findings[ $i ]['display_severity'] = $soft;
		}

		return $findings;
	}
}
