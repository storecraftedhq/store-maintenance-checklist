<?php
/**
 * Live WooCommerce catalog source (product CRUD APIs).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Reads products/variations via WooCommerce product APIs.
 */
final class STMC_Catalog_Source_Live implements STMC_Catalog_Source {

	/**
	 * {@inheritdoc}
	 */
	public function count_products(): int {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return 0;
		}

		$ids = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 1,
				'return'   => 'ids',
				'paginate' => true,
			)
		);

		if ( is_object( $ids ) && isset( $ids->total ) ) {
			return (int) $ids->total;
		}

		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_products( int $offset, int $limit ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$page     = (int) floor( $offset / max( 1, $limit ) ) + 1;
		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => $limit,
				'page'    => $page,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);

		if ( ! is_array( $products ) ) {
			return array();
		}

		$rows = array();
		foreach ( $products as $product ) {
			if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
				continue;
			}
			$type         = method_exists( $product, 'get_type' ) ? (string) $product->get_type() : 'simple';
			$is_external  = in_array( $type, array( 'external', 'affiliate' ), true );
			$downloadable = method_exists( $product, 'is_downloadable' ) ? (bool) $product->is_downloadable() : false;
			$downloads    = ( $downloadable && method_exists( $product, 'get_downloads' ) ) ? $product->get_downloads() : array();

			$rows[] = array(
				'id'                => (int) $product->get_id(),
				'type'              => $type,
				'price'             => method_exists( $product, 'get_price' ) ? (string) $product->get_price() : '',
				'regular_price'     => method_exists( $product, 'get_regular_price' ) ? (string) $product->get_regular_price() : '',
				'image_id'          => method_exists( $product, 'get_image_id' ) ? (int) $product->get_image_id() : 0,
				'downloadable'      => $downloadable,
				'has_downloads'     => is_array( $downloads ) ? count( $downloads ) > 0 : ( ! empty( $downloads ) ),
				'is_external'       => $is_external,
				'name'              => method_exists( $product, 'get_name' ) ? (string) $product->get_name() : '',
				'description'       => method_exists( $product, 'get_description' ) ? (string) $product->get_description() : '',
				'short_description' => method_exists( $product, 'get_short_description' ) ? (string) $product->get_short_description() : '',
				'sku'               => method_exists( $product, 'get_sku' ) ? (string) $product->get_sku() : '',
				'stock_status'      => method_exists( $product, 'get_stock_status' ) ? (string) $product->get_stock_status() : 'instock',
				'is_virtual'        => method_exists( $product, 'is_virtual' ) ? (bool) $product->is_virtual() : false,
				'needs_shipping'    => method_exists( $product, 'needs_shipping' ) ? (bool) $product->needs_shipping() : true,
			);
		}

		return $rows;
	}

	/**
	 * {@inheritdoc}
	 */
	public function count_variations(): int {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return 0;
		}

		$ids = wc_get_products(
			array(
				'type'     => 'variation',
				'status'   => array( 'publish', 'private' ),
				'limit'    => 1,
				'return'   => 'ids',
				'paginate' => true,
			)
		);

		if ( is_object( $ids ) && isset( $ids->total ) ) {
			return (int) $ids->total;
		}

		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_variations( int $offset, int $limit ): array {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$page       = (int) floor( $offset / max( 1, $limit ) ) + 1;
		$variations = wc_get_products(
			array(
				'type'    => 'variation',
				'status'  => array( 'publish', 'private' ),
				'limit'   => $limit,
				'page'    => $page,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);

		if ( ! is_array( $variations ) ) {
			return array();
		}

		$rows = array();
		foreach ( $variations as $variation ) {
			if ( ! is_object( $variation ) || ! method_exists( $variation, 'get_id' ) ) {
				continue;
			}
			$rows[] = array(
				'id'            => (int) $variation->get_id(),
				'parent_id'     => method_exists( $variation, 'get_parent_id' ) ? (int) $variation->get_parent_id() : 0,
				'price'         => method_exists( $variation, 'get_price' ) ? (string) $variation->get_price() : '',
				'regular_price' => method_exists( $variation, 'get_regular_price' ) ? (string) $variation->get_regular_price() : '',
				'name'          => method_exists( $variation, 'get_name' ) ? (string) $variation->get_name() : '',
				'sku'           => method_exists( $variation, 'get_sku' ) ? (string) $variation->get_sku() : '',
			);
		}

		return $rows;
	}
}
