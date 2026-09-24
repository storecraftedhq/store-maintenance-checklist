<?php
/**
 * Authorization regression net for stmc/v1 routes (H-3).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Every registered stmc/v1 route/method must use the manage_woocommerce gate.
 */
final class STMC_Security_Auth_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * Real route table: every method has a real permission callback that enforces the cap.
	 */
	public function test_all_stmc_v1_routes_require_manage_woocommerce(): void {
		$this->boot_stmc_routes();
		$this->assert_all_stmc_routes_gated();
		$this->assertGreaterThanOrEqual( 12, $this->count_stmc_route_methods(), 'Expected all contracted stmc/v1 method handlers.' );
	}

	/**
	 * Prove the net fails when an ungated throwaway route is registered.
	 */
	public function test_auth_net_detects_missing_permission_callback(): void {
		$this->boot_stmc_routes();
		register_rest_route(
			'stmc/v1',
			'/__throwaway_ungated',
			array(
				'methods'  => 'GET',
				'callback' => static function () {
					return null;
				},
			)
		);

		$caught = false;
		try {
			$this->assert_all_stmc_routes_gated();
		} catch ( ExpectationFailedException $e ) {
			$caught = true;
			$this->assertStringContainsString( '__throwaway_ungated', $e->getMessage() );
		}

		$this->assertTrue( $caught, 'Auth net must fail when a stmc/v1 route lacks permission_callback.' );
	}

	/**
	 * Prove the net fails when permission_callback is __return_true.
	 */
	public function test_auth_net_detects_return_true_permission_callback(): void {
		$this->boot_stmc_routes();
		register_rest_route(
			'stmc/v1',
			'/__throwaway_open',
			array(
				'methods'             => 'GET',
				'callback'            => static function () {
					return null;
				},
				'permission_callback' => '__return_true',
			)
		);

		$caught = false;
		try {
			$this->assert_all_stmc_routes_gated();
		} catch ( ExpectationFailedException $e ) {
			$caught = true;
			$this->assertStringContainsString( '__throwaway_open', $e->getMessage() );
		}

		$this->assertTrue( $caught, 'Auth net must fail when permission_callback is __return_true.' );
	}

	/**
	 * Register plugin REST routes via rest_api_init.
	 */
	private function boot_stmc_routes(): void {
		$options   = new STMC_Options();
		$registry  = new STMC_Checks_Registry();
		$scheduler = new STMC_Scan_Scheduler( $registry );
		$engine    = new STMC_Scan_Engine( $options, new STMC_Score(), $scheduler, $registry );
		$api       = new STMC_Api_Rest( $options, $engine, new STMC_Csv_Exporter(), $registry );
		$api->register();
		do_action( 'rest_api_init' );
	}

	/**
	 * Assert every /stmc/v1 route method is gated on manage_woocommerce.
	 */
	private function assert_all_stmc_routes_gated(): void {
		$routes = rest_get_server()->get_routes();
		$seen   = 0;

		foreach ( $routes as $route => $endpoints ) {
			if ( 0 !== strpos( (string) $route, '/stmc/v1' ) ) {
				continue;
			}

			foreach ( $endpoints as $endpoint ) {
				if ( ! is_array( $endpoint ) ) {
					continue;
				}

				$methods = $endpoint['methods'] ?? '';
				$label   = $route . ' ' . $this->methods_label( $methods );
				++$seen;

				$this->assertArrayHasKey(
					'permission_callback',
					$endpoint,
					"{$label} must set permission_callback."
				);

				$callback = $endpoint['permission_callback'];
				$this->assertNotSame(
					'__return_true',
					$callback,
					"{$label} must not use __return_true as permission_callback."
				);
				$this->assertTrue(
					is_callable( $callback ),
					"{$label} permission_callback must be callable."
				);

				$GLOBALS['stmc_test_caps'] = array();
				$denied                    = call_user_func( $callback );
				$this->assertInstanceOf( WP_Error::class, $denied, "{$label} must deny users without manage_woocommerce." );
				$this->assertSame( 'stmc_forbidden', $denied->get_error_code(), "{$label} denial code." );
				$this->assertSame( 403, $denied->get_error_data()['status'], "{$label} must return HTTP 403." );

				$GLOBALS['stmc_test_caps'] = array( 'manage_woocommerce' => true );
				$this->assertTrue(
					call_user_func( $callback ),
					"{$label} must allow manage_woocommerce (shop manager / administrator)."
				);
			}
		}

		$this->assertGreaterThan( 0, $seen, 'No /stmc/v1 routes were registered.' );
	}

	/**
	 * @return int
	 */
	private function count_stmc_route_methods(): int {
		$count = 0;
		foreach ( rest_get_server()->get_routes() as $route => $endpoints ) {
			if ( 0 !== strpos( (string) $route, '/stmc/v1' ) ) {
				continue;
			}
			$count += count( $endpoints );
		}
		return $count;
	}

	/**
	 * @param mixed $methods Methods value from route args.
	 */
	private function methods_label( $methods ): string {
		if ( is_array( $methods ) ) {
			return implode( '|', array_map( 'strval', $methods ) );
		}
		return (string) $methods;
	}
}
