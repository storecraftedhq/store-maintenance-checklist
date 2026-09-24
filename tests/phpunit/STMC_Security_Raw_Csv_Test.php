<?php
/**
 * Security tests for raw CSV REST serving (Lead C).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Raw CSV echo must only apply to the export route.
 */
final class STMC_Security_Raw_Csv_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * Another route carrying X-STMC-Raw-CSV must still be JSON-served (not raw-echoed).
	 */
	public function test_serve_raw_csv_ignores_header_on_non_export_route(): void {
		$api    = $this->make_api();
		$result = new WP_REST_Response( 'check_id,title' . "\n" . 'x,y' . "\n", 200 );
		$result->header( 'X-STMC-Raw-CSV', '1' );
		$request = $this->request_with_route( '/stmc/v1/scan' );

		ob_start();
		$served = $api->serve_raw_csv( false, $result, $request, null );
		$out    = ob_get_clean();

		$this->assertFalse( $served );
		$this->assertSame( '', $out );
	}

	/**
	 * Export route with the header is served as raw CSV.
	 */
	public function test_serve_raw_csv_echoes_body_for_export_route(): void {
		$api    = $this->make_api();
		$csv    = 'check_id,title' . "\n" . 'payments.no_gateways,No gateways' . "\n";
		$result = new WP_REST_Response( $csv, 200 );
		$result->header( 'X-STMC-Raw-CSV', '1' );
		$request = $this->request_with_route( '/stmc/v1/export/csv' );

		ob_start();
		$served = $api->serve_raw_csv( false, $result, $request, null );
		$out    = ob_get_clean();

		$this->assertTrue( $served );
		$this->assertSame( $csv, $out );
	}

	/**
	 * @return STMC_Api_Rest
	 */
	private function make_api(): STMC_Api_Rest {
		$options   = new STMC_Options();
		$registry  = new STMC_Checks_Registry();
		$scheduler = new STMC_Scan_Scheduler( $registry );
		$engine    = new STMC_Scan_Engine( $options, new STMC_Score(), $scheduler, $registry );

		return new STMC_Api_Rest( $options, $engine, new STMC_Csv_Exporter(), $registry );
	}

	/**
	 * @param string $route REST route.
	 * @return object
	 */
	private function request_with_route( string $route ) {
		return new class( $route ) {
			/**
			 * @var string
			 */
			private $route;

			/**
			 * @param string $route Route.
			 */
			public function __construct( string $route ) {
				$this->route = $route;
			}

			/**
			 * @return string
			 */
			public function get_route() {
				return $this->route;
			}
		};
	}
}
