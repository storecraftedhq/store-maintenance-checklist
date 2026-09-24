<?php
/**
 * Smoke tests for plugin bootstrap classes.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Verifies core classes load via the SPL autoloader.
 */
final class STMC_Plugin_Test extends TestCase {

	/**
	 * Plugin class should be instantiable without WordPress.
	 */
	public function test_plugin_class_is_instantiable(): void {
		$plugin = new STMC_Plugin();
		$this->assertInstanceOf( STMC_Plugin::class, $plugin );
	}

	/**
	 * Autoloader should resolve Admin classes from includes/Admin/.
	 */
	public function test_admin_class_is_autoloadable(): void {
		$this->assertTrue( class_exists( 'STMC_Admin', true ) );
	}
}
