<?php
/**
 * PHPUnit bootstrap (unit suite — no full WordPress install).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

require dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! defined( 'STMC_VERSION' ) ) {
	define( 'STMC_VERSION', '0.0.0-test' );
}

if ( ! defined( 'STMC_PLUGIN_FILE' ) ) {
	define( 'STMC_PLUGIN_FILE', dirname( __DIR__ ) . '/store-maintenance-checklist.php' );
}

if ( ! defined( 'STMC_PLUGIN_DIR' ) ) {
	define( 'STMC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'STMC_PLUGIN_URL' ) ) {
	define( 'STMC_PLUGIN_URL', 'http://example.test/wp-content/plugins/store-maintenance-checklist/' );
}

if ( ! defined( 'STMC_TEXT_DOMAIN' ) ) {
	define( 'STMC_TEXT_DOMAIN', 'store-maintenance-checklist-for-woocommerce' );
}

if ( ! defined( 'STMC_URL_AUTO_ARCHIVE' ) ) {
	define( 'STMC_URL_AUTO_ARCHIVE', 'https://storecrafted.com/product/auto-archive-old-orders-for-woocommerce/' );
}

if ( ! defined( 'STMC_URL_EXPECTED_DELIVERY' ) ) {
	define( 'STMC_URL_EXPECTED_DELIVERY', 'https://storecrafted.com/product/expected-delivery-times-for-woocommerce/' );
}

if ( ! defined( 'STMC_URL_WC_MEMORY_LIMIT_DOCS' ) ) {
	define( 'STMC_URL_WC_MEMORY_LIMIT_DOCS', 'https://woocommerce.com/document/increasing-the-wordpress-memory-limit/' );
}

$GLOBALS['stmc_test_options'] = array();
$GLOBALS['stmc_test_option_autoload'] = array();
$GLOBALS['stmc_test_caps']    = array();
$GLOBALS['stmc_test_time']    = null;
$GLOBALS['stmc_test_as']      = array();
$GLOBALS['stmc_test_actions'] = array();
$GLOBALS['stmc_test_rest_routes'] = array();

if ( ! class_exists( 'WP_Error', false ) ) {
	/**
	 * Minimal WP_Error stand-in for unit tests.
	 */
	class WP_Error {
		/**
		 * @var array<string, string[]>
		 */
		public $errors = array();

		/**
		 * @var array<string, mixed>
		 */
		public $error_data = array();

		/**
		 * @param string|int          $code    Error code.
		 * @param string              $message Message.
		 * @param mixed               $data    Optional data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( '' !== $code ) {
				$this->errors[ (string) $code ][] = $message;
				if ( '' !== $data ) {
					$this->error_data[ (string) $code ] = $data;
				}
			}
		}

		/**
		 * @return string
		 */
		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return (string) ( $codes[0] ?? '' );
		}

		/**
		 * @param string $code Optional code.
		 * @return mixed
		 */
		public function get_error_data( $code = '' ) {
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			return $this->error_data[ $code ] ?? null;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response', false ) ) {
	/**
	 * Minimal WP_REST_Response stand-in for unit tests.
	 */
	class WP_REST_Response {
		/**
		 * @var mixed
		 */
		private $data;

		/**
		 * @var int
		 */
		private $status;

		/**
		 * @var array<string, string>
		 */
		private $headers = array();

		/**
		 * @param mixed $data   Response data.
		 * @param int   $status HTTP status.
		 */
		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = (int) $status;
		}

		/**
		 * @param string $key   Header name.
		 * @param string $value Header value.
		 */
		public function header( $key, $value ): void {
			$this->headers[ (string) $key ] = (string) $value;
		}

		/**
		 * @return array<string, string>
		 */
		public function get_headers() {
			return $this->headers;
		}

		/**
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * @return int
		 */
		public function get_status() {
			return $this->status;
		}
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key     Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( $key, $default = false ) {
		return array_key_exists( $key, $GLOBALS['stmc_test_options'] )
			? $GLOBALS['stmc_test_options'][ $key ]
			: $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * @param string     $key      Option key.
	 * @param mixed      $value    Value.
	 * @param bool|null  $autoload Optional autoload flag.
	 * @return bool
	 */
	function update_option( $key, $value, $autoload = null ) {
		$GLOBALS['stmc_test_options'][ $key ] = $value;
		if ( null !== $autoload ) {
			$GLOBALS['stmc_test_option_autoload'][ $key ] = $autoload;
		}
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @return bool
	 */
	function delete_option( $key ) {
		unset( $GLOBALS['stmc_test_options'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * @param string $cap Capability.
	 * @return bool
	 */
	function current_user_can( $cap ) {
		return ! empty( $GLOBALS['stmc_test_caps'][ $cap ] );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param string $str Input.
	 * @return string
	 */
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * @param string $str Input.
	 * @return string
	 */
	function wp_strip_all_tags( $str ) {
		return strip_tags( (string) $str );
	}
}

if ( ! function_exists( 'gmdate' ) ) {
	// Native gmdate exists in PHP; no stub needed.
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
	/**
	 * @return string
	 */
	function wp_generate_uuid4() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0x0fff ) | 0x4000,
			wp_rand( 0, 0x3fff ) | 0x8000,
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff )
		);
	}
}

if ( ! function_exists( 'wp_rand' ) ) {
	/**
	 * @param int $min Min.
	 * @param int $max Max.
	 * @return int
	 */
	function wp_rand( $min = 0, $max = 0 ) {
		return mt_rand( (int) $min, (int) $max );
	}
}

if ( ! function_exists( 'as_enqueue_async_action' ) ) {
	/**
	 * @param string               $hook     Hook.
	 * @param array<string, mixed> $args     Args.
	 * @param string               $group    Group.
	 * @return int
	 */
	function as_enqueue_async_action( $hook, $args = array(), $group = '' ) {
		$id = count( $GLOBALS['stmc_test_as'] ) + 1;
		$GLOBALS['stmc_test_as'][] = array(
			'id'    => $id,
			'hook'  => $hook,
			'args'  => $args,
			'group' => $group,
		);
		return $id;
	}
}

if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
	/**
	 * @param string                    $hook  Hook.
	 * @param array<string, mixed>|null $args  Args.
	 * @param string                    $group Group.
	 * @return void
	 */
	function as_unschedule_all_actions( $hook = null, $args = null, $group = '' ) {
		$GLOBALS['stmc_test_as'] = array_values(
			array_filter(
				$GLOBALS['stmc_test_as'],
				static function ( $action ) use ( $hook, $group ) {
					if ( null !== $hook && $action['hook'] !== $hook ) {
						return true;
					}
					if ( '' !== $group && $action['group'] !== $group ) {
						return true;
					}
					return false;
				}
			)
		);
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'wp_get_environment_type' ) ) {
	/**
	 * @return string
	 */
	function wp_get_environment_type() {
		return $GLOBALS['stmc_test_env'] ?? 'production';
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * @param string $hook     Hook.
	 * @param mixed  $value    Value.
	 * @param mixed  ...$args  Extra args (ignored).
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) {
		unset( $hook );
		$args = func_get_args();
		return $args[1] ?? $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * @param string   $hook     Hook.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $accepted Accepted args.
	 * @return true
	 */
	function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {
		unset( $accepted );
		$GLOBALS['stmc_test_actions'][ (string) $hook ][ (int) $priority ][] = $callback;
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * @param string $hook Hook.
	 * @param mixed  ...$args Args.
	 * @return void
	 */
	function do_action( $hook ) {
		$args = func_get_args();
		array_shift( $args );
		$hook = (string) $hook;
		if ( empty( $GLOBALS['stmc_test_actions'][ $hook ] ) ) {
			return;
		}
		$by_priority = $GLOBALS['stmc_test_actions'][ $hook ];
		ksort( $by_priority );
		foreach ( $by_priority as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				call_user_func_array( $callback, $args );
			}
		}
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * @param string   $hook     Hook.
	 * @param callable $callback Callback.
	 * @param int      $priority Priority.
	 * @param int      $accepted Accepted args.
	 * @return true
	 */
	function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) {
		return add_action( $hook, $callback, $priority, $accepted );
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	/**
	 * @param string               $namespace Namespace.
	 * @param string               $route     Route.
	 * @param array<string, mixed> $args      Args.
	 * @param bool                 $override  Override.
	 * @return bool
	 */
	function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
		$full = '/' . trim( (string) $namespace, '/' ) . '/' . ltrim( (string) $route, '/' );

		if ( isset( $args['methods'] ) || isset( $args['callback'] ) || isset( $args['permission_callback'] ) ) {
			$args = array( $args );
		}

		if ( $override || ! isset( $GLOBALS['stmc_test_rest_routes'][ $full ] ) ) {
			$GLOBALS['stmc_test_rest_routes'][ $full ] = array();
		}

		foreach ( $args as $endpoint ) {
			if ( is_array( $endpoint ) ) {
				$GLOBALS['stmc_test_rest_routes'][ $full ][] = $endpoint;
			}
		}

		return true;
	}
}

if ( ! function_exists( 'rest_get_server' ) ) {
	/**
	 * @return object
	 */
	function rest_get_server() {
		return new class() {
			/**
			 * @return array<string, array<int, array<string, mixed>>>
			 */
			public function get_routes() {
				return $GLOBALS['stmc_test_rest_routes'] ?? array();
			}
		};
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	/**
	 * @param mixed $response Response data.
	 * @return mixed
	 */
	function rest_ensure_response( $response ) {
		return $response;
	}
}

if ( ! function_exists( '__return_true' ) ) {
	/**
	 * @return true
	 */
	function __return_true() {
		return true;
	}
}

require_once STMC_PLUGIN_DIR . 'includes/class-stmc-autoloader.php';
STMC_Autoloader::register();

/**
 * Reset in-memory WP stubs between tests.
 */
function stmc_test_reset(): void {
	$GLOBALS['stmc_test_options']         = array();
	$GLOBALS['stmc_test_option_autoload'] = array();
	$GLOBALS['stmc_test_caps']            = array();
	$GLOBALS['stmc_test_time']            = null;
	$GLOBALS['stmc_test_as']              = array();
	$GLOBALS['stmc_test_env']             = 'production';
	$GLOBALS['stmc_test_actions']         = array();
	$GLOBALS['stmc_test_rest_routes']     = array();
}
