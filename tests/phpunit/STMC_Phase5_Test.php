<?php
/**
 * Acceptance tests for Phase 5: softening, history markers, CSV, uninstall.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 5 behaviours from the roadmap.
 */
final class STMC_Phase5_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * Constant STMC_FORCE_PRODUCTION_SEVERITY wins over toggle and local env.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_force_production_constant_wins_over_toggle(): void {
		define( 'STMC_FORCE_PRODUCTION_SEVERITY', true );

		$GLOBALS['stmc_test_env'] = 'local';
		$options                  = new STMC_Options();
		$options->update_settings( array( 'force_production_severity' => false ) );

		$env = new STMC_Environment( $options );
		$this->assertTrue( $env->treat_as_production() );
	}

	/**
	 * On local env without force, critical display severity becomes warning.
	 */
	public function test_critical_softens_to_warning_on_local(): void {
		$GLOBALS['stmc_test_env'] = 'local';
		$options                  = new STMC_Options();
		$options->update_settings( array( 'force_production_severity' => false ) );

		$env = new STMC_Environment( $options );
		$this->assertFalse( $env->treat_as_production() );

		$softener = new STMC_Softening();
		$out      = $softener->apply(
			array(
				array(
					'id'       => 'payments.no_gateways',
					'severity' => 'critical',
					'status'   => 'open',
				),
				array(
					'id'       => 'email.wp_mail_broken',
					'severity' => 'warning',
					'status'   => 'open',
				),
			),
			$env->treat_as_production()
		);

		$this->assertSame( 'warning', $out[0]['severity'] );
		$this->assertSame( 'warning', $out[0]['display_severity'] );
		$this->assertSame( 'info', $out[1]['severity'] );
		$this->assertSame( 'info', $out[1]['display_severity'] );

		$score   = new STMC_Score();
		$summary = $score->summarize( $out );
		$this->assertSame( 89, $summary['score'] ); // 100 - 8 - 3.
		$this->assertSame( 1, $summary['warning'] );
		$this->assertSame( 1, $summary['info'] );
		$this->assertSame( 0, $summary['critical'] );
	}

	/**
	 * HTTPS check is passed when not treating as production (via Environment).
	 */
	public function test_https_check_skipped_when_not_treat_as_production(): void {
		$GLOBALS['stmc_test_env'] = 'local';
		$options                  = new STMC_Options();
		$options->update_settings( array( 'force_production_severity' => false ) );

		$env = new STMC_Environment( $options );
		$this->assertFalse( $env->treat_as_production() );

		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'treat_as_production' => $env->treat_as_production(),
					'is_ssl'              => false,
					'home_url'            => 'http://example.com',
					'debug_display'       => true,
				)
			)
		);

		$by_id = array();
		foreach ( $findings as $finding ) {
			$by_id[ (string) $finding['id'] ] = $finding;
		}

		$this->assertSame( 'passed', $by_id['environment.https_off_production']['status'] );
		$this->assertSame( 'passed', $by_id['environment.debug_display_production']['status'] );
	}

	/**
	 * History diff marks worsened when severity rank increases for same check.
	 */
	public function test_history_marks_worsened_severity(): void {
		$previous = array(
			array(
				'id'       => 'payments.no_gateways',
				'severity' => 'warning',
				'status'   => 'open',
			),
			array(
				'id'       => 'email.wp_mail_broken',
				'severity' => 'warning',
				'status'   => 'open',
			),
		);

		$current = array(
			array(
				'id'       => 'payments.no_gateways',
				'severity' => 'critical',
				'status'   => 'open',
			),
			array(
				'id'       => 'catalog.missing_price',
				'severity' => 'critical',
				'status'   => 'open',
			),
			array(
				'id'       => 'email.wp_mail_broken',
				'severity' => 'passed',
				'status'   => 'passed',
			),
		);

		$diff      = new STMC_History_Diff();
		$annotated = $diff->annotate( $current, $previous );
		$changes   = $diff->counts( $annotated, $previous );

		$by_id = array();
		foreach ( $annotated as $finding ) {
			$by_id[ (string) $finding['id'] ] = $finding;
		}

		$this->assertSame( 'worsened', $by_id['payments.no_gateways']['change'] );
		$this->assertSame( 'new', $by_id['catalog.missing_price']['change'] );
		$this->assertSame( 'resolved', $by_id['email.wp_mail_broken']['change'] );
		$this->assertSame( 1, $changes['worsened'] );
		$this->assertSame( 1, $changes['new'] );
		$this->assertSame( 1, $changes['resolved'] );
	}

	/**
	 * CSV includes open + ignored, excludes passed; columns match PRD §4.6 #27.
	 */
	public function test_csv_excludes_passed_includes_ignored(): void {
		$exporter = new STMC_Csv_Exporter();
		$csv      = $exporter->export(
			array(
				array(
					'id'             => 'payments.no_gateways',
					'severity'       => 'critical',
					'area'           => 'Payments',
					'title'          => 'No gateways',
					'why'            => 'Cannot take payment',
					'evidence'       => array( 'summary' => '0 enabled' ),
					'status'         => 'open',
					'ignore_reason'  => null,
					'primary_action' => array( 'url' => 'https://example.test/payments' ),
					'further_tools'  => array(),
					'score_excluded' => false,
				),
				array(
					'id'             => 'email.wp_mail_broken',
					'severity'       => 'warning',
					'area'           => 'Email',
					'title'          => 'Mail broken',
					'why'            => 'Mail fails',
					'evidence'       => array( 'summary' => 'wp_mail false' ),
					'status'         => 'ignored',
					'ignore_reason'  => 'Expected on staging',
					'primary_action' => array( 'url' => '' ),
					'further_tools'  => array(
						array(
							'label' => 'Tool A',
							'url'   => 'https://storecrafted.com/a',
						),
					),
					'score_excluded' => true,
				),
				array(
					'id'       => 'shipping.no_methods_configured',
					'severity' => 'passed',
					'status'   => 'passed',
					'title'    => 'OK',
				),
			),
			array(
				'scan_timestamp' => '2026-09-23T12:00:00Z',
				'environment'    => 'local',
				'provisional'    => false,
			)
		);

		$lines = array_values( array_filter( explode( "\n", trim( $csv ) ) ) );
		$this->assertSame( implode( ',', STMC_Csv_Exporter::COLUMNS ), $lines[0] );
		$this->assertCount( 3, $lines ); // header + open + ignored.

		$this->assertStringContainsString( 'payments.no_gateways', $lines[1] );
		$this->assertStringContainsString( 'open', $lines[1] );
		$this->assertStringContainsString( 'email.wp_mail_broken', $lines[2] );
		$this->assertStringContainsString( 'ignored', $lines[2] );
		$this->assertStringContainsString( 'Expected on staging', $lines[2] );
		$this->assertStringNotContainsString( 'shipping.no_methods_configured', $csv );
	}

	/**
	 * Uninstall deletes stmc_* options/transients and clears AS group stmc.
	 */
	public function test_uninstall_removes_stmc_options_and_as_actions(): void {
		update_option( 'stmc_settings', array( 'force_production_severity' => true ) );
		update_option( 'stmc_ignores', array( 'x' => array( 'reason' => 'y' ) ) );
		update_option( 'stmc_latest_results', array( 'summary' => array() ) );
		update_option( 'stmc_history', array( array( 'id' => 'h1' ) ) );
		update_option( 'stmc_scan_state', array( 'state' => 'running' ) );
		update_option( '_transient_stmc_scan_lock', '1' );
		update_option( '_transient_timeout_stmc_scan_lock', time() + 60 );
		update_option( 'woocommerce_currency', 'USD' );

		as_enqueue_async_action( 'stmc/scan/catalog_batch', array( 'scan_id' => 'abc' ), 'stmc' );
		as_enqueue_async_action( 'stmc/scan/finalize', array( 'scan_id' => 'abc' ), 'stmc' );
		as_enqueue_async_action( 'other_plugin_hook', array(), 'other' );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		require STMC_PLUGIN_DIR . 'uninstall.php';

		$this->assertFalse( get_option( 'stmc_settings', false ) );
		$this->assertFalse( get_option( 'stmc_ignores', false ) );
		$this->assertFalse( get_option( 'stmc_latest_results', false ) );
		$this->assertFalse( get_option( 'stmc_history', false ) );
		$this->assertFalse( get_option( 'stmc_scan_state', false ) );
		$this->assertFalse( get_option( '_transient_stmc_scan_lock', false ) );
		$this->assertFalse( get_option( '_transient_timeout_stmc_scan_lock', false ) );
		$this->assertSame( 'USD', get_option( 'woocommerce_currency' ) );

		$remaining = $GLOBALS['stmc_test_as'];
		$this->assertCount( 1, $remaining );
		$this->assertSame( 'other_plugin_hook', $remaining[0]['hook'] );
	}
}
