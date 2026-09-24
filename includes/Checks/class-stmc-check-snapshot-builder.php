<?php
/**
 * Builds a live check snapshot from WordPress / WooCommerce when available.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Snapshot builder with safe no-op defaults for unit tests.
 */
final class STMC_Check_Snapshot_Builder {

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
	 * Build snapshot from live WP/WC APIs, or defaults when unavailable.
	 */
	public function build(): STMC_Check_Snapshot {
		$env  = new STMC_Environment( $this->options );
		$db   = $this->resolve_db_server();
		$data = array(
			'treat_as_production'     => $env->treat_as_production(),
			'home_url'                => function_exists( 'home_url' ) ? (string) home_url( '/' ) : 'https://example.com',
			'is_ssl'                  => function_exists( 'is_ssl' ) ? (bool) is_ssl() : true,
			'debug_display'           => defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY,
			'admin_url'               => function_exists( 'admin_url' ) ? (string) admin_url() : 'http://example.test/wp-admin/',
			'email_from'              => $this->resolve_email_from(),
			'enabled_gateways'        => $this->resolve_gateways(),
			'shipping_zones'          => $this->resolve_shipping_zones(),
			'has_delivery_eta_signal' => $this->resolve_delivery_eta_signal(),
			'emails'                  => $this->resolve_emails(),
			'wc_pages'                => $this->resolve_wc_pages(),
			'policy_pages'            => $this->resolve_policy_pages(),
			'checkout_page'           => $this->resolve_checkout_page(),
			'hpos_compat_mode'        => $this->resolve_hpos_compat(),
			'permalink_structure'     => $this->resolve_permalink_structure(),
			'taxes_enabled'           => $this->resolve_taxes_enabled(),
			'tax_rates_count'         => $this->resolve_tax_rates_count(),
			'store_address'           => $this->resolve_store_address(),
			'fatal_error_log_count'   => $this->resolve_fatal_error_log_count(),
			'php_version'             => $this->resolve_php_version(),
			'wp_version'              => $this->resolve_wp_version(),
			'db_server_type'          => $db['type'],
			'db_server_version'       => $db['version'],
			'wp_memory_limit_bytes'   => $this->resolve_wp_memory_limit_bytes(),
			'wp_update_available'     => $this->resolve_wp_update_available(),
			'wc_update_available'     => $this->resolve_wc_update_available(),
			'coming_soon_enabled'     => $this->resolve_coming_soon_enabled(),
			'woocommerce_currency'    => $this->resolve_woocommerce_currency(),
			'timezone_string'         => $this->resolve_timezone_string(),
			'gmt_offset'              => $this->resolve_gmt_offset(),
		);

		$orders = $this->resolve_order_counts();
		$data   = array_merge( $data, $orders );

		$as                       = $this->resolve_action_scheduler_counts();
		$data['failed_as_count']  = $as['failed'];
		$data['overdue_as_count'] = $as['overdue'];

		$coupons                                = $this->resolve_expired_enabled_coupons();
		$data['expired_enabled_coupon_count']   = $coupons['count'];
		$data['expired_enabled_coupon_samples'] = $coupons['samples'];

		return STMC_Check_Snapshot::from_array( $data );
	}

	/**
	 * WordPress permalink_structure option (empty string = Plain).
	 */
	private function resolve_permalink_structure(): string {
		if ( function_exists( 'get_option' ) ) {
			return (string) get_option( 'permalink_structure', '' );
		}
		return '/%postname%/';
	}

	/**
	 * Whether WooCommerce tax calculation is enabled.
	 */
	private function resolve_taxes_enabled(): bool {
		if ( ! function_exists( 'get_option' ) ) {
			return false;
		}
		return 'yes' === (string) get_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Count rows in the WooCommerce tax rates table.
	 */
	private function resolve_tax_rates_count(): int {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return 0;
		}

		$cache_key = 'tax_rates_count';
		if ( function_exists( 'wp_cache_get' ) ) {
			$cached = wp_cache_get( $cache_key, 'stmc' );
			if ( false !== $cached ) {
				return (int) $cached;
			}
		}

		$table = esc_sql( $wpdb->prefix . 'woocommerce_tax_rates' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- core WC table; no public count API.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );

		if ( function_exists( 'wp_cache_set' ) ) {
			wp_cache_set( $cache_key, $count, 'stmc', 5 * MINUTE_IN_SECONDS );
		}

		return $count;
	}

