<?php
/**
 * Acceptance tests for Phase 2 scoring, persistence, scan FSM, and REST auth.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 2 behaviours from the roadmap.
 */
final class STMC_Phase2_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * Penalties: info 3, warning 8, critical 15; floor at zero.
	 */
	public function test_score_applies_penalties_and_floors_at_zero(): void {
		$score = new STMC_Score();

		$summary = $score->summarize(
			array(
				$this->finding( 'a.critical', 'critical', 'open' ),
				$this->finding( 'b.warning', 'warning', 'open' ),
				$this->finding( 'c.info', 'info', 'open' ),
				$this->finding( 'd.passed', 'passed', 'passed' ),
			)
		);

		$this->assertSame( 74, $summary['score'] ); // 100 - 15 - 8 - 3.
		$this->assertSame( 3, $summary['open'] );
		$this->assertSame( 1, $summary['critical'] );
		$this->assertSame( 1, $summary['warning'] );
		$this->assertSame( 1, $summary['info'] );
		$this->assertSame( 1, $summary['passed'] );

		$floor = $score->summarize(
			array(
				$this->finding( 'c1', 'critical', 'open' ),
				$this->finding( 'c2', 'critical', 'open' ),
				$this->finding( 'c3', 'critical', 'open' ),
				$this->finding( 'c4', 'critical', 'open' ),
				$this->finding( 'c5', 'critical', 'open' ),
				$this->finding( 'c6', 'critical', 'open' ),
				$this->finding( 'c7', 'critical', 'open' ),
			)
		);
		$this->assertSame( 0, $floor['score'] ); // max(0, 100 - 105).
	}

	/**
	 * Ignored checks add no penalty and are excluded from open counts.
	 */
	public function test_ignored_check_excluded_from_score(): void {
		$options = new STMC_Options();
		$options->ignore_check( 'payments.gateway_test_mode', 'Expected sandbox' );

		$score   = new STMC_Score();
		$summary = $score->summarize(
			array(
				$this->finding( 'payments.gateway_test_mode', 'critical', 'ignored', 'Expected sandbox' ),
				$this->finding( 'email.wp_mail_broken', 'warning', 'open' ),
			),
			$options->get_ignores()
		);

		$this->assertSame( 92, $summary['score'] ); // 100 - 8 only.
		$this->assertSame( 1, $summary['open'] );
		$this->assertSame( 1, $summary['ignored'] );
		$this->assertSame( 0, $summary['critical'] );
	}

	/**
	 * Link-out environment checks never penalize.
	 */
	public function test_linkout_checks_never_penalize(): void {
		$score   = new STMC_Score();
		$summary = $score->summarize(
			array(
				$this->finding( 'environment.site_health_linkout', 'info', 'open', null, true ),
				$this->finding( 'environment.wc_status_linkout', 'info', 'open', null, true ),
				$this->finding( 'catalog.missing_price', 'warning', 'open' ),
			)
		);

		$this->assertSame( 92, $summary['score'] ); // 100 - 8; link-outs unscored.
		$this->assertSame( 3, $summary['open'] );
		$this->assertSame( 2, $summary['info'] );
	}

	/**
	 * Display severity (after softening) drives penalties — Phase 5 hook; Phase 2 stub.
	 */
	public function test_softened_severity_used_when_scoring(): void {
		$score   = new STMC_Score();
		$summary = $score->summarize(
			array(
				array_merge(
					$this->finding( 'env.https', 'critical', 'open' ),
					array( 'display_severity' => 'warning' )
				),
			)
		);

		$this->assertSame( 92, $summary['score'] ); // Uses warning (8), not critical (15).
	}

	/**
	 * Starting a second scan while running returns 409.
	 */
	public function test_start_scan_conflict_returns_409(): void {
		$engine = new STMC_Scan_Engine( new STMC_Options(), new STMC_Score(), new STMC_Scan_Scheduler() );
		$first  = $engine->start();
		$this->assertIsArray( $first );
		$this->assertSame( 'running', $first['status']['state'] );

		$second = $engine->start();
		$this->assertInstanceOf( WP_Error::class, $second );
		$this->assertSame( 'stmc_scan_in_progress', $second->get_error_code() );
		$this->assertSame( 409, $second->get_error_data()['status'] );
	}

	/**
	 * Cancel with zero progress does not write history.
	 */
	public function test_cancel_with_no_progress_skips_history(): void {
		$options = new STMC_Options();
		$engine  = new STMC_Scan_Engine( $options, new STMC_Score(), new STMC_Scan_Scheduler() );
		$engine->start();
		$result = $engine->cancel();

		$this->assertIsArray( $result );
		$this->assertSame( 'cancelled', $result['status']['state'] );
		$this->assertSame( array(), $options->get_history() );
	}

	/**
	 * History keeps at most 10 newest-first entries.
	 */
	public function test_history_cap_drops_oldest(): void {
		$options = new STMC_Options();
		for ( $i = 1; $i <= 12; $i++ ) {
			$options->prepend_history(
				array(
					'id'        => 'hist_' . $i,
					'timestamp' => '2026-09-23T14:00:00Z',
					'score'     => $i,
				)
			);
		}

		$history = $options->get_history();
		$this->assertCount( 10, $history );
		$this->assertSame( 'hist_12', $history[0]['id'] );
		$this->assertSame( 'hist_3', $history[9]['id'] );
	}

	/**
	 * Settings clamp products/variations to contracted bounds and step.
	 */
	public function test_settings_clamps_product_bounds(): void {
		$options  = new STMC_Options();
		$settings = $options->update_settings(
			array(
				'max_products'   => 50,
				'max_variations' => 25000,
			)
		);

		$this->assertSame( 100, $settings['max_products'] );
		$this->assertSame( 20000, $settings['max_variations'] );

		$settings = $options->update_settings(
			array(
				'max_products'   => 1150,
				'max_variations' => 1150,
			)
		);
		$this->assertSame( 1200, $settings['max_products'] );
		$this->assertSame( 1200, $settings['max_variations'] );
	}

	/**
	 * REST permission callback requires manage_woocommerce.
	 */
	public function test_rest_requires_manage_woocommerce(): void {
		$GLOBALS['stmc_test_caps'] = array();
		$denied                    = STMC_Api_Permissions::check();
		$this->assertInstanceOf( WP_Error::class, $denied );
		$this->assertSame( 'stmc_forbidden', $denied->get_error_code() );
		$this->assertSame( 403, $denied->get_error_data()['status'] );

		$GLOBALS['stmc_test_caps'] = array( 'manage_woocommerce' => true );
		$this->assertTrue( STMC_Api_Permissions::check() );
	}

	/**
	 * @param string      $id             Check id.
	 * @param string      $severity       Severity.
	 * @param string      $status         Status.
	 * @param string|null $ignore_reason  Ignore reason.
	 * @param bool        $score_excluded Score excluded flag.
	 * @return array<string, mixed>
	 */
	private function finding( string $id, string $severity, string $status, ?string $ignore_reason = null, bool $score_excluded = false ): array {
		return array(
			'id'             => $id,
			'title'          => $id,
			'severity'       => $severity,
			'area'           => 'test',
			'status'         => $status,
			'why'            => '',
			'evidence'       => array( 'summary' => '' ),
			'ignore_reason'  => $ignore_reason,
			'score_excluded' => $score_excluded || 'ignored' === $status,
		);
	}
}
