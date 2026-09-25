<?php
/**
 * Check registry: 50 matrix checks + catalog batch accumulators.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Registered check IDs, sync runner, and catalog AS helpers.
 */
final class STMC_Checks_Registry {

	public const PRODUCTS_PER_BATCH   = 100;
	public const VARIATIONS_PER_BATCH = 200;

	/**
	 * @var array<string, STMC_Check>
	 */
	private $checks = array();

	/**
	 * Register all 50 checks.
	 */
	public function __construct() {
		$instances = array(
			new STMC_Check_Catalog_Missing_Price(),
			new STMC_Check_Catalog_Incomplete_Variation_Prices(),
			new STMC_Check_Catalog_Missing_Featured_Image(),
			new STMC_Check_Catalog_Missing_Description(),
			new STMC_Check_Catalog_Missing_Short_Description(),
			new STMC_Check_Catalog_Downloadable_Missing_File(),
			new STMC_Check_Catalog_Unsellable_Stock(),
			new STMC_Check_Catalog_Missing_Sku(),
			new STMC_Check_Catalog_Duplicate_Sku(),
			new STMC_Check_Catalog_Virtual_Requires_Shipping(),
			new STMC_Check_Catalog_Required_Pages(),
			new STMC_Check_Catalog_Policy_Pages_Unpublished(),
			new STMC_Check_Catalog_Checkout_Page_Signal(),
			new STMC_Check_Catalog_Expired_Coupons_Enabled(),
			new STMC_Check_Payments_No_Gateways(),
			new STMC_Check_Payments_Gateway_Test_Mode(),
			new STMC_Check_Payments_Taxes_Enabled_No_Rates(),
			new STMC_Check_Shipping_No_Methods_Configured(),
			new STMC_Check_Shipping_Empty_Default_Zone(),
			new STMC_Check_Shipping_Zone_Without_Methods(),
			new STMC_Check_Shipping_No_Delivery_Eta(),
			new STMC_Check_Email_Weak_From_Address(),
			new STMC_Check_Email_Customer_Processing_Disabled(),
			new STMC_Check_Email_Customer_Completed_Disabled(),
			new STMC_Check_Email_Customer_Refunded_Disabled(),
			new STMC_Check_Email_Customer_Cancelled_Disabled(),
			new STMC_Check_Email_New_Order_Disabled(),
			new STMC_Check_Email_New_Order_Recipient_Missing(),
			new STMC_Check_Orders_Stuck_Pending(),
			new STMC_Check_Orders_Stuck_On_Hold(),
			new STMC_Check_Orders_Stuck_Failed(),
			new STMC_Check_Orders_Failed_Action_Scheduler(),
			new STMC_Check_Orders_Overdue_Action_Scheduler(),
			new STMC_Check_Orders_Hpos_Compat_Mode(),
			new STMC_Check_Orders_Aged_Completed_Volume(),
			new STMC_Check_Orders_Aged_Cancelled_Volume(),
			new STMC_Check_Environment_Https_Off_Production(),
			new STMC_Check_Environment_Debug_Display_Production(),
			new STMC_Check_Environment_Plain_Permalinks(),
			new STMC_Check_Environment_Store_Address_Incomplete(),
			new STMC_Check_Environment_Fatal_Error_Logs(),
			new STMC_Check_Environment_Php_Below_Recommended(),
			new STMC_Check_Environment_Wp_Below_Recommended(),
			new STMC_Check_Environment_Db_Below_Recommended(),
			new STMC_Check_Environment_Memory_Below_Recommended(),
			new STMC_Check_Environment_Updates_Available(),
			new STMC_Check_Environment_Coming_Soon_Enabled(),
			new STMC_Check_Environment_Currency_Or_Timezone_Unset(),
			new STMC_Check_Environment_Site_Health_Linkout(),
			new STMC_Check_Environment_Wc_Status_Linkout(),
		);

		foreach ( $instances as $check ) {
			$this->checks[ $check->id() ] = $check;
		}
	}

	/**
	 * @return array<int, string>
	 */
	public function ids(): array {
		return array_keys( $this->checks );
	}

	/**
	 * @param string $id Check id.
	 */
	public function get( string $id ): ?STMC_Check {
		return $this->checks[ $id ] ?? null;
	}

