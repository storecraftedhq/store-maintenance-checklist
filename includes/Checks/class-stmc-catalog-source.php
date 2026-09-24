<?php
/**
 * Catalog data source contract for AS product/variation batches.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Reads product and variation rows for catalog checks.
 */
interface STMC_Catalog_Source {

	/**
	 * Total product count available to scan.
	 */
	public function count_products(): int;

	/**
	 * Product rows for a batch.
	 *
	 * @param int $offset Offset.
	 * @param int $limit  Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_products( int $offset, int $limit ): array;

	/**
	 * Total variation count available to scan.
	 */
	public function count_variations(): int;

	/**
	 * Variation rows for a batch.
	 *
	 * @param int $offset Offset.
	 * @param int $limit  Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_variations( int $offset, int $limit ): array;
}
