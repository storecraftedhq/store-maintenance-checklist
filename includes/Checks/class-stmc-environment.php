<?php
/**
 * Treat-as-production precedence (agent contract §4).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Environment / force-production helper.
 */
final class STMC_Environment {

	/**
	 * @var STMC_Options
	 */
	private $options;

	/**
	 * @param STMC_Options $options Options store.
	 */
	public function __construct( STMC_Options $options ) {
		$this->options = $options;
	}

	/**
	 * Whether severities should use production rules (no softening / env skips off).
	 */
	public function treat_as_production(): bool {
		if ( defined( 'STMC_FORCE_PRODUCTION_SEVERITY' ) && STMC_FORCE_PRODUCTION_SEVERITY ) {
			return true;
		}

		if ( function_exists( 'apply_filters' ) ) {
			/**
			 * Filter whether to treat the site as production for checklist severities.
			 *
			 * @param bool|null $treat Treat as production.
			 */
			$filtered = apply_filters( 'stmc_treat_as_production', null );
			if ( true === $filtered ) {
				return true;
			}
		}

		$settings = $this->options->get_settings();
		if ( ! empty( $settings['force_production_severity'] ) ) {
			return true;
		}

		$type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		if ( in_array( $type, array( 'local', 'development', 'staging' ), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Current environment type string.
	 */
	public function type(): string {
		return function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	}
}