	/**
	 * Run all sync checks; apply shipping exclusivity.
	 *
	 * @param STMC_Check_Snapshot $snapshot Snapshot.
	 * @return array<int, array<string, mixed>>
	 */
	public function run_sync( STMC_Check_Snapshot $snapshot ): array {
		$findings = array();
		foreach ( $this->checks as $check ) {
			if ( 'sync' !== $check->runner() ) {
				continue;
			}
			$findings[] = $check->evaluate( $snapshot );
		}

		return $this->apply_shipping_exclusivity( $findings );
	}

	/**
	 * Empty accumulators for catalog AS batches.
	 *
	 * @return array<string, mixed>
	 */
	public function empty_catalog_accumulators(): array {
		return array(
			'missing_price'               => array(
				'count'   => 0,
				'samples' => array(),
			),
			'missing_featured_image'      => array(
				'count'   => 0,
				'samples' => array(),
			),
			'missing_description'         => array(
				'count'   => 0,
				'samples' => array(),
			),
			'missing_short_description'   => array(
				'count'   => 0,
				'samples' => array(),
			),
			'downloadable_missing_file'   => array(
				'count'   => 0,
				'samples' => array(),
			),
			'unsellable_stock'            => array(
				'count'   => 0,
				'samples' => array(),
			),
			'missing_sku'                 => array(
				'count'   => 0,
				'samples' => array(),
			),
			'duplicate_sku'               => array(
				'by_sku'  => array(),
				'samples' => array(),
			),
			'virtual_requires_shipping'   => array(
				'count'   => 0,
				'samples' => array(),
			),
			'incomplete_variation_prices' => array(
				'parents' => array(),
				'samples' => array(),
			),
		);
	}

	/**
	 * Accumulate product-row catalog checks.
	 *
	 * @param array<string, mixed>             $acc      Accumulators.
	 * @param array<int, array<string, mixed>> $products Product rows.
	 */
	public function accumulate_products( array &$acc, array $products ): void {
		foreach ( $products as $product ) {
			if ( ! is_array( $product ) ) {
				continue;
			}
			$id   = (int) ( $product['id'] ?? 0 );
			$name = (string) ( $product['name'] ?? '' );

			$is_external = ! empty( $product['is_external'] )
				|| in_array( (string) ( $product['type'] ?? '' ), array( 'external', 'affiliate' ), true );

			if ( ! $is_external ) {
				$price         = (string) ( $product['price'] ?? '' );
				$regular_price = (string) ( $product['regular_price'] ?? '' );
				if ( STMC_Check_Base::price_missing( $price ) && STMC_Check_Base::price_missing( $regular_price ) ) {
					++$acc['missing_price']['count'];
					if ( count( $acc['missing_price']['samples'] ) < 3 ) {
						$acc['missing_price']['samples'][] = array(
							'id'   => $id,
							'name' => $name,
						);
					}
				}
			}

			if ( empty( $product['image_id'] ) ) {
				++$acc['missing_featured_image']['count'];
				if ( count( $acc['missing_featured_image']['samples'] ) < 3 ) {
					$acc['missing_featured_image']['samples'][] = array(
						'id'   => $id,
						'name' => $name,
					);
				}
			}

			if ( STMC_Check_Base::description_missing( (string) ( $product['description'] ?? '' ) ) ) {
				++$acc['missing_description']['count'];
				if ( count( $acc['missing_description']['samples'] ) < 3 ) {
					$acc['missing_description']['samples'][] = array(
						'id'   => $id,
						'name' => $name,
					);
				}
			}

			if ( STMC_Check_Base::description_missing( (string) ( $product['short_description'] ?? '' ) ) ) {
				++$acc['missing_short_description']['count'];
				if ( count( $acc['missing_short_description']['samples'] ) < 3 ) {
					$acc['missing_short_description']['samples'][] = array(
						'id'   => $id,
						'name' => $name,
					);
				}
			}

			if ( ! empty( $product['downloadable'] ) && empty( $product['has_downloads'] ) ) {
				++$acc['downloadable_missing_file']['count'];
				if ( count( $acc['downloadable_missing_file']['samples'] ) < 3 ) {
					$acc['downloadable_missing_file']['samples'][] = array(
						'id'   => $id,
						'name' => $name,
					);
				}
			}

			if ( ! $is_external && 'outofstock' === (string) ( $product['stock_status'] ?? '' ) ) {
				++$acc['unsellable_stock']['count'];
				if ( count( $acc['unsellable_stock']['samples'] ) < 3 ) {
					$acc['unsellable_stock']['samples'][] = array(
						'id'   => $id,
						'name' => $name,
					);
				}
			}

			$sku = trim( (string) ( $product['sku'] ?? '' ) );
			if ( '' === $sku ) {
				++$acc['missing_sku']['count'];
				if ( count( $acc['missing_sku']['samples'] ) < 3 ) {
					$acc['missing_sku']['samples'][] = array(
						'id'   => $id,
						'name' => $name,
					);
				}
			} else {
				$this->track_sku( $acc, $sku, $id, $name, 0 );
			}

			if ( ! empty( $product['is_virtual'] ) && ! empty( $product['needs_shipping'] ) ) {
				++$acc['virtual_requires_shipping']['count'];
				if ( count( $acc['virtual_requires_shipping']['samples'] ) < 3 ) {
					$acc['virtual_requires_shipping']['samples'][] = array(
						'id'   => $id,
						'name' => $name,
					);
				}
			}
		}
	}

