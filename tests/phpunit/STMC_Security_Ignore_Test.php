<?php
/**
 * Security tests for ignore input hardening (H-1, H-2).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Ignore reason character limits and unknown-check REST errors.
 */
final class STMC_Security_Ignore_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * Ignore reasons truncate to 200 characters (not bytes) of valid UTF-8.
	 */
	public function test_ignore_reason_truncates_to_200_utf8_characters(): void {
		$reason = str_repeat( 'a', 199 ) . 'é…';

		$options = new STMC_Options();
		$options->ignore_check( 'payments.no_gateways', $reason );

		$stored = $options->get_ignores()['payments.no_gateways']['reason'] ?? '';

		$this->assertTrue(
			mb_check_encoding( $stored, 'UTF-8' ),
			'Stored ignore reason must be valid UTF-8 (byte truncation can split a multibyte char).'
		);
		$this->assertSame( 200, mb_strlen( $stored, 'UTF-8' ) );
		$this->assertSame( str_repeat( 'a', 199 ) . 'é', $stored );
	}

	/**
	 * POST ignore for an unknown check id returns 404 and leaves ignores unchanged.
	 */
	public function test_post_ignore_unknown_check_returns_404(): void {
		$options = new STMC_Options();
		$options->ignore_check( 'payments.no_gateways', 'keep me' );
		$before  = $options->get_ignores();
		$api     = $this->make_api( $options );
		$request = new ArrayObject( array( 'id' => 'not.a.real.check' ) );

		$result = $api->post_ignore( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stmc_unknown_check', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
		$this->assertSame( $before, $options->get_ignores() );
	}

	/**
	 * DELETE ignore for an unknown check id returns 404 and leaves ignores unchanged.
	 */
	public function test_delete_ignore_unknown_check_returns_404(): void {
		$options = new STMC_Options();
		$options->ignore_check( 'payments.no_gateways', 'keep me' );
		$before  = $options->get_ignores();
		$api     = $this->make_api( $options );
		$request = new ArrayObject( array( 'id' => 'not.a.real.check' ) );

		$result = $api->delete_ignore( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'stmc_unknown_check', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
		$this->assertSame( $before, $options->get_ignores() );
	}

	/**
	 * @param STMC_Options $options Options store.
	 */
	private function make_api( STMC_Options $options ): STMC_Api_Rest {
		$registry  = new STMC_Checks_Registry();
		$scheduler = new STMC_Scan_Scheduler( $registry );
		$engine    = new STMC_Scan_Engine( $options, new STMC_Score(), $scheduler, $registry );

		return new STMC_Api_Rest( $options, $engine, new STMC_Csv_Exporter(), $registry );
	}
}
