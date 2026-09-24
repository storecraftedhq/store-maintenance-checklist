<?php
/**
 * Security tests for CSV formula neutralisation (F-1).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * CSV cells must not start with spreadsheet formula triggers.
 */
final class STMC_Security_Csv_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * Ignore reason that is a spreadsheet formula exports with a leading apostrophe.
	 */
	public function test_ignore_reason_hyperlink_formula_is_prefixed(): void {
		$row = $this->export_row(
			array(
				'ignore_reason' => '=HYPERLINK("https://x.test","y")',
			)
		);

		$this->assertStringStartsWith(
			"'=",
			$row['ignore_reason'],
			'Formula ignore reasons must be neutralised before spreadsheet open.'
		);
		$this->assertSame(
			'=HYPERLINK("https://x.test","y")',
			substr( $row['ignore_reason'], 1 )
		);
	}

	/**
	 * Each OWASP CSV formula trigger at cell start is prefixed.
	 *
	 * @dataProvider formula_trigger_provider
	 *
	 * @param string $trigger Leading character that must be neutralised.
	 */
	public function test_formula_trigger_chars_at_cell_start_are_prefixed( string $trigger ): void {
		$row = $this->export_row(
			array(
				'ignore_reason' => $trigger . 'payload',
			)
		);

		$this->assertStringStartsWith( "'" . $trigger, $row['ignore_reason'] );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function formula_trigger_provider(): array {
		return array(
			'equals'          => array( '=' ),
			'plus'            => array( '+' ),
			'minus'           => array( '-' ),
			'at'              => array( '@' ),
			'tab'             => array( "\t" ),
			'carriage_return' => array( "\r" ),
		);
	}

	/**
	 * A trigger character inside a cell (not at the start) is left unchanged.
	 */
	public function test_trigger_char_inside_cell_is_unchanged(): void {
		$row = $this->export_row(
			array(
				'ignore_reason' => 'a=b',
			)
		);

		$this->assertSame( 'a=b', $row['ignore_reason'] );
	}

	/**
	 * Evidence whose summary starts with store-controlled text that looks like a formula is prefixed.
	 */
	public function test_evidence_summary_starting_with_store_title_formula_is_prefixed(): void {
		$row = $this->export_row(
			array(
				'evidence' => array(
					'summary' => '=Privacy Policy is unpublished',
				),
			)
		);

		$this->assertStringStartsWith( "'=", $row['evidence'] );
		$this->assertSame( '=Privacy Policy is unpublished', substr( $row['evidence'], 1 ) );
	}

	/**
	 * Formula prefix still round-trips under RFC 4180 for quotes, commas, and newlines.
	 */
	public function test_formula_prefix_preserves_rfc4180_special_chars(): void {
		$cases = array(
			'comma'   => array(
				'raw'      => '=foo,bar',
				'expected' => "'=foo,bar",
			),
			'quotes'  => array(
				'raw'      => '=say "hi"',
				'expected' => "'=say \"hi\"",
			),
			'newline' => array(
				'raw'      => "=line1\nline2",
				'expected' => "'=line1\nline2",
			),
		);

		foreach ( $cases as $label => $case ) {
			$row = $this->export_row(
				array(
					'ignore_reason' => $case['raw'],
				)
			);
			$this->assertSame(
				$case['expected'],
				$row['ignore_reason'],
				"RFC 4180 round-trip failed for {$label}"
			);
		}
	}

	/**
	 * Export one finding and return the data row keyed by column name.
	 *
	 * @param array<string, mixed> $overrides Finding field overrides.
	 * @return array<string, string>
	 */
	private function export_row( array $overrides ): array {
		$finding = array_merge(
			array(
				'id'             => 'payments.no_gateways',
				'severity'       => 'critical',
				'area'           => 'Payments',
				'title'          => 'No gateways',
				'why'            => 'Cannot take payment',
				'evidence'       => array( 'summary' => '0 enabled' ),
				'status'         => 'ignored',
				'ignore_reason'  => '',
				'primary_action' => array( 'url' => 'https://example.test/payments' ),
				'further_tools'  => array(),
				'score_excluded' => true,
			),
			$overrides
		);

		$exporter = new STMC_Csv_Exporter();
		$csv      = $exporter->export(
			array( $finding ),
			array(
				'scan_timestamp' => '2026-09-23T12:00:00Z',
				'environment'    => 'local',
				'provisional'    => false,
			)
		);

		$rows = $this->parse_csv( $csv );
		$this->assertGreaterThanOrEqual( 2, count( $rows ) );

		$header = $rows[0];
		$cells  = $rows[1];
		$this->assertSame( count( $header ), count( $cells ) );

		$row = array();
		foreach ( $header as $index => $name ) {
			$row[ (string) $name ] = (string) $cells[ $index ];
		}

		return $row;
	}

	/**
	 * Minimal RFC 4180 parser (handles quoted newlines).
	 *
	 * @param string $csv CSV body.
	 * @return array<int, array<int, string>>
	 */
	private function parse_csv( string $csv ): array {
		$rows      = array();
		$row       = array();
		$field     = '';
		$in_quotes = false;
		$length    = strlen( $csv );
		$i         = 0;

		while ( $i < $length ) {
			$ch = $csv[ $i ];

			if ( $in_quotes ) {
				if ( '"' === $ch ) {
					if ( $i + 1 < $length && '"' === $csv[ $i + 1 ] ) {
						$field .= '"';
						$i     += 2;
						continue;
					}
					$in_quotes = false;
					++$i;
					continue;
				}
				$field .= $ch;
				++$i;
				continue;
			}

			if ( '"' === $ch ) {
				$in_quotes = true;
				++$i;
				continue;
			}

			if ( ',' === $ch ) {
				$row[] = $field;
				$field = '';
				++$i;
				continue;
			}

			if ( "\n" === $ch ) {
				$row[]  = $field;
				$rows[] = $row;
				$row    = array();
				$field  = '';
				++$i;
				continue;
			}

			if ( "\r" === $ch ) {
				++$i;
				continue;
			}

			$field .= $ch;
			++$i;
		}

		if ( '' !== $field || array() !== $row ) {
			$row[]  = $field;
			$rows[] = $row;
		}

		return $rows;
	}
}
