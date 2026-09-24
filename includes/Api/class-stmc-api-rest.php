<?php
/**
 * REST API registration for stmc/v1.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Registers all contracted REST routes.
 */
final class STMC_Api_Rest {

	public const NAMESPACE = 'stmc/v1';

	/**
	 * @var STMC_Options
	 */
	private $options;

	/**
	 * @var STMC_Scan_Engine
	 */
	private $engine;

	/**
	 * @var STMC_Csv_Exporter
	 */
	private $csv;

	/**
	 * @var STMC_Checks_Registry
	 */
	private $registry;

	/**
	 * @param STMC_Options           $options  Options.
	 * @param STMC_Scan_Engine       $engine   Scan engine.
	 * @param STMC_Csv_Exporter      $csv      CSV exporter.
	 * @param STMC_Checks_Registry   $registry Check registry.
	 */
	public function __construct( STMC_Options $options, STMC_Scan_Engine $engine, STMC_Csv_Exporter $csv, STMC_Checks_Registry $registry ) {
		$this->options  = $options;
		$this->engine   = $engine;
		$this->csv      = $csv;
		$this->registry = $registry;
	}

	/**
	 * Hook rest_api_init.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_filter( 'rest_pre_serve_request', array( $this, 'serve_raw_csv' ), 15, 4 );
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/scan',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_scan' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_scan' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/scan/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_scan_status' ),
				'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/scan/cancel',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'post_scan_cancel' ),
				'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/scan/retry',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'post_scan_retry' ),
				'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/checks/(?P<id>[a-z0-9._-]+)/ignore',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_ignore' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_ignore' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/history',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_history' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_history' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'put_settings' ),
					'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/export/csv',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_export_csv' ),
				'permission_callback' => array( 'STMC_Api_Permissions', 'check' ),
			)
		);
	}

	/**
	 * GET /scan
	 *
	 * @return WP_REST_Response|array<string, mixed>
	 */
	public function get_scan() {
		return rest_ensure_response( $this->engine->get_scan_payload() );
	}

	/**
	 * POST /scan
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function post_scan() {
		$result = $this->engine->start();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$response = rest_ensure_response( $result );
		$response->set_status( 202 );
		return $response;
	}

	/**
	 * GET /scan/status
	 *
	 * @return WP_REST_Response
	 */
	public function get_scan_status() {
		return rest_ensure_response( $this->engine->get_status() );
	}

