<?php
/**
 * In-memory catalog source for unit tests.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Memory-backed product/variation rows.
 */
final class STMC_Catalog_Source_Memory implements STMC_Catalog_Source {

	/**
	 * @var array<int, array<string, mixed>>
	 */
	private $products;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	private $variations;

	/**
	 * @param array<int, array<string, mixed>> $products   Product rows.
	 * @param array<int, array<string, mixed>> $variations Variation rows.
	 */
	public function __construct( array $products = array(), array $variations = array() ) {
		$this->products   = array_values( $products );
		$this->variations = array_values( $variations );
	}

	/**
	 * {@inheritdoc}
	 */
	public function count_products(): int {
		return count( $this->products );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_products( int $offset, int $limit ): array {
		return array_slice( $this->products, $offset, $limit );
	}

	/**
	 * {@inheritdoc}
	 */
	public function count_variations(): int {
		return count( $this->variations );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_variations( int $offset, int $limit ): array {
		return array_slice( $this->variations, $offset, $limit );
	}
}
