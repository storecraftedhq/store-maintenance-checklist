<?php
/**
 * Main plugin bootstrap.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Boots text domain, admin, REST, and scan scheduler.
 */
final class STMC_Plugin {

	/**
	 * Wire hooks after WooCommerce is available.
	 */
	public function boot(): void {
		$options   = new STMC_Options();
		$score     = new STMC_Score();
		$registry  = new STMC_Checks_Registry();
		$catalog   = new STMC_Catalog_Source_Live();
		$scheduler = new STMC_Scan_Scheduler( $registry, $catalog );
		$engine    = new STMC_Scan_Engine( $options, $score, $scheduler, $registry, $catalog );
		$csv       = new STMC_Csv_Exporter();

		$scheduler->register();
		( new STMC_Api_Rest( $options, $engine, $csv, $registry ) )->register();

		if ( is_admin() ) {
			( new STMC_Admin() )->register();
		}
	}
}