	/**
	 * POST /scan/cancel
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function post_scan_cancel() {
		$result = $this->engine->cancel();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( $result );
	}

	/**
	 * POST /scan/retry
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function post_scan_retry() {
		$result = $this->engine->retry();
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$response = rest_ensure_response( $result );
		$response->set_status( 202 );
		return $response;
	}

	/**
	 * POST /checks/{id}/ignore
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function post_ignore( $request ) {
		$id      = (string) $request['id'];
		$unknown = $this->unknown_check_error( $id );
		if ( null !== $unknown ) {
			return $unknown;
		}

		$reason = '';
		if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) {
			$reason = (string) $request->get_param( 'reason' );
		}
		$this->options->ignore_check( $id, $reason );
		$ignores = $this->options->get_ignores();
		$meta    = $ignores[ $id ] ?? array();

		return rest_ensure_response(
			array(
				'id'             => $id,
				'status'         => 'ignored',
				'score_excluded' => true,
				'ignore_reason'  => (string) ( $meta['reason'] ?? $reason ),
			)
		);
	}

	/**
	 * DELETE /checks/{id}/ignore
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_ignore( $request ) {
		$id      = (string) $request['id'];
		$unknown = $this->unknown_check_error( $id );
		if ( null !== $unknown ) {
			return $unknown;
		}

		$this->options->restore_check( $id );
		return rest_ensure_response(
			array(
				'id'             => $id,
				'status'         => 'open',
				'score_excluded' => false,
				'ignore_reason'  => null,
			)
		);
	}

	/**
	 * @param string $id Check id.
	 * @return WP_Error|null
	 */
	private function unknown_check_error( string $id ): ?WP_Error {
		if ( null !== $this->registry->get( $id ) ) {
			return null;
		}

		return new WP_Error(
			'stmc_unknown_check',
			__( 'Unknown check id.', 'store-maintenance-checklist' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * GET /history
	 *
	 * @return WP_REST_Response
	 */
	public function get_history() {
		$items = $this->options->get_history();
		if ( count( $items ) > 10 ) {
			$items = array_slice( $items, 0, 10 );
		}
		return rest_ensure_response( array( 'items' => $items ) );
	}

	/**
	 * DELETE /history
	 *
	 * @return WP_REST_Response
	 */
	public function delete_history() {
		$this->options->clear_history();
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * GET /settings
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		$settings = $this->options->get_settings();
		return rest_ensure_response(
			array_merge(
				$settings,
				array(
					'ignored_count' => count( $this->options->get_ignores() ),
					'constants'     => array(
						'force_production_defined' => defined( 'STMC_FORCE_PRODUCTION_SEVERITY' ),
					),
				)
			)
		);
	}

	/**
	 * PUT /settings
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function put_settings( $request ) {
		$params = array();
		if ( is_object( $request ) && method_exists( $request, 'get_json_params' ) ) {
			$json = $request->get_json_params();
			if ( is_array( $json ) ) {
				$params = $json;
			}
		} elseif ( is_object( $request ) && method_exists( $request, 'get_params' ) ) {
			$params = (array) $request->get_params();
		}

		$allowed = array(
			'force_production_severity',
			'show_further_tools',
			'max_products',
			'max_variations',
		);
		$partial = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $params ) ) {
				$partial[ $key ] = $params[ $key ];
			}
		}

		$settings = $this->options->update_settings( $partial );
		return rest_ensure_response(
			array_merge(
				$settings,
				array(
					'ignored_count' => count( $this->options->get_ignores() ),
					'constants'     => array(
						'force_production_defined' => defined( 'STMC_FORCE_PRODUCTION_SEVERITY' ),
					),
				)
			)
		);
	}

	/**
	 * GET /export/csv — raw CSV (not JSON-wrapped).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_export_csv( $request = null ) {
		unset( $request );
		$latest   = $this->options->get_latest_results();
		$findings = array_merge(
			is_array( $latest['findings'] ?? null ) ? $latest['findings'] : array(),
			is_array( $latest['passed'] ?? null ) ? $latest['passed'] : array()
		);
		$env      = is_array( $latest['environment'] ?? null ) ? $latest['environment'] : array();

		$csv = $this->csv->export(
			$findings,
			array(
				'scan_timestamp' => (string) ( $latest['last_completed_at'] ?? '' ),
				'environment'    => (string) ( $env['type'] ?? '' ),
				'provisional'    => ! empty( $latest['summary']['provisional'] ),
			)
		);

		$response = new WP_REST_Response( $csv, 200 );
		$response->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="store-maintenance-checklist-findings.csv"' );
		$response->header( 'X-STMC-Raw-CSV', '1' );
		return $response;
	}

	/**
	 * Serve CSV body without JSON encoding.
	 *
	 * @param bool             $served  Whether already served.
	 * @param WP_REST_Response $result  Result.
	 * @param WP_REST_Request  $request Request.
	 * @param WP_REST_Server   $server  Server.
	 * @return bool
	 */
	public function serve_raw_csv( $served, $result, $request, $server ) {
		unset( $server );
		if ( $served || ! ( $result instanceof WP_REST_Response ) ) {
			return $served;
		}

		$route = '';
		if ( is_object( $request ) && method_exists( $request, 'get_route' ) ) {
			$route = (string) $request->get_route();
		}
		if ( '/stmc/v1/export/csv' !== $route ) {
			return $served;
		}

		$headers = $result->get_headers();
		if ( empty( $headers['X-STMC-Raw-CSV'] ) && empty( $headers['x-stmc-raw-csv'] ) ) {
			return $served;
		}
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="store-maintenance-checklist-findings.csv"' );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw CSV download.
		echo $result->get_data();
		return true;
	}
}
