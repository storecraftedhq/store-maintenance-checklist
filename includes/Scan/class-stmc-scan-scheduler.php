<?php
/**
 * Action Scheduler wiring for scan batches.
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Registers AS group hooks and enqueues/cancels scan work.
 */
final class STMC_Scan_Scheduler {

	public const GROUP          = 'stmc';
	public const HOOK_CATALOG   = 'stmc/scan/catalog_batch';
	public const HOOK_VARIATION = 'stmc/scan/variation_batch';
	public const HOOK_FINALIZE  = 'stmc/scan/finalize';

	/**
	 * @var STMC_Checks_Registry|null
	 */
	private $registry;

	/**
	 * @var STMC_Catalog_Source|null
	 */
	private $catalog;

	/**
	 * @param STMC_Checks_Registry|null $registry Registry.
	 * @param STMC_Catalog_Source|null  $catalog  Catalog source.
	 */
	public function __construct( ?STMC_Checks_Registry $registry = null, ?STMC_Catalog_Source $catalog = null ) {
		$this->registry = $registry;
		$this->catalog  = $catalog;
	}

	/**
	 * Wire Action Scheduler callbacks.
	 */
	public function register(): void {
		add_action( self::HOOK_CATALOG, array( $this, 'handle_catalog_batch' ), 10, 1 );
		add_action( self::HOOK_VARIATION, array( $this, 'handle_variation_batch' ), 10, 1 );
		add_action( self::HOOK_FINALIZE, array( $this, 'handle_finalize' ), 10, 1 );
	}

	/**
	 * Re-queue remaining catalog path for an active scan (retry).
	 *
	 * @param string $scan_id Active scan id.
	 */
	public function enqueue_scan_work( string $scan_id ): void {
		$options = new STMC_Options();
		$state   = $options->get_scan_state();
		$working = is_array( $state['working'] ?? null ) ? $state['working'] : array();

		$products_scanned = (int) ( $state['products_scanned'] ?? 0 );
		$product_bound    = (int) ( $working['product_bound'] ?? 0 );
		$variation_bound  = (int) ( $working['variation_bound'] ?? 0 );

		if ( $products_scanned < $product_bound ) {
			$this->enqueue_catalog_batch( $scan_id, $products_scanned );
			return;
		}

		$batches          = (int) ( $working['batches_completed'] ?? 0 );
		$product_batches  = $product_bound > 0
			? (int) ceil( $product_bound / STMC_Checks_Registry::PRODUCTS_PER_BATCH )
			: 0;
		$variation_done   = max( 0, $batches - $product_batches );
		$variation_offset = $variation_done * STMC_Checks_Registry::VARIATIONS_PER_BATCH;

		if ( $variation_offset < $variation_bound ) {
			$this->enqueue_variation_batch( $scan_id, $variation_offset );
			return;
		}

		$this->enqueue_finalize( $scan_id );
	}

	/**
	 * @param string $scan_id Scan id.
	 * @param int    $offset  Product offset.
	 */
	public function enqueue_catalog_batch( string $scan_id, int $offset ): void {
		// Wrap args in a single element so the callback receives one array (AS expands values).
		as_enqueue_async_action(
			self::HOOK_CATALOG,
			array(
				array(
					'scan_id' => $scan_id,
					'offset'  => $offset,
				),
			),
			self::GROUP
		);
	}

	/**
	 * @param string $scan_id Scan id.
	 * @param int    $offset  Variation offset.
	 */
	public function enqueue_variation_batch( string $scan_id, int $offset ): void {
		as_enqueue_async_action(
			self::HOOK_VARIATION,
			array(
				array(
					'scan_id' => $scan_id,
					'offset'  => $offset,
				),
			),
			self::GROUP
		);
	}

	/**
	 * @param string $scan_id Scan id.
	 */
	public function enqueue_finalize( string $scan_id ): void {
		as_enqueue_async_action(
			self::HOOK_FINALIZE,
			array(
				array( 'scan_id' => $scan_id ),
			),
			self::GROUP
		);
	}

