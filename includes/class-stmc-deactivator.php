<?php
/**
 * Deactivation handler.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Runs on plugin deactivation.
 */
final class STMC_Deactivator {

	/**
	 * Clear scheduled hooks / scan work when the product adds them.
	 */
	public static function deactivate(): void {
		// Phase 5+: cancel Action Scheduler group `stmc` and related state.
	}
}