	/**
	 * Store base address used for tax and shipping.
	 *
	 * @return array{address_1: string, city: string, postcode: string}
	 */
	private function resolve_store_address(): array {
		if ( ! function_exists( 'get_option' ) ) {
			return array(
				'address_1' => '',
				'city'      => '',
				'postcode'  => '',
			);
		}
		return array(
			'address_1' => (string) get_option( 'woocommerce_store_address', '' ),
			'city'      => (string) get_option( 'woocommerce_store_city', '' ),
			'postcode'  => (string) get_option( 'woocommerce_store_postcode', '' ),
		);
	}

	/**
	 * Count non-empty WooCommerce fatal-errors log files.
	 */
	private function resolve_fatal_error_log_count(): int {
		$dir = $this->resolve_wc_log_directory();
		if ( '' === $dir || ! is_dir( $dir ) ) {
			return 0;
		}

		$count = 0;
		$files = glob( trailingslashit( $dir ) . 'fatal-errors*.log' );
		if ( ! is_array( $files ) ) {
			return 0;
		}
		foreach ( $files as $file ) {
			if ( is_file( $file ) && filesize( $file ) > 0 ) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * WooCommerce log directory path, or empty when unavailable.
	 */
	private function resolve_wc_log_directory(): string {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\LoggingUtil' )
			&& method_exists( '\Automattic\WooCommerce\Utilities\LoggingUtil', 'get_log_directory' )
		) {
			$dir = \Automattic\WooCommerce\Utilities\LoggingUtil::get_log_directory();
			if ( is_string( $dir ) && '' !== $dir ) {
				return $dir;
			}
		}
		if ( defined( 'WC_LOG_DIR' ) ) {
			return (string) WC_LOG_DIR;
		}
		if ( function_exists( 'wp_upload_dir' ) ) {
			$uploads = wp_upload_dir( null, false );
			if ( is_array( $uploads ) && ! empty( $uploads['basedir'] ) ) {
				return trailingslashit( (string) $uploads['basedir'] ) . 'wc-logs';
			}
		}
		return '';
	}

	/**
	 * Current PHP version.
	 */
	private function resolve_php_version(): string {
		return PHP_VERSION;
	}

	/**
	 * Current WordPress version.
	 */
	private function resolve_wp_version(): string {
		if ( function_exists( 'get_bloginfo' ) ) {
			return (string) get_bloginfo( 'version' );
		}
		return '';
	}

	/**
	 * Database server type and version from $wpdb when available.
	 *
	 * @return array{type: string, version: string}
	 */
	private function resolve_db_server(): array {
		global $wpdb;
		$raw = '';
		if ( isset( $wpdb ) && is_object( $wpdb ) ) {
			if ( method_exists( $wpdb, 'db_server_info' ) ) {
				$raw = (string) $wpdb->db_server_info();
			} elseif ( method_exists( $wpdb, 'get_var' ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- single VERSION() probe for env check.
				$raw = (string) $wpdb->get_var( 'SELECT VERSION()' );
			}
		}
		if ( '' === $raw ) {
			return array(
				'type'    => '',
				'version' => '',
			);
		}
		$type = ( false !== stripos( $raw, 'mariadb' ) ) ? 'mariadb' : 'mysql';
		$ver  = '';
		if ( preg_match( '/^(\d+(?:\.\d+)*)/', $raw, $matches ) ) {
			$ver = $matches[1];
		}
		return array(
			'type'    => $type,
			'version' => $ver,
		);
	}

	/**
	 * WP_MEMORY_LIMIT in bytes.
	 */
	private function resolve_wp_memory_limit_bytes(): int {
		$limit = defined( 'WP_MEMORY_LIMIT' ) ? (string) WP_MEMORY_LIMIT : '40M';
		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {
			return (int) wp_convert_hr_to_bytes( $limit );
		}
		return $this->convert_hr_to_bytes( $limit );
	}

	/**
	 * Whether a WordPress core update is available.
	 */
	private function resolve_wp_update_available(): bool {
		if ( ! function_exists( 'get_core_updates' ) ) {
			return false;
		}
		$updates = get_core_updates( array( 'dismissed' => false ) );
		if ( ! is_array( $updates ) ) {
			return false;
		}
		foreach ( $updates as $update ) {
			if ( is_object( $update ) && isset( $update->response ) && 'upgrade' === $update->response ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a WooCommerce plugin update is available.
	 */
	private function resolve_wc_update_available(): bool {
		if ( ! function_exists( 'get_site_transient' ) ) {
			return false;
		}
		$transient = get_site_transient( 'update_plugins' );
		if ( ! is_object( $transient ) || empty( $transient->response ) || ! is_array( $transient->response ) ) {
			return false;
		}
		foreach ( array_keys( $transient->response ) as $plugin_file ) {
			if ( is_string( $plugin_file ) && false !== strpos( $plugin_file, 'woocommerce.php' ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * WooCommerce Coming soon site visibility.
	 */
	private function resolve_coming_soon_enabled(): bool {
		if ( ! function_exists( 'get_option' ) ) {
			return false;
		}
		return 'yes' === (string) get_option( 'woocommerce_coming_soon', 'no' );
	}

	/**
	 * Store currency code.
	 */
	private function resolve_woocommerce_currency(): string {
		if ( function_exists( 'get_woocommerce_currency' ) ) {
			return (string) get_woocommerce_currency();
		}
		if ( function_exists( 'get_option' ) ) {
			return (string) get_option( 'woocommerce_currency', '' );
		}
		return '';
	}

	/**
	 * WordPress timezone_string.
	 */
	private function resolve_timezone_string(): string {
		if ( function_exists( 'get_option' ) ) {
			return (string) get_option( 'timezone_string', '' );
		}
		return '';
	}

	/**
	 * WordPress gmt_offset as string.
	 */
	private function resolve_gmt_offset(): string {
		if ( ! function_exists( 'get_option' ) ) {
			return '';
		}
		$offset = get_option( 'gmt_offset', '' );
		if ( false === $offset || null === $offset ) {
			return '';
		}
		return (string) $offset;
	}

	/**
	 * Minimal size parser when wp_convert_hr_to_bytes is unavailable.
	 */
	private function convert_hr_to_bytes( string $value ): int {
		$value = trim( $value );
		if ( '' === $value ) {
			return 0;
		}
		$last = strtolower( substr( $value, -1 ) );
		$num  = (float) $value;
		switch ( $last ) {
			case 'g':
				return (int) ( $num * 1024 * 1024 * 1024 );
			case 'm':
				return (int) ( $num * 1024 * 1024 );
			case 'k':
				return (int) ( $num * 1024 );
			default:
				return (int) $num;
		}
	}

	/**
	 * @return string
	 */
	private function resolve_email_from(): string {
		if ( function_exists( 'get_option' ) ) {
			$from = (string) get_option( 'woocommerce_email_from_address', '' );
			if ( '' !== $from ) {
				return $from;
			}
		}
		return 'shop@example.com';
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_gateways(): array {
		if ( ! function_exists( 'WC' ) || ! is_object( WC() ) || ! isset( WC()->payment_gateways ) ) {
			return array();
		}
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( ! is_array( $gateways ) ) {
			return array();
		}
		$rows = array();
		foreach ( $gateways as $id => $gateway ) {
			$test_mode = null;
			if ( is_object( $gateway ) ) {
				if ( isset( $gateway->testmode ) ) {
					$test_mode = (bool) $gateway->testmode;
				} elseif ( method_exists( $gateway, 'get_option' ) ) {
					$raw = $gateway->get_option( 'testmode', null );
					if ( null !== $raw && '' !== $raw ) {
						$test_mode = in_array( (string) $raw, array( 'yes', '1', 'true' ), true );
					}
				}
			}
			$rows[] = array(
				'id'        => (string) $id,
				'title'     => is_object( $gateway ) && isset( $gateway->title ) ? (string) $gateway->title : (string) $id,
				'test_mode' => $test_mode,
			);
		}
		return $rows;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_shipping_zones(): array {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return array();
		}
		$zones = array();
		foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
			$methods = array();
			if ( ! empty( $zone_data['shipping_methods'] ) && is_array( $zone_data['shipping_methods'] ) ) {
				foreach ( $zone_data['shipping_methods'] as $method ) {
					if ( is_object( $method ) && isset( $method->id ) ) {
						$methods[] = (string) $method->id;
					}
				}
			}
			$zones[] = array(
				'id'      => (int) ( $zone_data['id'] ?? 0 ),
				'name'    => (string) ( $zone_data['zone_name'] ?? '' ),
				'default' => false,
				'methods' => $methods,
			);
		}

		$default = WC_Shipping_Zones::get_zone( 0 );
		if ( $default ) {
			$methods = array();
			foreach ( $default->get_shipping_methods( true ) as $method ) {
				if ( is_object( $method ) && isset( $method->id ) ) {
					$methods[] = (string) $method->id;
				}
			}
			$zones[] = array(
				'id'      => 0,
				'name'    => (string) $default->get_zone_name(),
				'default' => true,
				'methods' => $methods,
			);
		}

		return $zones;
	}

	/**
	 * Detect delivery ETA / shipping-time messaging via active plugins or method titles.
	 */
	private function resolve_delivery_eta_signal(): bool {
		if ( $this->active_plugins_suggest_delivery_eta() ) {
			return true;
		}
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return false;
		}
		foreach ( WC_Shipping_Zones::get_zones() as $zone_data ) {
			if ( empty( $zone_data['shipping_methods'] ) || ! is_array( $zone_data['shipping_methods'] ) ) {
				continue;
			}
			foreach ( $zone_data['shipping_methods'] as $method ) {
				if ( is_object( $method ) && method_exists( $method, 'get_title' )
					&& $this->title_suggests_delivery_eta( (string) $method->get_title() ) ) {
					return true;
				}
			}
		}
		$default = WC_Shipping_Zones::get_zone( 0 );
		if ( $default ) {
			foreach ( $default->get_shipping_methods( true ) as $method ) {
				if ( is_object( $method ) && method_exists( $method, 'get_title' )
					&& $this->title_suggests_delivery_eta( (string) $method->get_title() ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Soft match active plugin paths that typically provide delivery ETA messaging.
	 */
	private function active_plugins_suggest_delivery_eta(): bool {
		if ( ! function_exists( 'get_option' ) ) {
			return false;
		}
		$active = (array) get_option( 'active_plugins', array() );
		if ( empty( $active ) ) {
			return false;
		}
		$needles = array(
			'expected-delivery',
			'estimated-delivery',
			'delivery-time',
			'delivery-date',
			'shipping-dates',
			'delivery-eta',
		);
		foreach ( $active as $plugin ) {
			$plugin = strtolower( (string) $plugin );
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $plugin, $needle ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param string $title Shipping method title.
	 */
	private function title_suggests_delivery_eta( string $title ): bool {
		$title = trim( $title );
		if ( '' === $title ) {
			return false;
		}
		return (bool) preg_match(
			'/\b(\d+\s*[-–to]+\s*)?\d+\s*(business\s+)?days?\b|\bships?\s+in\b|\bdelivery\s+(in|within|time|estimate|eta)\b|\beta\b|\bshipping\s+time\b/i',
			$title
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function resolve_emails(): array {
		$defaults = array(
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
				'recipient' => function_exists( 'get_option' ) ? (string) get_option( 'admin_email', '' ) : '',
			),
		);

		if ( ! function_exists( 'WC' ) || ! is_object( WC() ) || ! isset( WC()->mailer ) ) {
			return $defaults;
		}

		$mails = WC()->mailer()->get_emails();
		if ( ! is_array( $mails ) ) {
			return $defaults;
		}

		foreach ( $mails as $mail ) {
			if ( ! is_object( $mail ) || ! isset( $mail->id ) ) {
				continue;
			}
			$id = (string) $mail->id;
			if ( ! isset( $defaults[ $id ] ) ) {
				continue;
			}
			$enabled   = method_exists( $mail, 'is_enabled' ) ? (bool) $mail->is_enabled() : true;
			$recipient = '';
			if ( method_exists( $mail, 'get_recipient' ) ) {
				$recipient = (string) $mail->get_recipient();
			} elseif ( isset( $mail->recipient ) ) {
				$recipient = (string) $mail->recipient;
			}
			$defaults[ $id ] = array(
				'enabled'   => $enabled,
				'recipient' => $recipient,
			);
		}

		return $defaults;
	}

	/**
	 * @return array<string, int>
	 */
	private function resolve_wc_pages(): array {
		$pages = array(
			'shop'      => 0,
			'cart'      => 0,
			'checkout'  => 0,
			'myaccount' => 0,
		);
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return $pages;
		}
		foreach ( array_keys( $pages ) as $key ) {
			$id            = (int) wc_get_page_id( $key );
			$pages[ $key ] = $id > 0 ? $id : 0;
		}
		return $pages;
	}

	/**
	 * Privacy + refunds policy page assignment and publish status.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function resolve_policy_pages(): array {
		$pages = array(
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

		$privacy_id = (int) get_option( 'wp_page_for_privacy_policy', 0 );
		if ( $privacy_id > 0 ) {
			$pages['privacy'] = $this->page_status_row( $privacy_id );
		}

		$refunds_id = 0;
		if ( function_exists( 'wc_get_page_id' ) ) {
			$refunds_id = (int) wc_get_page_id( 'refunds' );
		}
		if ( $refunds_id > 0 ) {
			$pages['refunds'] = $this->page_status_row( $refunds_id );
		}

		return $pages;
	}

	/**
	 * @param int $page_id Page ID.
	 * @return array{id: int, status: string, title: string}
	 */
	private function page_status_row( int $page_id ): array {
		$row = array(
			'id'     => $page_id,
			'status' => '',
			'title'  => '',
		);
		if ( ! function_exists( 'get_post' ) ) {
			return $row;
		}
		$post = get_post( $page_id );
		if ( ! $post || ! isset( $post->post_status ) ) {
			return $row;
		}
		$row['status'] = (string) $post->post_status;
		$row['title']  = isset( $post->post_title ) ? (string) $post->post_title : '';
		return $row;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function resolve_checkout_page(): array {
		$id = 0;
		if ( function_exists( 'wc_get_page_id' ) ) {
			$id = (int) wc_get_page_id( 'checkout' );
		}
		$has_block     = false;
		$has_shortcode = false;
		if ( $id > 0 && function_exists( 'get_post' ) ) {
			$post = get_post( $id );
			if ( $post && isset( $post->post_content ) ) {
				$content       = (string) $post->post_content;
				$has_shortcode = false !== strpos( $content, '[woocommerce_checkout]' );
				$has_block     = false !== strpos( $content, 'woocommerce/checkout' )
					|| false !== strpos( $content, 'wp:woocommerce/checkout' );
			}
		}
		return array(
			'id'                 => $id,
			'has_checkout_block' => $has_block,
			'has_shortcode'      => $has_shortcode,
		);
	}

	/**
	 * @return bool
	 */
	private function resolve_hpos_compat(): bool {
		if ( ! function_exists( 'get_option' ) ) {
			return false;
		}
		return 'yes' === get_option( 'woocommerce_custom_orders_table_data_sync_enabled', 'no' );
	}

	/**
	 * Bounded order counts via WC order query when available.
	 *
	 * @return array<string, mixed>
	 */
	private function resolve_order_counts(): array {
		$result = array(
			'stuck_pending_count'   => 0,
			'stuck_pending_samples' => array(),
			'stuck_on_hold_count'   => 0,
			'stuck_on_hold_samples' => array(),
			'stuck_failed_count'    => 0,
			'stuck_failed_samples'  => array(),
			'aged_completed_count'  => 0,
			'aged_cancelled_count'  => 0,
		);

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $result;
		}

		$day                = defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400;
		$seven_days         = gmdate( 'Y-m-d H:i:s', time() - ( 7 * $day ) );
		$twenty_four_months = gmdate( 'Y-m-d H:i:s', time() - ( 730 * $day ) );

		$pending = wc_get_orders(
			array(
				'status'       => array( 'wc-pending' ),
				'date_created' => '<' . $seven_days,
				'limit'        => 3,
				'return'       => 'ids',
				'paginate'     => true,
			)
		);
		if ( is_object( $pending ) ) {
			$result['stuck_pending_count']   = (int) ( $pending->total ?? 0 );
			$result['stuck_pending_samples'] = array_map( 'intval', (array) ( $pending->orders ?? array() ) );
		}

		$on_hold = wc_get_orders(
			array(
				'status'       => array( 'wc-on-hold' ),
				'date_created' => '<' . $seven_days,
				'limit'        => 3,
				'return'       => 'ids',
				'paginate'     => true,
			)
		);
		if ( is_object( $on_hold ) ) {
			$result['stuck_on_hold_count']   = (int) ( $on_hold->total ?? 0 );
			$result['stuck_on_hold_samples'] = array_map( 'intval', (array) ( $on_hold->orders ?? array() ) );
		}

		$failed = wc_get_orders(
			array(
				'status'       => array( 'wc-failed' ),
				'date_created' => '<' . $seven_days,
				'limit'        => 3,
				'return'       => 'ids',
				'paginate'     => true,
			)
		);
		if ( is_object( $failed ) ) {
			$result['stuck_failed_count']   = (int) ( $failed->total ?? 0 );
			$result['stuck_failed_samples'] = array_map( 'intval', (array) ( $failed->orders ?? array() ) );
		}

		$completed = wc_get_orders(
			array(
				'status'       => array( 'wc-completed' ),
				'date_created' => '<' . $twenty_four_months,
				'limit'        => 1,
				'return'       => 'ids',
				'paginate'     => true,
			)
		);
		if ( is_object( $completed ) ) {
			$result['aged_completed_count'] = (int) ( $completed->total ?? 0 );
		}

		$cancelled = wc_get_orders(
			array(
				'status'       => array( 'wc-cancelled' ),
				'date_created' => '<' . $twenty_four_months,
				'limit'        => 1,
				'return'       => 'ids',
				'paginate'     => true,
			)
		);
		if ( is_object( $cancelled ) ) {
			$result['aged_cancelled_count'] = (int) ( $cancelled->total ?? 0 );
		}

		return $result;
	}

	/**
	 * @return array{failed: int, overdue: int}
	 */
	private function resolve_action_scheduler_counts(): array {
		$out = array(
			'failed'  => 0,
			'overdue' => 0,
		);
		if ( ! function_exists( 'as_get_scheduled_actions' ) ) {
			return $out;
		}

		$failed = as_get_scheduled_actions(
			array(
				'status'   => 'failed',
				'per_page' => 1,
			),
			'ids'
		);
		if ( is_array( $failed ) ) {
			// Prefer store count when available.
			if ( class_exists( 'ActionScheduler' ) && method_exists( 'ActionScheduler', 'store' ) ) {
				$store = ActionScheduler::store();
				if ( is_object( $store ) && method_exists( $store, 'query_actions' ) ) {
					$out['failed'] = (int) $store->query_actions(
						array(
							'status'   => 'failed',
							'per_page' => 1,
						),
						'count'
					);
				} else {
					$out['failed'] = count( $failed ) > 0 ? 1 : 0;
				}
			} else {
				$out['failed'] = count( $failed ) > 0 ? 1 : 0;
			}
		}

		if ( class_exists( 'ActionScheduler' ) && method_exists( 'ActionScheduler', 'store' ) ) {
			$store = ActionScheduler::store();
			if ( is_object( $store ) && method_exists( $store, 'query_actions' ) ) {
				$date           = function_exists( 'as_get_datetime_object' ) ? as_get_datetime_object() : gmdate( 'Y-m-d H:i:s' );
				$out['overdue'] = (int) $store->query_actions(
					array(
						'status'       => 'pending',
						'date'         => $date,
						'date_compare' => '<',
						'per_page'     => 1,
					),
					'count'
				);
			}
		}

		return $out;
	}

	/**
	 * Published coupons whose expiry timestamp is in the past.
	 *
	 * @return array{count: int, samples: array<int, array{id: int, code: string}>}
	 */
	private function resolve_expired_enabled_coupons(): array {
		$out = array(
			'count'   => 0,
			'samples' => array(),
		);

		if ( ! class_exists( 'WP_Query' ) ) {
			return $out;
		}

		$now = time();
		$q   = new WP_Query(
			array(
				'post_type'              => 'shop_coupon',
				'post_status'            => 'publish',
				'posts_per_page'         => 3,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded coupon hygiene scan.
					'relation' => 'AND',
					array(
						'key'     => 'date_expires',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
					array(
						'key'     => 'date_expires',
						'value'   => $now,
						'compare' => '<=',
						'type'    => 'NUMERIC',
					),
				),
			)
		);

		$out['count'] = (int) $q->found_posts;
		$ids          = is_array( $q->posts ) ? $q->posts : array();

		foreach ( $ids as $id ) {
			$id   = (int) $id;
			$code = '';
			if ( class_exists( 'WC_Coupon' ) ) {
				$coupon = new WC_Coupon( $id );
				if ( method_exists( $coupon, 'get_code' ) ) {
					$code = (string) $coupon->get_code();
				}
			} elseif ( function_exists( 'get_the_title' ) ) {
				$code = (string) get_the_title( $id );
			}
			$out['samples'][] = array(
				'id'   => $id,
				'code' => $code,
			);
		}

		return $out;
	}
}