	/**
	 * Clear pending actions for this plugin group.
	 *
	 * @param string|null $scan_id Optional scan id (group-wide clear).
	 */
	public function cancel_scan_work( ?string $scan_id = null ): void {
		unset( $scan_id );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::HOOK_CATALOG, null, self::GROUP );
			as_unschedule_all_actions( self::HOOK_VARIATION, null, self::GROUP );
			as_unschedule_all_actions( self::HOOK_FINALIZE, null, self::GROUP );
		}
	}

	/**
	 * Process pending STMC scan actions inline (admin polls / cron-challenged hosts).
	 *
	 * Action Scheduler async loopbacks often fail when siteurl is unreachable from
	 * the PHP process (e.g. Docker published as localhost:8888). Draining a few
	 * pending actions on status polls keeps hybrid scans moving.
	 *
	 * @param int $limit Max actions to run this call.
	 * @return int Number of actions processed.
	 */
	public function run_pending( int $limit = 3 ): int {
		$limit = max( 1, min( 10, $limit ) );

		if ( class_exists( 'ActionScheduler_QueueRunner' ) && function_exists( 'as_get_scheduled_actions' ) ) {
			return $this->run_pending_via_action_scheduler( $limit );
		}

		return $this->run_pending_from_test_queue( $limit );
	}

	/**
	 * @param int $limit Max actions.
	 */
	private function run_pending_via_action_scheduler( int $limit ): int {
		$status = class_exists( 'ActionScheduler_Store' )
			? ActionScheduler_Store::STATUS_PENDING
			: 'pending';

		$ids = as_get_scheduled_actions(
			array(
				'group'    => self::GROUP,
				'status'   => $status,
				'per_page' => $limit,
				'orderby'  => 'date',
				'order'    => 'ASC',
			),
			'ids'
		);

		if ( ! is_array( $ids ) || array() === $ids ) {
			return 0;
		}

		$runner = ActionScheduler_QueueRunner::instance();
		$done   = 0;
		foreach ( $ids as $id ) {
			$runner->process_action( (int) $id, 'STMC' );
			++$done;
		}

		return $done;
	}

	/**
	 * Unit-test path: drain the in-memory AS stub queue used by PHPUnit.
	 *
	 * @param int $limit Max actions.
	 */
	private function run_pending_from_test_queue( int $limit ): int {
		if ( empty( $GLOBALS['stmc_test_as'] ) || ! is_array( $GLOBALS['stmc_test_as'] ) ) {
			return 0;
		}

		$done  = 0;
		$queue = &$GLOBALS['stmc_test_as'];
		while ( $done < $limit && ! empty( $queue ) ) {
			$action = array_shift( $queue );
			if ( ! is_array( $action ) ) {
				continue;
			}
			if ( self::GROUP !== (string) ( $action['group'] ?? '' ) ) {
				continue;
			}

			$hook = (string) ( $action['hook'] ?? '' );
			$args = is_array( $action['args'] ?? null ) ? $action['args'] : array();
			if ( isset( $args[0] ) && is_array( $args[0] ) ) {
				$args = $args[0];
			}

			if ( self::HOOK_CATALOG === $hook ) {
				$this->handle_catalog_batch( $args );
			} elseif ( self::HOOK_VARIATION === $hook ) {
				$this->handle_variation_batch( $args );
			} elseif ( self::HOOK_FINALIZE === $hook ) {
				$this->handle_finalize( $args );
			}

			++$done;
		}

		return $done;
	}

	/**
	 * Catalog batch handler.
	 *
	 * @param array<string, mixed> $args Hook args.
	 */
	public function handle_catalog_batch( $args = array() ): void {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$this->make_engine()->on_catalog_batch( $args );
	}

	/**
	 * Variation batch handler.
	 *
	 * @param array<string, mixed> $args Hook args.
	 */
	public function handle_variation_batch( $args = array() ): void {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$this->make_engine()->on_variation_batch( $args );
	}

	/**
	 * Finalize handler.
	 *
	 * @param array<string, mixed> $args Hook args.
	 */
	public function handle_finalize( $args = array() ): void {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$this->make_engine()->finalize( (string) ( $args['scan_id'] ?? '' ) );
	}

	/**
	 * Build engine with shared registry/catalog.
	 */
	private function make_engine(): STMC_Scan_Engine {
		return new STMC_Scan_Engine(
			new STMC_Options(),
			new STMC_Score(),
			$this,
			$this->registry,
			$this->catalog ?? new STMC_Catalog_Source_Memory()
		);
	}
}