	/**
	 * Accumulate variation price gaps (count distinct parents).
	 *
	 * @param array<string, mixed>             $acc        Accumulators.
	 * @param array<int, array<string, mixed>> $variations Variation rows.
	 */
	public function accumulate_variations( array &$acc, array $variations ): void {
		foreach ( $variations as $variation ) {
			if ( ! is_array( $variation ) ) {
				continue;
			}
			$price         = (string) ( $variation['price'] ?? '' );
			$regular_price = (string) ( $variation['regular_price'] ?? '' );
			if ( STMC_Check_Base::price_missing( $price ) && STMC_Check_Base::price_missing( $regular_price ) ) {
				$parent = (int) ( $variation['parent_id'] ?? 0 );
				if ( $parent > 0 ) {
					$acc['incomplete_variation_prices']['parents'][ $parent ] = true;
					if ( count( $acc['incomplete_variation_prices']['samples'] ) < 3 ) {
						$acc['incomplete_variation_prices']['samples'][] = array(
							'id'        => (int) ( $variation['id'] ?? 0 ),
							'parent_id' => $parent,
							'name'      => (string) ( $variation['name'] ?? '' ),
						);
					}
				}
			}

			$sku = trim( (string) ( $variation['sku'] ?? '' ) );
			if ( '' !== $sku ) {
				$this->track_sku(
					$acc,
					$sku,
					(int) ( $variation['id'] ?? 0 ),
					(string) ( $variation['name'] ?? '' ),
					(int) ( $variation['parent_id'] ?? 0 )
				);
			}
		}
	}

	/**
	 * Track a non-empty SKU for duplicate detection.
	 *
	 * @param array<string, mixed> $acc       Accumulators.
	 * @param string               $sku       SKU.
	 * @param int                  $id        Product or variation ID.
	 * @param string               $name      Display name.
	 * @param int                  $parent_id Parent product ID for variations.
	 */
	private function track_sku( array &$acc, string $sku, int $id, string $name, int $parent_id ): void {
		if ( ! isset( $acc['duplicate_sku']['by_sku'][ $sku ] ) || ! is_array( $acc['duplicate_sku']['by_sku'][ $sku ] ) ) {
			$acc['duplicate_sku']['by_sku'][ $sku ] = array();
		}
		$acc['duplicate_sku']['by_sku'][ $sku ][] = array(
			'id'        => $id,
			'name'      => $name,
			'parent_id' => $parent_id,
		);
	}

