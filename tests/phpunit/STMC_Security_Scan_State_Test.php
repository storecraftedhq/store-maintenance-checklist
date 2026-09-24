<?php
/**
 * Security / perf tests for scan state persistence (Lead B).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Scan state must not autoload on every request.
 */
final class STMC_Security_Scan_State_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * set_scan_state persists with autoload disabled.
	 */
	public function test_set_scan_state_disables_autoload(): void {
		$options = new STMC_Options();
		$options->set_scan_state(
			array(
				'state'   => 'running',
				'scan_id' => 'scan_test',
			)
		);

		$this->assertSame(
			array(
				'state'   => 'running',
				'scan_id' => 'scan_test',
			),
			$options->get_scan_state()
		);
		$this->assertFalse(
			$GLOBALS['stmc_test_option_autoload']['stmc_scan_state'] ?? null,
			'stmc_scan_state must be stored with autoload=false.'
		);
	}
}
