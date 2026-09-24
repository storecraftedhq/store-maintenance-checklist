<?php
/**
 * Contract for a single checklist check.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Check interface.
 */
interface STMC_Check {

	/**
	 * Stable check id.
	 */
	public function id(): string;

	/**
	 * Area slug.
	 */
	public function area(): string;

	/**
	 * `sync` or `as`.
	 */
	public function runner(): string;

	/**
	 * Default production severity for open findings.
	 */
	public function severity(): string;

	/**
	 * Human title.
	 */
	public function title(): string;

	/**
	 * Evaluate against a snapshot; return one Finding array.
	 *
	 * @param STMC_Check_Snapshot $snapshot Store snapshot.
	 * @return array<string, mixed>
	 */
	public function evaluate( STMC_Check_Snapshot $snapshot ): array;
}
