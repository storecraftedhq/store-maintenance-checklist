<?php
/**
 * SPL autoloader for STMC_* classes (no Composer runtime vendor).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Maps STMC_* class names to includes/ files.
 */
final class STMC_Autoloader {

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Load a class file when the class starts with STMC_.
	 *
	 * @param string $class_name Fully-qualified class name.
	 */
	public static function autoload( string $class_name ): void {
		if ( 0 !== strpos( $class_name, 'STMC_' ) ) {
			return;
		}

		$relative = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		$candidates = array(
			STMC_PLUGIN_DIR . 'includes/' . $relative,
		);

		$subdir_map = array(
			'STMC_Admin'       => 'Admin',
			'STMC_Api'         => 'Api',
			'STMC_Scan'        => 'Scan',
			'STMC_Checks'      => 'Checks',
			'STMC_Check'       => 'Checks',
			'STMC_Finding'     => 'Checks',
			'STMC_Environment' => 'Checks',
			'STMC_Catalog'     => 'Checks',
		);

		foreach ( $subdir_map as $prefix => $subdir ) {
			if ( 0 === strpos( $class_name, $prefix ) ) {
				array_unshift(
					$candidates,
					STMC_PLUGIN_DIR . 'includes/' . $subdir . '/' . $relative
				);
				break;
			}
		}

		foreach ( $candidates as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
