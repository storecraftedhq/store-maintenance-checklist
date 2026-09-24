<?php
/**
 * CSV export of open + ignored findings.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Builds CSV per PRD §4.6 #27.
 */
final class STMC_Csv_Exporter {

	/**
	 * Column headers in locked order.
	 */
	public const COLUMNS = array(
		'check_id',
		'severity',
		'area',
		'title',
		'why',
		'evidence',
		'status',
		'ignore_reason',
		'primary_action_url',
		'further_tools',
		'score_excluded',
		'scan_timestamp',
		'environment',
		'provisional',
	);

	/**
	 * @param array<int, array<string, mixed>> $findings Findings (open + ignored + passed).
	 * @param array<string, mixed>             $meta     scan_timestamp, environment, provisional.
	 * @return string CSV body including header.
	 */
	public function export( array $findings, array $meta = array() ): string {
		$rows   = array();
		$rows[] = self::COLUMNS;

		foreach ( $findings as $finding ) {
			$status = (string) ( $finding['status'] ?? '' );
			if ( 'passed' === $status ) {
				continue;
			}
			if ( ! in_array( $status, array( 'open', 'ignored' ), true ) ) {
				continue;
			}

			$evidence = '';
			if ( isset( $finding['evidence']['summary'] ) ) {
				$evidence = (string) $finding['evidence']['summary'];
			} elseif ( isset( $finding['evidence'] ) && is_string( $finding['evidence'] ) ) {
				$evidence = $finding['evidence'];
			}

			$tools = array();
			if ( ! empty( $finding['further_tools'] ) && is_array( $finding['further_tools'] ) ) {
				foreach ( $finding['further_tools'] as $tool ) {
					if ( is_array( $tool ) ) {
						$tools[] = (string) ( $tool['label'] ?? $tool['url'] ?? '' );
					} else {
						$tools[] = (string) $tool;
					}
				}
			}

			$primary_url = '';
			if ( isset( $finding['primary_action']['url'] ) ) {
				$primary_url = (string) $finding['primary_action']['url'];
			}

			$rows[] = array(
				(string) ( $finding['id'] ?? '' ),
				(string) ( $finding['severity'] ?? '' ),
				(string) ( $finding['area'] ?? '' ),
				(string) ( $finding['title'] ?? '' ),
				(string) ( $finding['why'] ?? '' ),
				$evidence,
				$status,
				(string) ( $finding['ignore_reason'] ?? '' ),
				$primary_url,
				implode( '|', array_filter( $tools ) ),
				! empty( $finding['score_excluded'] ) ? 'yes' : 'no',
				(string) ( $meta['scan_timestamp'] ?? '' ),
				(string) ( $meta['environment'] ?? '' ),
				! empty( $meta['provisional'] ) ? 'yes' : 'no',
			);
		}

		$out = '';
		foreach ( $rows as $row ) {
			$out .= $this->csv_line( $row );
		}
		return $out;
	}

	/**
	 * @param array<int, string> $fields Fields.
	 * @return string
	 */
	private function csv_line( array $fields ): string {
		$escaped = array();
		foreach ( $fields as $field ) {
			$field = $this->neutralise_formula( (string) $field );
			$field = str_replace( '"', '""', $field );
			if ( preg_match( '/[",\r\n]/', $field ) ) {
				$field = '"' . $field . '"';
			}
			$escaped[] = $field;
		}
		return implode( ',', $escaped ) . "\n";
	}

	/**
	 * Prefix cells that would be treated as spreadsheet formulas (CWE-1236).
	 *
	 * @param string $field Cell value.
	 * @return string
	 */
	private function neutralise_formula( string $field ): string {
		if ( '' === $field ) {
			return $field;
		}

		$first = $field[0];
		if ( in_array( $first, array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $field;
		}

		return $field;
	}
}
