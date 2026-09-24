<?php
/**
 * Read-only store snapshot for check evaluation (unit-test friendly).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Plain data bag for sync checks.
 */
final class STMC_Check_Snapshot {

	/**
	 * @var bool
	 */
	public $treat_as_production = true;

	/**
	 * @var string
	 */
	public $home_url = 'https://example.com';

	/**
	 * @var bool
	 */
	public $is_ssl = true;

	/**
	 * @var bool
	 */
	public $debug_display = false;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $enabled_gateways = array();

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $shipping_zones = array();

	/**
	 * True when delivery ETA / shipping-time messaging is detectable.
	 *
	 * @var bool
	 */
	public $has_delivery_eta_signal = false;

	/**
	 * WordPress permalink_structure option (empty = Plain).
	 *
	 * @var string
	 */
	public $permalink_structure = '/%postname%/';

	/**
	 * Whether WooCommerce tax calculation is enabled.
	 *
	 * @var bool
	 */
	public $taxes_enabled = false;

	/**
	 * Number of configured WooCommerce tax rates.
	 *
	 * @var int
	 */
	public $tax_rates_count = 0;

	/**
	 * Store base address fields used for tax/shipping.
	 *
	 * @var array{address_1?: string, city?: string, postcode?: string}
	 */
	public $store_address = array(
		'address_1' => '123 Main St',
		'city'      => 'Exampleville',
		'postcode'  => '10001',
	);

	/**
	 * Number of non-empty WooCommerce fatal-errors*.log files.
	 *
	 * @var int
	 */
	public $fatal_error_log_count = 0;

	/**
	 * Running PHP version string (e.g. 8.3.0).
	 *
	 * @var string
	 */
	public $php_version = '8.3.0';

	/**
	 * WordPress version string (e.g. 6.9).
	 *
	 * @var string
	 */
	public $wp_version = '6.9';

	/**
	 * Database server family: mysql, mariadb, or empty when unknown.
	 *
	 * @var string
	 */
	public $db_server_type = 'mysql';

	/**
	 * Database server version string.
	 *
	 * @var string
	 */
	public $db_server_version = '8.0.0';

	/**
	 * WP_MEMORY_LIMIT resolved to bytes.
	 *
	 * @var int
	 */
	public $wp_memory_limit_bytes = 268435456;

	/**
	 * Whether a WordPress core update is available.
	 *
	 * @var bool
	 */
	public $wp_update_available = false;

	/**
	 * Whether a WooCommerce plugin update is available.
	 *
	 * @var bool
	 */
	public $wc_update_available = false;

	/**
	 * Whether WooCommerce Coming soon mode is enabled.
	 *
	 * @var bool
	 */
	public $coming_soon_enabled = false;

	/**
	 * WooCommerce store currency code.
	 *
	 * @var string
	 */
	public $woocommerce_currency = 'USD';

	/**
	 * WordPress timezone_string option.
	 *
	 * @var string
	 */
	public $timezone_string = 'UTC';

	/**
	 * WordPress gmt_offset option (string form).
	 *
	 * @var string
	 */
	public $gmt_offset = '0';

	/**
	 * Count of published coupons whose expiry date is in the past.
	 *
	 * @var int
	 */
	public $expired_enabled_coupon_count = 0;

	/**
	 * Sample coupon IDs (or id/code rows) for evidence links.
	 *
	 * @var array<int, int|array<string, mixed>>
	 */
	public $expired_enabled_coupon_samples = array();

	/**
	 * @var string
	 */
	public $email_from = 'shop@example.com';

	/**
	 * @var array<string, array<string, mixed>>
	 */
	public $emails = array();

	/**
	 * @var array<string, int>
	 */
	public $wc_pages = array(
		'shop'      => 0,
		'cart'      => 0,
		'checkout'  => 0,
		'myaccount' => 0,
	);

	/**
	 * Privacy / refunds policy page ids and statuses.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public $policy_pages = array(
		'privacy' => array(
			'id'     => 0,
			'status' => '',
			'title'  => '',
		),
		'refunds' => array(
			'id'     => 0,
			'status' => '',
			'title'  => '',
		),
	);

	/**
	 * @var array<string, mixed>
	 */
	public $checkout_page = array(
		'id'                 => 0,
		'has_checkout_block' => false,
		'has_shortcode'      => false,
	);

	/**
	 * @var int
	 */
	public $stuck_pending_count = 0;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $stuck_pending_samples = array();

	/**
	 * @var int
	 */
	public $stuck_on_hold_count = 0;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $stuck_on_hold_samples = array();

	/**
	 * @var int
	 */
	public $stuck_failed_count = 0;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	public $stuck_failed_samples = array();

	/**
	 * @var int
	 */
	public $failed_as_count = 0;

	/**
	 * @var int
	 */
	public $overdue_as_count = 0;

	/**
	 * @var bool
	 */
	public $hpos_compat_mode = false;

	/**
	 * @var int
	 */
	public $aged_completed_count = 0;

	/**
	 * @var int
	 */
	public $aged_cancelled_count = 0;

	/**
	 * Admin base URL for deep links.
	 *
	 * @var string
	 */
	public $admin_url = 'http://example.test/wp-admin/';

	/**
	 * Optional product rows for unit-test catalog AS path.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public $catalog_products = array();

	/**
	 * Optional variation rows for unit-test catalog AS path.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	public $catalog_variations = array();

	/**
	 * Build from associative overrides.
	 *
	 * @param array<string, mixed> $data Overrides.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$snap = new self();
		foreach ( $data as $key => $value ) {
			if ( property_exists( $snap, $key ) ) {
				$snap->$key = $value;
			}
		}
		return $snap;
	}
}