	/**
	 * Convert catalog accumulators into findings (passed if count 0).
	 *
	 * @param array<string, mixed> $acc Accumulators.
	 * @return array<int, array<string, mixed>>
	 */
	public function finalize_catalog_findings( array $acc ): array {
		$admin = function_exists( 'admin_url' ) ? (string) admin_url() : 'http://example.test/wp-admin/';
		$snap  = STMC_Check_Snapshot::from_array( array( 'admin_url' => $admin ) );

		$findings = array();

		$missing = (int) ( $acc['missing_price']['count'] ?? 0 );
		$check   = $this->get( 'catalog.missing_price' );
		if ( $check ) {
			$findings[] = $missing > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Customers cannot complete purchase when prices are missing.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d products with missing or invalid prices.', 'store-maintenance-checklist-for-woocommerce' ),
							$missing
						),
						'count'   => $missing,
						'samples' => $this->product_edit_samples( $acc['missing_price']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$parents = $acc['incomplete_variation_prices']['parents'] ?? array();
		$count   = is_array( $parents ) ? count( $parents ) : 0;
		$check   = $this->get( 'catalog.incomplete_variation_prices' );
		if ( $check ) {
			$findings[] = $count > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Shoppers hit unsellable variations when prices are incomplete.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: parent product count */
							__( '%d variable products with incomplete variation prices.', 'store-maintenance-checklist-for-woocommerce' ),
							$count
						),
						'count'   => $count,
						'samples' => $this->product_edit_samples( $acc['incomplete_variation_prices']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$images = (int) ( $acc['missing_featured_image']['count'] ?? 0 );
		$check  = $this->get( 'catalog.missing_featured_image' );
		if ( $check ) {
			$findings[] = $images > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Missing featured images hurt trust and listings.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d products missing a featured image.', 'store-maintenance-checklist-for-woocommerce' ),
							$images
						),
						'count'   => $images,
						'samples' => $this->product_edit_samples( $acc['missing_featured_image']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$descriptions = (int) ( $acc['missing_description']['count'] ?? 0 );
		$check        = $this->get( 'catalog.missing_description' );
		if ( $check ) {
			$findings[] = $descriptions > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Empty product pages hurt trust, SEO, and support load.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d products missing a description.', 'store-maintenance-checklist-for-woocommerce' ),
							$descriptions
						),
						'count'   => $descriptions,
						'samples' => $this->product_edit_samples( $acc['missing_description']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$short_descriptions = (int) ( $acc['missing_short_description']['count'] ?? 0 );
		$check              = $this->get( 'catalog.missing_short_description' );
		if ( $check ) {
			$findings[] = $short_descriptions > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Shop and archive cards often rely on the short description.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d products missing a short description.', 'store-maintenance-checklist-for-woocommerce' ),
							$short_descriptions
						),
						'count'   => $short_descriptions,
						'samples' => $this->product_edit_samples( $acc['missing_short_description']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$downloads = (int) ( $acc['downloadable_missing_file']['count'] ?? 0 );
		$check     = $this->get( 'catalog.downloadable_missing_file' );
		if ( $check ) {
			$findings[] = $downloads > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Paid downloads fail after checkout when files are missing.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d downloadable products missing files.', 'store-maintenance-checklist-for-woocommerce' ),
							$downloads
						),
						'count'   => $downloads,
						'samples' => $this->product_edit_samples( $acc['downloadable_missing_file']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$oos   = (int) ( $acc['unsellable_stock']['count'] ?? 0 );
		$check = $this->get( 'catalog.unsellable_stock' );
		if ( $check ) {
			$findings[] = $oos > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Out-of-stock products cannot be purchased until restocked.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d products marked out of stock.', 'store-maintenance-checklist-for-woocommerce' ),
							$oos
						),
						'count'   => $oos,
						'samples' => $this->product_edit_samples( $acc['unsellable_stock']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$missing_sku = (int) ( $acc['missing_sku']['count'] ?? 0 );
		$check       = $this->get( 'catalog.missing_sku' );
		if ( $check ) {
			$findings[] = $missing_sku > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Missing SKUs make inventory sync and ops harder.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d products missing a SKU.', 'store-maintenance-checklist-for-woocommerce' ),
							$missing_sku
						),
						'count'   => $missing_sku,
						'samples' => $this->product_edit_samples( $acc['missing_sku']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$dup_samples = array();
		$dup_count   = 0;
		$by_sku      = $acc['duplicate_sku']['by_sku'] ?? array();
		if ( is_array( $by_sku ) ) {
			foreach ( $by_sku as $rows ) {
				if ( ! is_array( $rows ) || count( $rows ) < 2 ) {
					continue;
				}
				++$dup_count;
				if ( count( $dup_samples ) < 3 && isset( $rows[0] ) && is_array( $rows[0] ) ) {
					$dup_samples[] = $rows[0];
				}
			}
		}
		$check = $this->get( 'catalog.duplicate_sku' );
		if ( $check ) {
			$findings[] = $dup_count > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Duplicate SKUs cause inventory collisions and feed errors.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: number of duplicated SKU values */
							__( '%d duplicate SKU values found.', 'store-maintenance-checklist-for-woocommerce' ),
							$dup_count
						),
						'count'   => $dup_count,
						'samples' => $this->product_edit_samples( $dup_samples, $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		$virtual_ship = (int) ( $acc['virtual_requires_shipping']['count'] ?? 0 );
		$check        = $this->get( 'catalog.virtual_requires_shipping' );
		if ( $check ) {
			$findings[] = $virtual_ship > 0
				? STMC_Finding::from_check(
					$check,
					'open',
					__( 'Virtual products that still require shipping confuse checkout tax and shipping.', 'store-maintenance-checklist-for-woocommerce' ),
					array(
						'summary' => sprintf(
							/* translators: %d: product count */
							__( '%d virtual products still require shipping.', 'store-maintenance-checklist-for-woocommerce' ),
							$virtual_ship
						),
						'count'   => $virtual_ship,
						'samples' => $this->product_edit_samples( $acc['virtual_requires_shipping']['samples'] ?? array(), $snap->admin_url ),
					),
					array(
						'primary_action' => array(
							'label' => __( 'Products', 'store-maintenance-checklist-for-woocommerce' ),
							'url'   => rtrim( $snap->admin_url, '/' ) . '/edit.php?post_type=product',
						),
					)
				)
				: STMC_Finding::passed( $check );
		}

		return $findings;
	}

	/**
	 * Build product edit evidence samples (label + admin edit URL).
	 *
	 * @param array<int, array<string, mixed>> $samples Raw samples with id/name/parent_id.
	 * @param string                           $admin_url Admin base URL.
	 * @return array<int, array{label: string, url: string}>
	 */
	private function product_edit_samples( array $samples, string $admin_url ): array {
		$out  = array();
		$base = rtrim( $admin_url, '/' );

		foreach ( array_slice( $samples, 0, 3 ) as $sample ) {
			if ( ! is_array( $sample ) ) {
				continue;
			}
			$id        = (int) ( $sample['id'] ?? 0 );
			$parent_id = (int) ( $sample['parent_id'] ?? 0 );
			$edit_id   = $parent_id > 0 ? $parent_id : $id;
			$name      = trim( (string) ( $sample['name'] ?? '' ) );
			$label     = '' !== $name ? $name : ( $edit_id > 0 ? '#' . $edit_id : '' );
			if ( '' === $label ) {
				continue;
			}

			$url = '';
			if ( $edit_id > 0 ) {
				if ( function_exists( 'get_edit_post_link' ) ) {
					$link = get_edit_post_link( $edit_id, 'raw' );
					if ( is_string( $link ) && '' !== $link ) {
						$url = $link;
					}
				}
				if ( '' === $url ) {
					$url = $base . '/post.php?post=' . $edit_id . '&action=edit';
				}
			}

			$out[] = array(
				'label' => $label,
				'url'   => $url,
			);
		}

		return $out;
	}

	/**
	 * If no methods anywhere, force zone warnings to passed.
	 *
	 * @param array<int, array<string, mixed>> $findings Findings.
	 * @return array<int, array<string, mixed>>
	 */
	private function apply_shipping_exclusivity( array $findings ): array {
		$no_methods_open = false;
		foreach ( $findings as $finding ) {
			if ( 'shipping.no_methods_configured' === ( $finding['id'] ?? '' ) && 'open' === ( $finding['status'] ?? '' ) ) {
				$no_methods_open = true;
				break;
			}
		}
		if ( ! $no_methods_open ) {
			return $findings;
		}

		$suppress = array( 'shipping.empty_default_zone', 'shipping.zone_without_methods' );
		foreach ( $findings as $i => $finding ) {
			$id = (string) ( $finding['id'] ?? '' );
			if ( ! in_array( $id, $suppress, true ) ) {
				continue;
			}
			$check = $this->get( $id );
			if ( $check ) {
				$findings[ $i ] = STMC_Finding::passed( $check );
			} else {
				$findings[ $i ]['status']   = 'passed';
				$findings[ $i ]['severity'] = 'passed';
			}
		}

		return $findings;
	}
}
