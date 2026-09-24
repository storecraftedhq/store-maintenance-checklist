<?php
/**
 * Acceptance tests for Phase 3 checks and hybrid catalog runner.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Phase 3 behaviours from the check matrix and phase3 plan.
 */
final class STMC_Phase3_Test extends TestCase {

	/**
	 * Reset stubs before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		stmc_test_reset();
	}

	/**
	 * Open findings use short primary-action labels (not WC breadcrumb paths).
	 */
	public function test_primary_action_labels_are_short_destinations(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'admin_url'            => 'https://example.test/wp-admin/',
					'treat_as_production'  => true,
					'is_ssl'               => false,
					'home_url'             => 'http://example.test',
					'debug_display'        => true,
					'enabled_gateways'     => array(),
					'shipping_zones'       => array(
						array(
							'id'      => 0,
							'name'    => 'Rest of world',
							'default' => true,
							'methods' => array(),
						),
					),
					'email_from'           => 'wordpress@example.test',
					'emails'               => array(
						'new_order'                 => array(
							'enabled'   => false,
							'recipient' => '',
						),
						'customer_processing_order' => array(
							'enabled' => false,
						),
					),
					'stuck_pending_count'  => 2,
					'stuck_on_hold_count'  => 2,
					'stuck_failed_count'   => 2,
					'failed_as_count'      => 1,
					'overdue_as_count'     => 1,
					'hpos_compat_mode'     => true,
					'aged_completed_count' => 10000,
					'aged_cancelled_count' => 10000,
					'wc_pages'             => array(
						'shop'      => 0,
						'cart'      => 0,
						'checkout'  => 0,
						'myaccount' => 0,
					),
					'checkout_page'        => array(
						'id'                 => 0,
						'has_checkout_block' => false,
						'has_shortcode'      => false,
					),
					'policy_pages'         => array(
						'privacy' => array(
							'id'     => 20,
							'status' => 'draft',
							'title'  => 'Privacy Policy',
						),
						'refunds' => array(
							'id'     => 21,
							'status' => 'draft',
							'title'  => 'Refund and Returns Policy',
						),
					),
					'permalink_structure'  => '',
				)
			)
		);

		$expected = array(
			'payments.no_gateways'                 => 'Payments',
			'shipping.no_methods_configured'       => 'Shipping',
			'email.weak_from_address'              => 'Emails',
			'email.new_order_disabled'             => 'Emails',
			'email.customer_processing_disabled'   => 'Emails',
			'email.new_order_recipient_missing'    => 'Emails',
			'orders.stuck_pending'                 => 'Orders',
			'orders.stuck_on_hold'                 => 'Orders',
			'orders.stuck_failed'                  => 'Orders',
			'orders.failed_action_scheduler'       => 'Scheduled Actions',
			'orders.overdue_action_scheduler'      => 'Scheduled Actions',
			'orders.hpos_compat_mode'              => 'Features',
			'orders.aged_completed_volume'         => 'Orders',
			'orders.aged_cancelled_volume'         => 'Orders',
			'catalog.required_pages'               => 'Advanced',
			'catalog.policy_pages_unpublished'     => 'Pages',
			'catalog.checkout_page_signal'         => 'Checkout',
			'environment.https_off_production'     => 'Settings',
			'environment.debug_display_production' => 'Hosting',
			'environment.plain_permalinks'         => 'Permalinks',
			'environment.wc_status_linkout'        => 'Status',
			'environment.site_health_linkout'      => 'Site Health',
		);

		$by_id = $this->index_by_id( $findings );
		foreach ( $expected as $id => $label ) {
			$this->assertArrayHasKey( $id, $by_id, $id );
			$this->assertSame( 'open', $by_id[ $id ]['status'], $id );
			$this->assertSame( $label, $by_id[ $id ]['primary_action']['label'] ?? '', $id );
			$this->assertStringNotContainsString( '→', $by_id[ $id ]['primary_action']['label'] ?? '' );
		}
	}

	/**
	 * Registry exposes all 50 matrix check IDs.
	 */
	public function test_registry_registers_all_fifty_check_ids(): void {
		$registry = new STMC_Checks_Registry();
		$ids      = $registry->ids();

		$this->assertCount( 50, $ids );
		$this->assertContains( 'catalog.missing_price', $ids );
		$this->assertContains( 'catalog.policy_pages_unpublished', $ids );
		$this->assertContains( 'catalog.missing_description', $ids );
		$this->assertContains( 'catalog.missing_short_description', $ids );
		$this->assertContains( 'catalog.unsellable_stock', $ids );
		$this->assertContains( 'catalog.missing_sku', $ids );
		$this->assertContains( 'catalog.duplicate_sku', $ids );
		$this->assertContains( 'catalog.virtual_requires_shipping', $ids );
		$this->assertContains( 'catalog.expired_coupons_enabled', $ids );
		$this->assertContains( 'email.customer_completed_disabled', $ids );
		$this->assertContains( 'email.customer_refunded_disabled', $ids );
		$this->assertContains( 'email.customer_cancelled_disabled', $ids );
		$this->assertContains( 'environment.updates_available', $ids );
		$this->assertContains( 'environment.coming_soon_enabled', $ids );
		$this->assertContains( 'environment.currency_or_timezone_unset', $ids );
		$this->assertContains( 'orders.stuck_failed', $ids );
		$this->assertContains( 'shipping.no_methods_configured', $ids );
		$this->assertContains( 'shipping.no_delivery_eta', $ids );
		$this->assertContains( 'environment.plain_permalinks', $ids );
		$this->assertContains( 'environment.store_address_incomplete', $ids );
		$this->assertContains( 'environment.fatal_error_logs', $ids );
		$this->assertContains( 'environment.php_below_recommended', $ids );
		$this->assertContains( 'environment.wp_below_recommended', $ids );
		$this->assertContains( 'environment.db_below_recommended', $ids );
		$this->assertContains( 'environment.memory_below_recommended', $ids );
		$this->assertContains( 'payments.taxes_enabled_no_rates', $ids );
		$this->assertContains( 'environment.wc_status_linkout', $ids );
		foreach ( $ids as $id ) {
			$this->assertInstanceOf( STMC_Check::class, $registry->get( $id ) );
		}
	}

	/**
	 * When no shipping methods exist anywhere, only no_methods_configured is open.
	 */
	public function test_shipping_exclusivity_suppresses_zone_warnings(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'shipping_zones' => array(
						array(
							'id'      => 0,
							'name'    => 'Locations not covered',
							'default' => true,
							'methods' => array(),
						),
						array(
							'id'      => 1,
							'name'    => 'UK',
							'default' => false,
							'methods' => array(),
						),
					),
				)
			)
		);

		$by_id = $this->index_by_id( $findings );
		$this->assertSame( 'open', $by_id['shipping.no_methods_configured']['status'] );
		$this->assertSame( 'passed', $by_id['shipping.empty_default_zone']['status'] );
		$this->assertSame( 'passed', $by_id['shipping.zone_without_methods']['status'] );
		$this->assertSame( 'passed', $by_id['shipping.no_delivery_eta']['status'] );
	}

	/**
	 * Physical shipping without ETA messaging opens an info finding with Expected Delivery further tool.
	 */
	public function test_shipping_no_delivery_eta_opens_with_expected_delivery_further_tool(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'shipping_zones'          => array(
						array(
							'id'      => 1,
							'name'    => 'UK',
							'default' => false,
							'methods' => array( 'flat_rate' ),
						),
					),
					'has_delivery_eta_signal' => false,
				)
			)
		);
		$by_id    = $this->index_by_id( $findings );
		$this->assertSame( 'open', $by_id['shipping.no_delivery_eta']['status'] );
		$this->assertSame( 'info', $by_id['shipping.no_delivery_eta']['severity'] );
		$this->assertSame(
			'Shipping',
			$by_id['shipping.no_delivery_eta']['primary_action']['label'] ?? ''
		);
		$this->assertStringContainsString(
			'tab=shipping',
			$by_id['shipping.no_delivery_eta']['primary_action']['url'] ?? ''
		);
		$this->assertSame(
			'https://storecrafted.com/product/expected-delivery-times-for-woocommerce/',
			$by_id['shipping.no_delivery_eta']['further_tools'][0]['url'] ?? ''
		);

		$with_signal = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'shipping_zones'          => array(
						array(
							'id'      => 1,
							'name'    => 'UK',
							'default' => false,
							'methods' => array( 'flat_rate' ),
						),
					),
					'has_delivery_eta_signal' => true,
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $with_signal )['shipping.no_delivery_eta']['status']
		);

		$pickup_only = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'shipping_zones'          => array(
						array(
							'id'      => 1,
							'name'    => 'UK',
							'default' => false,
							'methods' => array( 'local_pickup' ),
						),
					),
					'has_delivery_eta_signal' => false,
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $pickup_only )['shipping.no_delivery_eta']['status']
		);
	}

	/**
	 * Plain permalinks open; any non-empty pretty structure passes.
	 */
	public function test_plain_permalinks_opens_and_pretty_structure_passes(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'permalink_structure' => '',
				)
			)
		);
		$by_id    = $this->index_by_id( $open );
		$this->assertSame( 'open', $by_id['environment.plain_permalinks']['status'] );
		$this->assertSame( 'warning', $by_id['environment.plain_permalinks']['severity'] );
		$this->assertSame(
			'Permalinks',
			$by_id['environment.plain_permalinks']['primary_action']['label'] ?? ''
		);
		$this->assertStringContainsString(
			'options-permalink.php',
			$by_id['environment.plain_permalinks']['primary_action']['url'] ?? ''
		);

		$post_name = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'permalink_structure' => '/%postname%/',
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $post_name )['environment.plain_permalinks']['status']
		);

		$day_name = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'permalink_structure' => '/%year%/%monthnum%/%day%/%postname%/',
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $day_name )['environment.plain_permalinks']['status']
		);
	}

	/**
	 * Taxes enabled with zero rates opens; taxes off or rates present passes.
	 */
	public function test_taxes_enabled_no_rates_opens_and_passes_when_configured(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'taxes_enabled'   => true,
					'tax_rates_count' => 0,
				)
			)
		);
		$by_id    = $this->index_by_id( $open );
		$this->assertSame( 'open', $by_id['payments.taxes_enabled_no_rates']['status'] );
		$this->assertSame( 'warning', $by_id['payments.taxes_enabled_no_rates']['severity'] );
		$this->assertSame(
			'Tax',
			$by_id['payments.taxes_enabled_no_rates']['primary_action']['label'] ?? ''
		);
		$this->assertStringContainsString(
			'tab=tax',
			$by_id['payments.taxes_enabled_no_rates']['primary_action']['url'] ?? ''
		);

		$with_rates = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'taxes_enabled'   => true,
					'tax_rates_count' => 2,
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $with_rates )['payments.taxes_enabled_no_rates']['status']
		);

		$taxes_off = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'taxes_enabled'   => false,
					'tax_rates_count' => 0,
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $taxes_off )['payments.taxes_enabled_no_rates']['status']
		);
	}

	/**
	 * Incomplete store address opens; address + city + postcode passes.
	 */
	public function test_store_address_incomplete_opens_and_complete_passes(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'store_address' => array(
						'address_1' => '',
						'city'      => '',
						'postcode'  => '',
					),
				)
			)
		);
		$by_id    = $this->index_by_id( $open );
		$this->assertSame( 'open', $by_id['environment.store_address_incomplete']['status'] );
		$this->assertSame( 'warning', $by_id['environment.store_address_incomplete']['severity'] );
		$this->assertSame(
			'General',
			$by_id['environment.store_address_incomplete']['primary_action']['label'] ?? ''
		);
		$this->assertStringContainsString(
			'tab=general',
			$by_id['environment.store_address_incomplete']['primary_action']['url'] ?? ''
		);

		$partial = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'store_address' => array(
						'address_1' => '123 Main St',
						'city'      => '',
						'postcode'  => '10001',
					),
				)
			)
		);
		$this->assertSame(
			'open',
			$this->index_by_id( $partial )['environment.store_address_incomplete']['status']
		);

		$complete = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'store_address' => array(
						'address_1' => '123 Main St',
						'city'      => 'Exampleville',
						'postcode'  => '10001',
					),
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $complete )['environment.store_address_incomplete']['status']
		);
	}

	/**
	 * Non-empty fatal-errors logs open; zero count passes.
	 */
	public function test_fatal_error_logs_opens_and_zero_passes(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'fatal_error_log_count' => 2,
				)
			)
		);
		$by_id    = $this->index_by_id( $open );
		$this->assertSame( 'open', $by_id['environment.fatal_error_logs']['status'] );
		$this->assertSame( 'warning', $by_id['environment.fatal_error_logs']['severity'] );
		$this->assertSame( 2, $by_id['environment.fatal_error_logs']['evidence']['count'] ?? null );
		$this->assertSame(
			'Logs',
			$by_id['environment.fatal_error_logs']['primary_action']['label'] ?? ''
		);
		$this->assertStringContainsString(
			'page=wc-status&tab=logs',
			$by_id['environment.fatal_error_logs']['primary_action']['url'] ?? ''
		);

		$passed = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'fatal_error_log_count' => 0,
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $passed )['environment.fatal_error_logs']['status']
		);
	}

	/**
	 * WooCommerce server recommendation checks open below floors and pass at/above.
	 */
	public function test_server_recommendation_checks_open_and_pass(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'php_version'           => '8.2.0',
					'wp_version'            => '6.8.0',
					'db_server_type'        => 'mysql',
					'db_server_version'     => '5.7.40',
					'wp_memory_limit_bytes' => 128 * 1024 * 1024,
				)
			)
		);
		$by_id    = $this->index_by_id( $open );

		$this->assertSame( 'open', $by_id['environment.php_below_recommended']['status'] );
		$this->assertSame( 'warning', $by_id['environment.php_below_recommended']['severity'] );
		$this->assertStringContainsString(
			'page=wc-status',
			$by_id['environment.php_below_recommended']['primary_action']['url'] ?? ''
		);

		$this->assertSame( 'open', $by_id['environment.wp_below_recommended']['status'] );
		$this->assertSame(
			'Updates',
			$by_id['environment.wp_below_recommended']['primary_action']['label'] ?? ''
		);
		$this->assertStringContainsString(
			'update-core.php',
			$by_id['environment.wp_below_recommended']['primary_action']['url'] ?? ''
		);

		$this->assertSame( 'open', $by_id['environment.db_below_recommended']['status'] );
		$this->assertSame( 'open', $by_id['environment.memory_below_recommended']['status'] );
		$this->assertSame(
			'Guide',
			$by_id['environment.memory_below_recommended']['primary_action']['label'] ?? ''
		);
		$this->assertSame(
			'https://woocommerce.com/document/increasing-the-wordpress-memory-limit/',
			$by_id['environment.memory_below_recommended']['primary_action']['url'] ?? ''
		);
		$this->assertTrue(
			(bool) ( $by_id['environment.memory_below_recommended']['primary_action']['external'] ?? false )
		);

		$mariadb = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'db_server_type'    => 'mariadb',
					'db_server_version' => '10.5.0',
				)
			)
		);
		$this->assertSame(
			'open',
			$this->index_by_id( $mariadb )['environment.db_below_recommended']['status']
		);

		$passed  = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'php_version'           => '8.3.0',
					'wp_version'            => '6.9',
					'db_server_type'        => 'mysql',
					'db_server_version'     => '8.0.0',
					'wp_memory_limit_bytes' => 256 * 1024 * 1024,
				)
			)
		);
		$pass_by = $this->index_by_id( $passed );
		$this->assertSame( 'passed', $pass_by['environment.php_below_recommended']['status'] );
		$this->assertSame( 'passed', $pass_by['environment.wp_below_recommended']['status'] );
		$this->assertSame( 'passed', $pass_by['environment.db_below_recommended']['status'] );
		$this->assertSame( 'passed', $pass_by['environment.memory_below_recommended']['status'] );

		$unknown_db = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'db_server_type'    => '',
					'db_server_version' => '',
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $unknown_db )['environment.db_below_recommended']['status']
		);
	}

	/**
	 * HTTPS check is passed when not treating as production.
	 */
	public function test_https_check_skipped_when_not_treat_as_production(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'treat_as_production' => false,
					'is_ssl'              => false,
					'home_url'            => 'http://example.com',
					'debug_display'       => true,
				)
			)
		);

		$by_id = $this->index_by_id( $findings );
		$this->assertSame( 'passed', $by_id['environment.https_off_production']['status'] );
		$this->assertSame( 'passed', $by_id['environment.debug_display_production']['status'] );
	}

	/**
	 * Link-out checks are always open info and score-excluded.
	 */
	public function test_linkout_checks_appear_score_excluded(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync( STMC_Check_Snapshot::from_array( array() ) );
		$by_id    = $this->index_by_id( $findings );

		foreach ( array( 'environment.site_health_linkout', 'environment.wc_status_linkout' ) as $id ) {
			$this->assertSame( 'open', $by_id[ $id ]['status'] );
			$this->assertSame( 'info', $by_id[ $id ]['severity'] );
			$this->assertTrue( $by_id[ $id ]['score_excluded'] );
		}
	}

	/**
	 * No enabled payment gateways is critical.
	 */
	public function test_payments_no_gateways_critical(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'enabled_gateways' => array(),
				)
			)
		);
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'open', $by_id['payments.no_gateways']['status'] );
		$this->assertSame( 'critical', $by_id['payments.no_gateways']['severity'] );

		$ok = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'enabled_gateways' => array(
						array(
							'id'        => 'bacs',
							'title'     => 'BACS',
							'test_mode' => false,
						),
					),
				)
			)
		);
		$this->assertSame( 'passed', $this->index_by_id( $ok )['payments.no_gateways']['status'] );
	}

	/**
	 * Weak From address and disabled order emails open as expected.
	 */
	public function test_email_weak_from_and_disabled(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'email_from' => 'wordpress@example.com',
					'emails'     => array(
						'customer_processing_order' => array(
							'enabled'   => false,
							'recipient' => '',
						),
						'new_order'                 => array(
							'enabled'   => false,
							'recipient' => '',
						),
					),
				)
			)
		);
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'open', $by_id['email.weak_from_address']['status'] );
		$this->assertSame( 'info', $by_id['email.weak_from_address']['severity'] );
		$this->assertSame( 'open', $by_id['email.customer_processing_disabled']['status'] );
		$this->assertSame( 'open', $by_id['email.new_order_disabled']['status'] );
		$this->assertSame( 'open', $by_id['email.new_order_recipient_missing']['status'] );
	}

	/**
	 * Missing required WC pages is critical.
	 */
	public function test_catalog_pages_missing(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'wc_pages'      => array(
						'shop'      => 0,
						'cart'      => 0,
						'checkout'  => 0,
						'myaccount' => 0,
					),
					'checkout_page' => array(
						'id'                 => 0,
						'has_checkout_block' => false,
						'has_shortcode'      => false,
					),
				)
			)
		);
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'open', $by_id['catalog.required_pages']['status'] );
		$this->assertSame( 'critical', $by_id['catalog.required_pages']['severity'] );
		$this->assertSame( 'open', $by_id['catalog.checkout_page_signal']['status'] );
	}

	/**
	 * Assigned Privacy / Refunds pages that are not published open as warning.
	 */
	public function test_policy_pages_unpublished_warning(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'admin_url'    => 'https://example.test/wp-admin/',
					'policy_pages' => array(
						'privacy' => array(
							'id'     => 20,
							'status' => 'draft',
							'title'  => 'Privacy Policy',
						),
						'refunds' => array(
							'id'     => 21,
							'status' => 'publish',
							'title'  => 'Refund and Returns Policy',
						),
					),
				)
			)
		);
		$by_id    = $this->index_by_id( $open );
		$finding  = $by_id['catalog.policy_pages_unpublished'];

		$this->assertSame( 'open', $finding['status'] );
		$this->assertSame( 'warning', $finding['severity'] );
		$this->assertStringContainsString( 'Privacy Policy', $finding['evidence']['summary'] );
		$this->assertSame( '#20', $finding['evidence']['samples'][0]['label'] );
		$this->assertStringContainsString( 'post=20', $finding['evidence']['samples'][0]['url'] );
		$this->assertStringContainsString( 'edit.php?post_type=page', $finding['primary_action']['url'] );

		$pass = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'policy_pages' => array(
						'privacy' => array(
							'id'     => 20,
							'status' => 'publish',
							'title'  => 'Privacy Policy',
						),
						'refunds' => array(
							'id'     => 0,
							'status' => '',
							'title'  => '',
						),
					),
				)
			)
		);
		$this->assertSame(
			'passed',
			$this->index_by_id( $pass )['catalog.policy_pages_unpublished']['status']
		);
	}

	/**
	 * Order / AS / HPOS / aged thresholds via snapshot.
	 */
	public function test_orders_thresholds(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'stuck_pending_count'  => 3,
					'stuck_on_hold_count'  => 2,
					'stuck_failed_count'   => 4,
					'failed_as_count'      => 1,
					'overdue_as_count'     => 4,
					'hpos_compat_mode'     => true,
					'aged_completed_count' => 10000,
					'aged_cancelled_count' => 5000,
				)
			)
		);
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'open', $by_id['orders.stuck_pending']['status'] );
		$this->assertStringContainsString(
			'page=wc-orders',
			$by_id['orders.stuck_pending']['primary_action']['url']
		);
		$this->assertStringContainsString(
			'status=wc-pending',
			$by_id['orders.stuck_pending']['primary_action']['url']
		);
		$this->assertSame( 'open', $by_id['orders.stuck_on_hold']['status'] );
		$this->assertSame( 'open', $by_id['orders.stuck_failed']['status'] );
		$this->assertSame( 'warning', $by_id['orders.stuck_failed']['severity'] );
		$this->assertStringContainsString(
			'status=wc-failed',
			$by_id['orders.stuck_failed']['primary_action']['url']
		);
		$this->assertSame( 'open', $by_id['orders.failed_action_scheduler']['status'] );
		$this->assertStringContainsString(
			'status=failed',
			$by_id['orders.failed_action_scheduler']['primary_action']['url']
		);
		$this->assertSame( 'open', $by_id['orders.overdue_action_scheduler']['status'] );
		$this->assertStringContainsString(
			'status=past-due',
			$by_id['orders.overdue_action_scheduler']['primary_action']['url']
		);
		$this->assertSame( 'open', $by_id['orders.hpos_compat_mode']['status'] );
		$this->assertSame( 'open', $by_id['orders.aged_completed_volume']['status'] );
		$this->assertSame( 'open', $by_id['orders.aged_cancelled_volume']['status'] );
		$archive_url = 'https://storecrafted.com/product/auto-archive-old-orders-for-woocommerce/';
		$this->assertSame(
			$archive_url,
			$by_id['orders.aged_completed_volume']['further_tools'][0]['url'] ?? ''
		);
		$this->assertSame(
			$archive_url,
			$by_id['orders.aged_cancelled_volume']['further_tools'][0]['url'] ?? ''
		);

		$on_hold = $by_id['orders.stuck_on_hold'];
		$this->assertSame( 2, $on_hold['evidence']['count'] );
		$this->assertSame(
			'on-hold orders older than 7 days.',
			$on_hold['evidence']['summary']
		);
		$this->assertStringContainsString(
			'page=wc-orders',
			$on_hold['primary_action']['url']
		);
		$this->assertStringContainsString(
			'status=wc-on-hold',
			$on_hold['primary_action']['url']
		);

		$with_samples   = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'stuck_on_hold_count'   => 7,
					'stuck_on_hold_samples' => array( 101, 102, 103 ),
					'admin_url'             => 'https://example.test/wp-admin/',
				)
			)
		);
		$sample_finding = $this->index_by_id( $with_samples )['orders.stuck_on_hold'];
		$this->assertSame( '#101', $sample_finding['evidence']['samples'][0]['label'] );
		$this->assertStringContainsString(
			'id=101',
			$sample_finding['evidence']['samples'][0]['url']
		);

		$ok    = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'stuck_pending_count'  => 0,
					'stuck_on_hold_count'  => 0,
					'stuck_failed_count'   => 0,
					'failed_as_count'      => 0,
					'overdue_as_count'     => 0,
					'hpos_compat_mode'     => false,
					'aged_completed_count' => 9999,
					'aged_cancelled_count' => 4999,
				)
			)
		);
		$ok_by = $this->index_by_id( $ok );
		$this->assertSame( 'passed', $ok_by['orders.stuck_pending']['status'] );
		$this->assertSame( 'passed', $ok_by['orders.stuck_on_hold']['status'] );
		$this->assertSame( 'passed', $ok_by['orders.stuck_failed']['status'] );
		$this->assertSame( 'passed', $ok_by['orders.aged_completed_volume']['status'] );
		$this->assertSame( 'passed', $ok_by['orders.aged_cancelled_volume']['status'] );
		$this->assertSame( 'passed', $ok_by['orders.hpos_compat_mode']['status'] );
	}

	/**
	 * External products are skipped for missing_price.
	 */
	public function test_catalog_missing_price_skips_external(): void {
		$registry = new STMC_Checks_Registry();
		$acc      = $registry->empty_catalog_accumulators();
		$registry->accumulate_products(
			$acc,
			array(
				array(
					'id'            => 1,
					'type'          => 'external',
					'price'         => '',
					'regular_price' => '',
					'image_id'      => 10,
					'downloadable'  => false,
					'has_downloads' => false,
					'is_external'   => true,
					'name'          => 'Affiliate',
				),
				array(
					'id'            => 2,
					'type'          => 'simple',
					'price'         => '',
					'regular_price' => '',
					'image_id'      => 0,
					'downloadable'  => true,
					'has_downloads' => false,
					'is_external'   => false,
					'name'          => 'Broken download',
				),
			)
		);
		$findings = $registry->finalize_catalog_findings( $acc );
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'open', $by_id['catalog.missing_price']['status'] );
		$this->assertSame( 1, $by_id['catalog.missing_price']['evidence']['count'] );
		$this->assertSame( 'open', $by_id['catalog.missing_featured_image']['status'] );
		$this->assertSame( 'open', $by_id['catalog.downloadable_missing_file']['status'] );
	}

	/**
	 * Empty / markup-only descriptions open; filled descriptions pass.
	 */
	public function test_catalog_missing_description_and_short_description(): void {
		$registry = new STMC_Checks_Registry();
		$acc      = $registry->empty_catalog_accumulators();
		$registry->accumulate_products(
			$acc,
			array(
				array(
					'id'                => 1,
					'type'              => 'simple',
					'price'             => '10',
					'regular_price'     => '10',
					'image_id'          => 1,
					'downloadable'      => false,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'Empty both',
					'description'       => '',
					'short_description' => '<p></p>',
				),
				array(
					'id'                => 2,
					'type'              => 'simple',
					'price'             => '10',
					'regular_price'     => '10',
					'image_id'          => 1,
					'downloadable'      => false,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'Complete',
					'description'       => '<p>Full product story.</p>',
					'short_description' => 'Short blurb',
				),
			)
		);
		$findings = $registry->finalize_catalog_findings( $acc );
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'open', $by_id['catalog.missing_description']['status'] );
		$this->assertSame( 'info', $by_id['catalog.missing_description']['severity'] );
		$this->assertSame( 1, $by_id['catalog.missing_description']['evidence']['count'] );
		$this->assertSame(
			'Empty both',
			$by_id['catalog.missing_description']['evidence']['samples'][0]['label'] ?? ''
		);
		$this->assertStringContainsString(
			'post=1',
			$by_id['catalog.missing_description']['evidence']['samples'][0]['url'] ?? ''
		);
		$this->assertSame( 'open', $by_id['catalog.missing_short_description']['status'] );
		$this->assertSame( 1, $by_id['catalog.missing_short_description']['evidence']['count'] );
		$this->assertStringContainsString(
			'post=1',
			$by_id['catalog.missing_short_description']['evidence']['samples'][0]['url'] ?? ''
		);

		$pass_acc = $registry->empty_catalog_accumulators();
		$registry->accumulate_products(
			$pass_acc,
			array(
				array(
					'id'                => 3,
					'type'              => 'simple',
					'price'             => '10',
					'regular_price'     => '10',
					'image_id'          => 1,
					'downloadable'      => false,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'OK',
					'description'       => 'Long form copy',
					'short_description' => 'Teaser',
				),
			)
		);
		$pass_by = $this->index_by_id( $registry->finalize_catalog_findings( $pass_acc ) );
		$this->assertSame( 'passed', $pass_by['catalog.missing_description']['status'] );
		$this->assertSame( 'passed', $pass_by['catalog.missing_short_description']['status'] );
	}

	/**
	 * Stock, SKU, and virtual/shipping catalog checks open and pass as expected.
	 */
	public function test_catalog_stock_sku_and_virtual_shipping_checks(): void {
		$registry = new STMC_Checks_Registry();
		$acc      = $registry->empty_catalog_accumulators();
		$registry->accumulate_products(
			$acc,
			array(
				array(
					'id'                => 1,
					'type'              => 'simple',
					'price'             => '10',
					'regular_price'     => '10',
					'image_id'          => 1,
					'downloadable'      => false,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'OOS no SKU virtual ship',
					'description'       => 'Desc',
					'short_description' => 'Short',
					'sku'               => '',
					'stock_status'      => 'outofstock',
					'is_virtual'        => true,
					'needs_shipping'    => true,
				),
				array(
					'id'                => 2,
					'type'              => 'simple',
					'price'             => '10',
					'regular_price'     => '10',
					'image_id'          => 1,
					'downloadable'      => false,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'Dup A',
					'description'       => 'Desc',
					'short_description' => 'Short',
					'sku'               => 'DUP-1',
					'stock_status'      => 'instock',
					'is_virtual'        => false,
					'needs_shipping'    => true,
				),
			)
		);
		$registry->accumulate_variations(
			$acc,
			array(
				array(
					'id'            => 3,
					'parent_id'     => 99,
					'price'         => '8',
					'regular_price' => '8',
					'name'          => 'Dup var',
					'sku'           => 'DUP-1',
				),
			)
		);
		$by_id = $this->index_by_id( $registry->finalize_catalog_findings( $acc ) );

		$this->assertSame( 'open', $by_id['catalog.unsellable_stock']['status'] );
		$this->assertSame( 'warning', $by_id['catalog.unsellable_stock']['severity'] );
		$this->assertSame( 'open', $by_id['catalog.missing_sku']['status'] );
		$this->assertSame( 'info', $by_id['catalog.missing_sku']['severity'] );
		$this->assertSame( 'open', $by_id['catalog.duplicate_sku']['status'] );
		$this->assertSame( 1, $by_id['catalog.duplicate_sku']['evidence']['count'] );
		$this->assertSame( 'open', $by_id['catalog.virtual_requires_shipping']['status'] );

		$ok = $registry->empty_catalog_accumulators();
		$registry->accumulate_products(
			$ok,
			array(
				array(
					'id'                => 10,
					'type'              => 'simple',
					'price'             => '10',
					'regular_price'     => '10',
					'image_id'          => 1,
					'downloadable'      => false,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'Healthy',
					'description'       => 'Desc',
					'short_description' => 'Short',
					'sku'               => 'OK-1',
					'stock_status'      => 'instock',
					'is_virtual'        => true,
					'needs_shipping'    => false,
				),
			)
		);
		$ok_by = $this->index_by_id( $registry->finalize_catalog_findings( $ok ) );
		$this->assertSame( 'passed', $ok_by['catalog.unsellable_stock']['status'] );
		$this->assertSame( 'passed', $ok_by['catalog.missing_sku']['status'] );
		$this->assertSame( 'passed', $ok_by['catalog.duplicate_sku']['status'] );
		$this->assertSame( 'passed', $ok_by['catalog.virtual_requires_shipping']['status'] );
	}

	/**
	 * New email and environment sync checks open and pass.
	 */
	public function test_email_and_environment_ops_checks_open_and_pass(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'emails'               => array(
						'customer_completed_order' => array( 'enabled' => false ),
						'customer_refunded_order'  => array( 'enabled' => false ),
						'customer_cancelled_order' => array( 'enabled' => false ),
					),
					'wp_update_available'  => true,
					'wc_update_available'  => false,
					'coming_soon_enabled'  => true,
					'woocommerce_currency' => '',
					'timezone_string'      => '',
					'gmt_offset'           => '',
				)
			)
		);
		$by_id    = $this->index_by_id( $open );
		$this->assertSame( 'open', $by_id['email.customer_completed_disabled']['status'] );
		$this->assertSame( 'warning', $by_id['email.customer_completed_disabled']['severity'] );
		$this->assertSame( 'open', $by_id['email.customer_refunded_disabled']['status'] );
		$this->assertSame( 'info', $by_id['email.customer_refunded_disabled']['severity'] );
		$this->assertSame( 'open', $by_id['email.customer_cancelled_disabled']['status'] );
		$this->assertSame( 'open', $by_id['environment.updates_available']['status'] );
		$this->assertSame( 'open', $by_id['environment.coming_soon_enabled']['status'] );
		$this->assertSame( 'critical', $by_id['environment.coming_soon_enabled']['severity'] );
		$this->assertSame( 'open', $by_id['environment.currency_or_timezone_unset']['status'] );

		$passed  = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'emails'               => array(
						'customer_completed_order' => array( 'enabled' => true ),
						'customer_refunded_order'  => array( 'enabled' => true ),
						'customer_cancelled_order' => array( 'enabled' => true ),
					),
					'wp_update_available'  => false,
					'wc_update_available'  => false,
					'coming_soon_enabled'  => false,
					'woocommerce_currency' => 'USD',
					'timezone_string'      => '',
					'gmt_offset'           => '0',
				)
			)
		);
		$pass_by = $this->index_by_id( $passed );
		$this->assertSame( 'passed', $pass_by['email.customer_completed_disabled']['status'] );
		$this->assertSame( 'passed', $pass_by['email.customer_refunded_disabled']['status'] );
		$this->assertSame( 'passed', $pass_by['email.customer_cancelled_disabled']['status'] );
		$this->assertSame( 'passed', $pass_by['environment.updates_available']['status'] );
		$this->assertSame( 'passed', $pass_by['environment.coming_soon_enabled']['status'] );
		$this->assertSame( 'passed', $pass_by['environment.currency_or_timezone_unset']['status'] );
	}

	/**
	 * Expired published coupons open as info; none open when count is zero.
	 */
	public function test_expired_coupons_enabled_opens_and_passes(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'expired_enabled_coupon_count'   => 2,
					'expired_enabled_coupon_samples' => array( 11, 12 ),
				)
			)
		);
		$by_id    = $this->index_by_id( $open );
		$this->assertSame( 'open', $by_id['catalog.expired_coupons_enabled']['status'] );
		$this->assertSame( 'info', $by_id['catalog.expired_coupons_enabled']['severity'] );
		$this->assertSame( 2, $by_id['catalog.expired_coupons_enabled']['evidence']['count'] );
		$this->assertNotEmpty( $by_id['catalog.expired_coupons_enabled']['evidence']['samples'] );

		$passed  = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'expired_enabled_coupon_count'   => 0,
					'expired_enabled_coupon_samples' => array(),
				)
			)
		);
		$pass_by = $this->index_by_id( $passed );
		$this->assertSame( 'passed', $pass_by['catalog.expired_coupons_enabled']['status'] );
	}

	/**
	 * Status polls drain pending Action Scheduler work so scans progress without WP-Cron.
	 */
	public function test_status_poll_drains_pending_catalog_batches(): void {
		$products = array();
		for ( $i = 1; $i <= 150; $i++ ) {
			$products[] = array(
				'id'            => $i,
				'type'          => 'simple',
				'price'         => '10',
				'regular_price' => '10',
				'image_id'      => 1,
				'downloadable'  => false,
				'has_downloads' => false,
				'is_external'   => false,
				'name'          => 'P' . $i,
			);
		}

		$options = new STMC_Options();
		$options->update_settings(
			array(
				'max_products'   => 150,
				'max_variations' => 100,
			)
		);
		$registry  = new STMC_Checks_Registry();
		$catalog   = new STMC_Catalog_Source_Memory( $products, array() );
		$scheduler = new STMC_Scan_Scheduler( $registry, $catalog );
		$engine    = new STMC_Scan_Engine( $options, new STMC_Score(), $scheduler, $registry, $catalog );

		$started = $engine->start();
		$this->assertIsArray( $started );
		$this->assertSame( 'running', $started['status']['state'] );
		$this->assertSame( 10, $started['status']['progress_percent'] );
		$this->assertNotEmpty( $GLOBALS['stmc_test_as'] );

		// Simulate admin polling — do not call drain_as_queue().
		$status = null;
		for ( $i = 0; $i < 20; $i++ ) {
			$status = $engine->get_status();
			if ( 'running' !== ( $status['state'] ?? '' ) ) {
				break;
			}
		}

		$this->assertSame( 'completed', $status['state'] ?? '' );
		$latest = $options->get_latest_results();
		$this->assertSame( 'completed', $latest['status']['state'] ?? '' );
		$this->assertSame( array(), $GLOBALS['stmc_test_as'] );
	}

	/**
	 * Catalog bound hit before all products marks provisional.
	 */
	public function test_partial_catalog_sets_provisional(): void {
		$products = array();
		for ( $i = 1; $i <= 250; $i++ ) {
			$products[] = array(
				'id'            => $i,
				'type'          => 'simple',
				'price'         => '10',
				'regular_price' => '10',
				'image_id'      => 1,
				'downloadable'  => false,
				'has_downloads' => false,
				'is_external'   => false,
				'name'          => 'P' . $i,
			);
		}

		$options = new STMC_Options();
		$options->update_settings( array( 'max_products' => 100 ) );

		$registry  = new STMC_Checks_Registry();
		$catalog   = new STMC_Catalog_Source_Memory( $products, array() );
		$scheduler = new STMC_Scan_Scheduler( $registry, $catalog );
		$engine    = new STMC_Scan_Engine( $options, new STMC_Score(), $scheduler, $registry, $catalog );

		$started = $engine->start();
		$this->assertIsArray( $started );
		$this->assertSame( 'running', $started['status']['state'] );

		// Drain AS queue (catalog batches then finalize).
		$this->drain_as_queue( $scheduler );

		$latest = $options->get_latest_results();
		$this->assertSame( 'completed', $latest['status']['state'] );
		$this->assertTrue( $latest['status']['provisional'] );
		$this->assertTrue( $latest['status']['partial_catalog'] );
		$this->assertTrue( $latest['summary']['provisional'] );
	}

	/**
	 * Cancel unschedules all stmc AS hooks.
	 */
	public function test_cancel_clears_as_actions(): void {
		$products = array();
		for ( $i = 1; $i <= 50; $i++ ) {
			$products[] = array(
				'id'            => $i,
				'type'          => 'simple',
				'price'         => '5',
				'regular_price' => '5',
				'image_id'      => 1,
				'downloadable'  => false,
				'has_downloads' => false,
				'is_external'   => false,
				'name'          => 'P' . $i,
			);
		}

		$registry  = new STMC_Checks_Registry();
		$catalog   = new STMC_Catalog_Source_Memory( $products, array() );
		$scheduler = new STMC_Scan_Scheduler( $registry, $catalog );
		$engine    = new STMC_Scan_Engine( new STMC_Options(), new STMC_Score(), $scheduler, $registry, $catalog );

		$engine->start();
		$this->assertNotEmpty( $GLOBALS['stmc_test_as'] );

		$engine->cancel();
		$this->assertSame( array(), $GLOBALS['stmc_test_as'] );
	}

	/**
	 * Each check ID produces open on a fail snapshot and passed on a clean one.
	 *
	 * @dataProvider provider_check_ids
	 * @param string $id Check id.
	 */
	public function test_each_check_id_pass_and_fail( string $id ): void {
		$registry = new STMC_Checks_Registry();
		$fail     = $this->index_by_id( $this->run_all_for_snapshot( $registry, $this->fail_snapshot_for( $id ) ) );
		$pass     = $this->index_by_id( $this->run_all_for_snapshot( $registry, $this->pass_snapshot() ) );

		$this->assertArrayHasKey( $id, $fail );
		$this->assertArrayHasKey( $id, $pass );

		if ( in_array( $id, array( 'environment.site_health_linkout', 'environment.wc_status_linkout' ), true ) ) {
			$this->assertSame( 'open', $fail[ $id ]['status'] );
			$this->assertSame( 'open', $pass[ $id ]['status'] );
			$this->assertTrue( $fail[ $id ]['score_excluded'] );
			return;
		}

		$this->assertSame( 'open', $fail[ $id ]['status'], $id . ' should be open on fail fixture' );
		$this->assertSame( 'passed', $pass[ $id ]['status'], $id . ' should be passed on clean fixture' );
	}

	/**
	 * @return array<int, array{0: string}>
	 */
	public static function provider_check_ids(): array {
		$ids = ( new STMC_Checks_Registry() )->ids();
		$out = array();
		foreach ( $ids as $id ) {
			$out[] = array( $id );
		}
		return $out;
	}

	/**
	 * Gateway test mode only when reliably true.
	 */
	public function test_gateway_test_mode_only_when_detectable(): void {
		$registry = new STMC_Checks_Registry();
		$open     = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'enabled_gateways' => array(
						array(
							'id'        => 'stripe',
							'title'     => 'Stripe',
							'test_mode' => true,
						),
					),
				)
			)
		);
		$this->assertSame( 'open', $this->index_by_id( $open )['payments.gateway_test_mode']['status'] );

		$unknown = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'enabled_gateways' => array(
						array(
							'id'        => 'mystery',
							'title'     => 'Mystery',
							'test_mode' => null,
						),
					),
				)
			)
		);
		$this->assertSame( 'passed', $this->index_by_id( $unknown )['payments.gateway_test_mode']['status'] );
	}

	/**
	 * Shipping zone warnings fire when some methods exist elsewhere.
	 */
	public function test_shipping_zone_warnings_when_methods_exist(): void {
		$registry = new STMC_Checks_Registry();
		$findings = $registry->run_sync(
			STMC_Check_Snapshot::from_array(
				array(
					'shipping_zones'          => array(
						array(
							'id'      => 0,
							'name'    => 'Locations not covered',
							'default' => true,
							'methods' => array(),
						),
						array(
							'id'      => 1,
							'name'    => 'UK',
							'default' => false,
							'methods' => array( 'flat_rate' ),
						),
						array(
							'id'      => 2,
							'name'    => 'EU',
							'default' => false,
							'methods' => array(),
						),
					),
					'has_delivery_eta_signal' => true,
				)
			)
		);
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'passed', $by_id['shipping.no_methods_configured']['status'] );
		$this->assertSame( 'open', $by_id['shipping.empty_default_zone']['status'] );
		$this->assertSame( 'open', $by_id['shipping.zone_without_methods']['status'] );
	}

	/**
	 * Incomplete variation prices accumulate across batches.
	 */
	public function test_incomplete_variation_prices_accumulate(): void {
		$registry = new STMC_Checks_Registry();
		$acc      = $registry->empty_catalog_accumulators();
		$registry->accumulate_variations(
			$acc,
			array(
				array(
					'id'            => 10,
					'parent_id'     => 1,
					'price'         => '',
					'regular_price' => '',
					'name'          => 'Red',
				),
				array(
					'id'            => 11,
					'parent_id'     => 1,
					'price'         => '9',
					'regular_price' => '9',
					'name'          => 'Blue',
				),
			)
		);
		$registry->accumulate_variations(
			$acc,
			array(
				array(
					'id'            => 20,
					'parent_id'     => 2,
					'price'         => '',
					'regular_price' => '',
					'name'          => 'S',
				),
			)
		);
		$findings = $registry->finalize_catalog_findings( $acc );
		$by_id    = $this->index_by_id( $findings );

		$this->assertSame( 'open', $by_id['catalog.incomplete_variation_prices']['status'] );
		$this->assertSame( 2, $by_id['catalog.incomplete_variation_prices']['evidence']['count'] );
	}

	/**
	 * @param array<int, array<string, mixed>> $findings Findings.
	 * @return array<string, array<string, mixed>>
	 */
	private function index_by_id( array $findings ): array {
		$out = array();
		foreach ( $findings as $finding ) {
			$out[ (string) $finding['id'] ] = $finding;
		}
		return $out;
	}

	/**
	 * Run sync + empty catalog finalize for a full findings set.
	 *
	 * @param STMC_Checks_Registry $registry Registry.
	 * @param STMC_Check_Snapshot  $snapshot Snapshot.
	 * @return array<int, array<string, mixed>>
	 */
	private function run_all_for_snapshot( STMC_Checks_Registry $registry, STMC_Check_Snapshot $snapshot ): array {
		$sync = $registry->run_sync( $snapshot );
		$acc  = $registry->empty_catalog_accumulators();

		if ( ! empty( $snapshot->catalog_products ) ) {
			$registry->accumulate_products( $acc, $snapshot->catalog_products );
		}
		if ( ! empty( $snapshot->catalog_variations ) ) {
			$registry->accumulate_variations( $acc, $snapshot->catalog_variations );
		}

		return array_merge( $sync, $registry->finalize_catalog_findings( $acc ) );
	}

	/**
	 * Fail fixture tailored so mutually exclusive checks can still open.
	 *
	 * @param string $id Check id under test.
	 */
	private function fail_snapshot_for( string $id ): STMC_Check_Snapshot {
		$base = array(
			'treat_as_production'            => true,
			'is_ssl'                         => false,
			'home_url'                       => 'http://example.com',
			'debug_display'                  => true,
			'enabled_gateways'               => array(),
			'shipping_zones'                 => array(
				array(
					'id'      => 0,
					'name'    => 'Locations not covered',
					'default' => true,
					'methods' => array(),
				),
			),
			'email_from'                     => 'wordpress@localhost',
			'emails'                         => array(
				'customer_processing_order' => array(
					'enabled'   => false,
					'recipient' => '',
				),
				'customer_completed_order'  => array(
					'enabled'   => false,
					'recipient' => '',
				),
				'customer_refunded_order'   => array(
					'enabled'   => false,
					'recipient' => '',
				),
				'customer_cancelled_order'  => array(
					'enabled'   => false,
					'recipient' => '',
				),
				'new_order'                 => array(
					'enabled'   => false,
					'recipient' => '',
				),
			),
			'wc_pages'                       => array(
				'shop'      => 0,
				'cart'      => 0,
				'checkout'  => 0,
				'myaccount' => 0,
			),
			'checkout_page'                  => array(
				'id'                 => 0,
				'has_checkout_block' => false,
				'has_shortcode'      => false,
			),
			'policy_pages'                   => array(
				'privacy' => array(
					'id'     => 20,
					'status' => 'draft',
					'title'  => 'Privacy Policy',
				),
				'refunds' => array(
					'id'     => 21,
					'status' => 'draft',
					'title'  => 'Refund and Returns Policy',
				),
			),
			'stuck_pending_count'            => 1,
			'stuck_on_hold_count'            => 1,
			'stuck_failed_count'             => 1,
			'failed_as_count'                => 1,
			'overdue_as_count'               => 1,
			'hpos_compat_mode'               => true,
			'aged_completed_count'           => 10000,
			'aged_cancelled_count'           => 5000,
			'permalink_structure'            => '',
			'taxes_enabled'                  => true,
			'tax_rates_count'                => 0,
			'store_address'                  => array(
				'address_1' => '',
				'city'      => '',
				'postcode'  => '',
			),
			'fatal_error_log_count'          => 1,
			'php_version'                    => '8.1.0',
			'wp_version'                     => '6.7.0',
			'db_server_type'                 => 'mysql',
			'db_server_version'              => '5.7.0',
			'wp_memory_limit_bytes'          => 64 * 1024 * 1024,
			'wp_update_available'            => true,
			'wc_update_available'            => true,
			'coming_soon_enabled'            => true,
			'woocommerce_currency'           => '',
			'timezone_string'                => '',
			'gmt_offset'                     => '',
			'expired_enabled_coupon_count'   => 2,
			'expired_enabled_coupon_samples' => array( 11, 12 ),
			'catalog_products'               => array(
				array(
					'id'                => 1,
					'type'              => 'simple',
					'price'             => '',
					'regular_price'     => '',
					'image_id'          => 0,
					'downloadable'      => true,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'Bad',
					'description'       => '',
					'short_description' => '',
					'sku'               => '',
					'stock_status'      => 'outofstock',
					'is_virtual'        => true,
					'needs_shipping'    => true,
				),
				array(
					'id'                => 4,
					'type'              => 'simple',
					'price'             => '1',
					'regular_price'     => '1',
					'image_id'          => 1,
					'downloadable'      => false,
					'has_downloads'     => false,
					'is_external'       => false,
					'name'              => 'Dup parent',
					'description'       => 'x',
					'short_description' => 'y',
					'sku'               => 'DUP',
					'stock_status'      => 'instock',
					'is_virtual'        => false,
					'needs_shipping'    => true,
				),
			),
			'catalog_variations'             => array(
				array(
					'id'            => 2,
					'parent_id'     => 99,
					'price'         => '',
					'regular_price' => '',
					'name'          => 'Var',
					'sku'           => 'DUP',
				),
			),
		);

		if ( 'payments.gateway_test_mode' === $id ) {
			$base['enabled_gateways'] = array(
				array(
					'id'        => 'stripe',
					'title'     => 'Stripe',
					'test_mode' => true,
				),
			);
		}

		if ( in_array( $id, array( 'shipping.empty_default_zone', 'shipping.zone_without_methods' ), true ) ) {
			$base['shipping_zones']          = array(
				array(
					'id'      => 0,
					'name'    => 'Locations not covered',
					'default' => true,
					'methods' => array(),
				),
				array(
					'id'      => 1,
					'name'    => 'UK',
					'default' => false,
					'methods' => array( 'flat_rate' ),
				),
				array(
					'id'      => 2,
					'name'    => 'EU',
					'default' => false,
					'methods' => array(),
				),
			);
			$base['has_delivery_eta_signal'] = true;
		}

		if ( 'shipping.no_delivery_eta' === $id ) {
			$base['shipping_zones']          = array(
				array(
					'id'      => 1,
					'name'    => 'UK',
					'default' => false,
					'methods' => array( 'flat_rate' ),
				),
			);
			$base['has_delivery_eta_signal'] = false;
		}

		return STMC_Check_Snapshot::from_array( $base );
	}

	/**
	 * Snapshot that passes every scorable check (link-outs stay open).
	 */
	private function pass_snapshot(): STMC_Check_Snapshot {
		return STMC_Check_Snapshot::from_array(
			array(
				'treat_as_production'            => true,
				'is_ssl'                         => true,
				'home_url'                       => 'https://example.com',
				'debug_display'                  => false,
				'enabled_gateways'               => array(
					array(
						'id'        => 'bacs',
						'title'     => 'BACS',
						'test_mode' => false,
					),
				),
				'shipping_zones'                 => array(
					array(
						'id'      => 0,
						'name'    => 'Locations not covered',
						'default' => true,
						'methods' => array( 'free_shipping' ),
					),
					array(
						'id'      => 1,
						'name'    => 'UK',
						'default' => false,
						'methods' => array( 'flat_rate' ),
					),
				),
				'has_delivery_eta_signal'        => true,
				'email_from'                     => 'orders@example.com',
				'emails'                         => array(
					'customer_processing_order' => array(
						'enabled'   => true,
						'recipient' => '',
					),
					'customer_completed_order'  => array(
						'enabled'   => true,
						'recipient' => '',
					),
					'customer_refunded_order'   => array(
						'enabled'   => true,
						'recipient' => '',
					),
					'customer_cancelled_order'  => array(
						'enabled'   => true,
						'recipient' => '',
					),
					'new_order'                 => array(
						'enabled'   => true,
						'recipient' => 'owner@example.com',
					),
				),
				'wc_pages'                       => array(
					'shop'      => 1,
					'cart'      => 2,
					'checkout'  => 3,
					'myaccount' => 4,
				),
				'checkout_page'                  => array(
					'id'                 => 3,
					'has_checkout_block' => true,
					'has_shortcode'      => false,
				),
				'policy_pages'                   => array(
					'privacy' => array(
						'id'     => 20,
						'status' => 'publish',
						'title'  => 'Privacy Policy',
					),
					'refunds' => array(
						'id'     => 21,
						'status' => 'publish',
						'title'  => 'Refund and Returns Policy',
					),
				),
				'stuck_pending_count'            => 0,
				'stuck_on_hold_count'            => 0,
				'stuck_failed_count'             => 0,
				'failed_as_count'                => 0,
				'overdue_as_count'               => 0,
				'hpos_compat_mode'               => false,
				'aged_completed_count'           => 0,
				'aged_cancelled_count'           => 0,
				'permalink_structure'            => '/%postname%/',
				'taxes_enabled'                  => true,
				'tax_rates_count'                => 1,
				'store_address'                  => array(
					'address_1' => '123 Main St',
					'city'      => 'Exampleville',
					'postcode'  => '10001',
				),
				'fatal_error_log_count'          => 0,
				'php_version'                    => '8.3.0',
				'wp_version'                     => '6.9',
				'db_server_type'                 => 'mysql',
				'db_server_version'              => '8.0.36',
				'wp_memory_limit_bytes'          => 256 * 1024 * 1024,
				'wp_update_available'            => false,
				'wc_update_available'            => false,
				'coming_soon_enabled'            => false,
				'woocommerce_currency'           => 'USD',
				'timezone_string'                => 'UTC',
				'gmt_offset'                     => '0',
				'expired_enabled_coupon_count'   => 0,
				'expired_enabled_coupon_samples' => array(),
				'catalog_products'               => array(
					array(
						'id'                => 1,
						'type'              => 'simple',
						'price'             => '10',
						'regular_price'     => '10',
						'image_id'          => 5,
						'downloadable'      => false,
						'has_downloads'     => false,
						'is_external'       => false,
						'name'              => 'Good',
						'description'       => 'Full product description.',
						'short_description' => 'Short product blurb.',
						'sku'               => 'GOOD-1',
						'stock_status'      => 'instock',
						'is_virtual'        => false,
						'needs_shipping'    => true,
					),
				),
				'catalog_variations'             => array(
					array(
						'id'            => 2,
						'parent_id'     => 99,
						'price'         => '8',
						'regular_price' => '8',
						'name'          => 'Var',
						'sku'           => 'GOOD-1-V',
					),
				),
			)
		);
	}

	/**
	 * Process queued AS actions until empty (bounded).
	 *
	 * @param STMC_Scan_Scheduler $scheduler Scheduler.
	 */
	private function drain_as_queue( STMC_Scan_Scheduler $scheduler ): void {
		$guard = 0;
		while ( ! empty( $GLOBALS['stmc_test_as'] ) && $guard < 50 ) {
			++$guard;
			$action = array_shift( $GLOBALS['stmc_test_as'] );
			$hook   = (string) ( $action['hook'] ?? '' );
			$args   = is_array( $action['args'] ?? null ) ? $action['args'] : array();
			// AS stores a single wrapped payload: array( array( 'scan_id' => … ) ).
			if ( isset( $args[0] ) && is_array( $args[0] ) ) {
				$args = $args[0];
			}
			if ( STMC_Scan_Scheduler::HOOK_CATALOG === $hook ) {
				$scheduler->handle_catalog_batch( $args );
			} elseif ( STMC_Scan_Scheduler::HOOK_VARIATION === $hook ) {
				$scheduler->handle_variation_batch( $args );
			} elseif ( STMC_Scan_Scheduler::HOOK_FINALIZE === $hook ) {
				$scheduler->handle_finalize( $args );
			}
		}
	}
}
