<?php
/**
 * Scan state machine (hybrid sync + AS catalog).
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

/**
 * Hybrid sync + AS scan runner.
 */
final class STMC_Scan_Engine {

	private const STALL_SECONDS = 480; // 8 minutes.

	/**
	 * @var STMC_Options
	 */
	private $options;

	/**
	 * @var STMC_Score
	 */
	private $score;

	/**
	 * @var STMC_Scan_Scheduler
	 */
	private $scheduler;

	/**
	 * @var STMC_Checks_Registry|null
	 */
	private $registry;

	/**
	 * @var STMC_Catalog_Source
	 */
	private $catalog;

	/**
	 * @param STMC_Options               $options   Options store.
	 * @param STMC_Score                 $score     Score service.
	 * @param STMC_Scan_Scheduler        $scheduler AS scheduler.
	 * @param STMC_Checks_Registry|null  $registry  Check registry (null = Phase 2 BC, no sync checks).
	 * @param STMC_Catalog_Source|null   $catalog   Catalog source (null = empty memory).
	 */
	public function __construct(
		STMC_Options $options,
		STMC_Score $score,
		STMC_Scan_Scheduler $scheduler,
		?STMC_Checks_Registry $registry = null,
		?STMC_Catalog_Source $catalog = null
	) {
		$this->options   = $options;
		$this->score     = $score;
		$this->scheduler = $scheduler;
		$this->registry  = $registry;
		$this->catalog   = $catalog ?? new STMC_Catalog_Source_Memory();
	}

	/**
	 * Current scan status payload (idle when no active state).
	 *
	 * @return array<string, mixed>
	 */
	public function get_status(): array {
		$state = $this->options->get_scan_state();
		if ( ! empty( $state ) && 'running' === ( $state['state'] ?? '' ) ) {
			// Advance hybrid batches when WP-Cron / AS async loopback is idle.
			$this->scheduler->run_pending( 3 );
			$state = $this->options->get_scan_state();
		}

		if ( empty( $state ) ) {
			$latest   = $this->options->get_latest_results();
			$terminal = (string) ( $latest['status']['state'] ?? 'idle' );
			if ( in_array( $terminal, array( 'completed', 'cancelled', 'failed' ), true ) ) {
				return $this->normalize_status( $latest['status'] );
			}
			return $this->idle_status();
		}

		$status            = $this->normalize_status( $state );
		$status['stalled'] = $this->compute_stalled( $status );
		return $status;
	}

	/**
	 * Start a scan; 409 if already running.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function start() {
		$current = $this->options->get_scan_state();
		if ( ! empty( $current ) && 'running' === ( $current['state'] ?? '' ) ) {
			return new WP_Error(
				'stmc_scan_in_progress',
				__( 'A scan is already in progress.', 'store-maintenance-checklist' ),
				array( 'status' => 409 )
			);
		}

		$now            = gmdate( 'Y-m-d\TH:i:s\Z' );
		$scan_id        = 'scan_' . wp_generate_uuid4();
		$settings       = $this->options->get_settings();
		$max_products   = (int) $settings['max_products'];
		$max_variations = (int) $settings['max_variations'];

		$snapshot = ( new STMC_Check_Snapshot_Builder( $this->options ) )->build();
		$findings = array();
		$acc      = array();

		if ( $this->registry ) {
			$findings = $this->registry->run_sync( $snapshot );
			$acc      = $this->registry->empty_catalog_accumulators();
		}

		$product_total = $this->catalog->count_products();
		$product_bound = min( $product_total, $max_products );
		$partial       = $product_total > $max_products;

		$variation_total = $this->catalog->count_variations();
		$variation_bound = min( $variation_total, $max_variations );

		$product_batches   = $product_bound > 0
			? (int) ceil( $product_bound / STMC_Checks_Registry::PRODUCTS_PER_BATCH )
			: 0;
		$variation_batches = $variation_bound > 0
			? (int) ceil( $variation_bound / STMC_Checks_Registry::VARIATIONS_PER_BATCH )
			: 0;
		$batch_total       = $product_batches + $variation_batches;

		$working = array(
			'findings'             => $findings,
			'sync_finished'        => true,
			'batches_completed'    => 0,
			'catalog_acc'          => $acc,
			'product_bound'        => $product_bound,
			'variation_bound'      => $variation_bound,
			'products_remaining'   => $product_bound,
			'variations_remaining' => $variation_bound,
		);

		$status = array(
			'scan_id'          => $scan_id,
			'state'            => 'running',
			'progress_percent' => 10,
			'phase'            => $product_bound > 0 ? 'catalog' : ( $variation_bound > 0 ? 'variations' : 'finalize' ),
			'phase_label'      => $product_bound > 0
				? __( 'Scanning products', 'store-maintenance-checklist' )
				: ( $variation_bound > 0
					? __( 'Scanning variations', 'store-maintenance-checklist' )
					: __( 'Finalizing', 'store-maintenance-checklist' ) ),
			'batch_current'    => 0,
			'batch_total'      => $batch_total,
			'provisional'      => false,
			'partial_catalog'  => $partial,
			'products_scanned' => 0,
			'products_bound'   => $max_products,
			'started_at'       => $now,
			'updated_at'       => $now,
			'last_batch_at'    => null,
			'stalled'          => false,
			'error'            => null,
			'working'          => $working,
		);

		$this->options->set_scan_state( $status );

		if ( $product_bound > 0 ) {
			$this->scheduler->enqueue_catalog_batch( $scan_id, 0 );
		} elseif ( $variation_bound > 0 ) {
			$this->scheduler->enqueue_variation_batch( $scan_id, 0 );
		} else {
			$this->scheduler->enqueue_finalize( $scan_id );
		}

		return array(
			'status' => $this->public_status( $status ),
		);
	}

	/**
	 * Cancel in-progress scan.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function cancel() {
		$state = $this->options->get_scan_state();
		if ( empty( $state ) || 'running' !== ( $state['state'] ?? '' ) ) {
			return new WP_Error(
				'stmc_no_active_scan',
				__( 'There is no active scan to cancel.', 'store-maintenance-checklist' ),
				array( 'status' => 409 )
			);
		}

		$this->scheduler->cancel_scan_work( (string) ( $state['scan_id'] ?? '' ) );

		$working           = is_array( $state['working'] ?? null ) ? $state['working'] : array();
		$sync_finished     = ! empty( $working['sync_finished'] );
		$batches_completed = (int) ( $working['batches_completed'] ?? 0 );
		$findings          = is_array( $working['findings'] ?? null ) ? $working['findings'] : array();

		// History only when there was real progress (findings and/or AS batches).
		$has_progress = $batches_completed > 0 || ( $sync_finished && ! empty( $findings ) );

		$now    = gmdate( 'Y-m-d\TH:i:s\Z' );
		$status = array_merge(
			$state,
			array(
				'state'      => 'cancelled',
				'updated_at' => $now,
				'stalled'    => false,
			)
		);
		unset( $status['working'] );

		$prepared = $this->prepare_terminal_findings( $findings );
		$findings = $prepared['findings'];
		$changes  = $prepared['changes'];

		$summary = $this->score->summarize( $findings, $this->options->get_ignores(), ! empty( $status['provisional'] ) || ! empty( $status['partial_catalog'] ) );
		$payload = array(
			'status'      => $this->public_status( $status ),
			'summary'     => $summary,
			'findings'    => $this->open_findings( $findings ),
			'passed'      => $this->passed_findings( $findings ),
			'environment' => $this->environment_payload(),
		);

		if ( $has_progress ) {
			$this->options->set_latest_results( $payload );
			$this->options->prepend_history( $this->history_row( $payload, 'cancelled', $changes ) );
		}

		$this->options->clear_scan_state();

		return $payload;
	}

	/**
	 * Retry stalled scan: re-queue remaining work.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function retry() {
		$state = $this->options->get_scan_state();
		if ( empty( $state ) || 'running' !== ( $state['state'] ?? '' ) ) {
			return new WP_Error(
				'stmc_no_active_scan',
				__( 'There is no stalled scan to retry.', 'store-maintenance-checklist' ),
				array( 'status' => 409 )
			);
		}

		$status = $this->normalize_status( $state );
		if ( ! $this->compute_stalled( $status ) ) {
			return new WP_Error(
				'stmc_no_active_scan',
				__( 'There is no stalled scan to retry.', 'store-maintenance-checklist' ),
				array( 'status' => 409 )
			);
		}

		$scan_id                = (string) ( $state['scan_id'] ?? '' );
		$state['updated_at']    = gmdate( 'Y-m-d\TH:i:s\Z' );
		$state['last_batch_at'] = $state['updated_at'];
		$state['stalled']       = false;
		$this->options->set_scan_state( $state );
		$this->scheduler->enqueue_scan_work( $scan_id );

		return array(
			'status' => $this->public_status( $state ),
		);
	}

	/**
	 * Catalog product batch callback.
	 *
	 * @param array<string, mixed> $args Args.
	 */
	public function on_catalog_batch( array $args ): void {
		$state = $this->load_running_state( $args );
		if ( null === $state || ! $this->registry ) {
			return;
		}

		$scan_id = (string) ( $state['scan_id'] ?? '' );
		$offset  = (int) ( $args['offset'] ?? 0 );
		$working = is_array( $state['working'] ?? null ) ? $state['working'] : array();
		$bound   = (int) ( $working['product_bound'] ?? 0 );
		$limit   = min( STMC_Checks_Registry::PRODUCTS_PER_BATCH, max( 0, $bound - $offset ) );

		$acc = is_array( $working['catalog_acc'] ?? null )
			? $working['catalog_acc']
			: $this->registry->empty_catalog_accumulators();

		if ( $limit > 0 ) {
			$products = $this->catalog->get_products( $offset, $limit );
			$this->registry->accumulate_products( $acc, $products );
			$working['catalog_acc']        = $acc;
			$working['batches_completed']  = (int) ( $working['batches_completed'] ?? 0 ) + 1;
			$state['products_scanned']     = (int) ( $state['products_scanned'] ?? 0 ) + count( $products );
			$working['products_remaining'] = max( 0, $bound - ( $offset + $limit ) );
		}

		$now                       = gmdate( 'Y-m-d\TH:i:s\Z' );
		$state['working']          = $working;
		$state['phase']            = 'catalog';
		$state['phase_label']      = __( 'Scanning products', 'store-maintenance-checklist' );
		$state['updated_at']       = $now;
		$state['last_batch_at']    = $now;
		$state['batch_current']    = (int) ( $working['batches_completed'] ?? 0 );
		$state['progress_percent'] = $this->progress_percent( $state );
		if ( ! empty( $state['partial_catalog'] ) ) {
			$state['provisional'] = true;
		}
		$this->options->set_scan_state( $state );

		$next_offset = $offset + STMC_Checks_Registry::PRODUCTS_PER_BATCH;
		if ( $next_offset < $bound ) {
			$this->scheduler->enqueue_catalog_batch( $scan_id, $next_offset );
			return;
		}

		$variation_bound = (int) ( $working['variation_bound'] ?? 0 );
		if ( $variation_bound > 0 ) {
			$this->scheduler->enqueue_variation_batch( $scan_id, 0 );
			return;
		}

		$this->scheduler->enqueue_finalize( $scan_id );
	}

	/**
	 * Variation batch callback.
	 *
	 * @param array<string, mixed> $args Args.
	 */
	public function on_variation_batch( array $args ): void {
		$state = $this->load_running_state( $args );
		if ( null === $state || ! $this->registry ) {
			return;
		}

		$scan_id = (string) ( $state['scan_id'] ?? '' );
		$offset  = (int) ( $args['offset'] ?? 0 );
		$working = is_array( $state['working'] ?? null ) ? $state['working'] : array();
		$bound   = (int) ( $working['variation_bound'] ?? 0 );
		$limit   = min( STMC_Checks_Registry::VARIATIONS_PER_BATCH, max( 0, $bound - $offset ) );

		$acc = is_array( $working['catalog_acc'] ?? null )
			? $working['catalog_acc']
			: $this->registry->empty_catalog_accumulators();

		if ( $limit > 0 ) {
			$variations = $this->catalog->get_variations( $offset, $limit );
			$this->registry->accumulate_variations( $acc, $variations );
			$working['catalog_acc']          = $acc;
			$working['batches_completed']    = (int) ( $working['batches_completed'] ?? 0 ) + 1;
			$working['variations_remaining'] = max( 0, $bound - ( $offset + $limit ) );
		}

		$now                       = gmdate( 'Y-m-d\TH:i:s\Z' );
		$state['working']          = $working;
		$state['phase']            = 'variations';
		$state['phase_label']      = __( 'Scanning variations', 'store-maintenance-checklist' );
		$state['updated_at']       = $now;
		$state['last_batch_at']    = $now;
		$state['batch_current']    = (int) ( $working['batches_completed'] ?? 0 );
		$state['progress_percent'] = $this->progress_percent( $state );
		$this->options->set_scan_state( $state );

		$next_offset = $offset + STMC_Checks_Registry::VARIATIONS_PER_BATCH;
		if ( $next_offset < $bound ) {
			$this->scheduler->enqueue_variation_batch( $scan_id, $next_offset );
			return;
		}

		$this->scheduler->enqueue_finalize( $scan_id );
	}

	/**
	 * Finalize scan: score, persist, history, completed.
	 *
	 * @param string $scan_id Scan id.
	 */
	public function finalize( string $scan_id ): void {
		$state = $this->options->get_scan_state();
		if ( empty( $state ) || (string) ( $state['scan_id'] ?? '' ) !== $scan_id ) {
			return;
		}

		$working  = is_array( $state['working'] ?? null ) ? $state['working'] : array();
		$findings = is_array( $working['findings'] ?? null ) ? $working['findings'] : array();

		if ( $this->registry ) {
			$acc              = is_array( $working['catalog_acc'] ?? null )
				? $working['catalog_acc']
				: $this->registry->empty_catalog_accumulators();
			$catalog_findings = $this->registry->finalize_catalog_findings( $acc );
			$findings         = array_merge( $findings, $catalog_findings );
		}

		$findings = $this->apply_ignores( $findings );

		$provisional = ! empty( $state['partial_catalog'] ) || ! empty( $state['provisional'] );

		$now    = gmdate( 'Y-m-d\TH:i:s\Z' );
		$status = array_merge(
			$state,
			array(
				'state'            => 'completed',
				'progress_percent' => 100,
				'phase'            => 'done',
				'phase_label'      => __( 'Scan complete', 'store-maintenance-checklist' ),
				'updated_at'       => $now,
				'last_batch_at'    => $now,
				'stalled'          => false,
				'provisional'      => $provisional,
			)
		);
		unset( $status['working'] );

		$prepared = $this->prepare_terminal_findings( $findings );
		$findings = $prepared['findings'];
		$changes  = $prepared['changes'];

		$summary = $this->score->summarize(
			$findings,
			$this->options->get_ignores(),
			$provisional
		);

		$payload = array(
			'status'            => $this->public_status( $status ),
			'summary'           => $summary,
			'findings'          => $this->open_findings( $findings ),
			'passed'            => $this->passed_findings( $findings ),
			'last_completed_at' => $now,
			'environment'       => $this->environment_payload(),
		);

		$this->options->set_latest_results( $payload );
		$this->options->prepend_history( $this->history_row( $payload, 'completed', $changes ) );
		$this->options->clear_scan_state();
		$this->scheduler->cancel_scan_work( $scan_id );
	}

	/**
	 * Bootstrap payload for GET /scan.
	 *
	 * @return array<string, mixed>
	 */
	public function get_scan_payload(): array {
		$latest = $this->options->get_latest_results();
		$status = $this->get_status();

		$summary  = is_array( $latest['summary'] ?? null ) ? $latest['summary'] : null;
		$findings = is_array( $latest['findings'] ?? null ) ? $latest['findings'] : array();
		$passed   = is_array( $latest['passed'] ?? null ) ? $latest['passed'] : array();

		return array(
			'status'            => $status,
			'summary'           => $summary,
			'environment'       => $this->environment_payload(),
			'findings'          => $findings,
			'passed'            => $passed,
			'last_completed_at' => $latest['last_completed_at'] ?? null,
		);
	}

	/**
	 * @param array<string, mixed> $args Args.
	 * @return array<string, mixed>|null
	 */
	private function load_running_state( array $args ): ?array {
		$state = $this->options->get_scan_state();
		if ( empty( $state ) || 'running' !== ( $state['state'] ?? '' ) ) {
			return null;
		}
		if ( (string) ( $args['scan_id'] ?? '' ) !== (string) ( $state['scan_id'] ?? '' ) ) {
			return null;
		}
		return $state;
	}

	/**
	 * @param array<string, mixed> $status Status.
	 */
	private function progress_percent( array $status ): int {
		$total   = max( 1, (int) ( $status['batch_total'] ?? 0 ) );
		$current = (int) ( $status['batch_current'] ?? 0 );
		return min( 95, 10 + (int) floor( ( $current / $total ) * 85 ) );
	}

	/**
	 * @param array<string, mixed> $status Status.
	 * @return bool
	 */
	private function compute_stalled( array $status ): bool {
		if ( 'running' !== ( $status['state'] ?? '' ) ) {
			return false;
		}
		$now = time();
		$ref = null;
		if ( ! empty( $status['last_batch_at'] ) ) {
			$ref = strtotime( (string) $status['last_batch_at'] );
		} elseif ( ! empty( $status['started_at'] ) ) {
			$ref = strtotime( (string) $status['started_at'] );
		}
		if ( ! $ref ) {
			return false;
		}
		return ( $now - $ref ) >= self::STALL_SECONDS;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function idle_status(): array {
		return array(
			'state'            => 'idle',
			'progress_percent' => 0,
			'phase'            => null,
			'phase_label'      => null,
			'batch_current'    => 0,
			'batch_total'      => 0,
			'provisional'      => false,
			'partial_catalog'  => false,
			'products_scanned' => 0,
			'products_bound'   => (int) $this->options->get_settings()['max_products'],
			'started_at'       => null,
			'updated_at'       => null,
			'last_batch_at'    => null,
			'stalled'          => false,
			'error'            => null,
		);
	}

	/**
	 * @param array<string, mixed> $status Raw status.
	 * @return array<string, mixed>
	 */
	private function normalize_status( array $status ): array {
		return array_merge( $this->idle_status(), $status );
	}

	/**
	 * Strip internal working keys for API responses.
	 *
	 * @param array<string, mixed> $status Status.
	 * @return array<string, mixed>
	 */
	private function public_status( array $status ): array {
		$public = $this->normalize_status( $status );
		unset( $public['working'], $public['scan_id'] );
		$public['stalled'] = $this->compute_stalled( $public );
		return $public;
	}

	/**
	 * @param array<int, array<string, mixed>> $findings Findings.
	 * @return array<int, array<string, mixed>>
	 */
	private function apply_ignores( array $findings ): array {
		$ignores = $this->options->get_ignores();
		foreach ( $findings as $i => $finding ) {
			$id = (string) ( $finding['id'] ?? '' );
			if ( isset( $ignores[ $id ] ) ) {
				$findings[ $i ]['status']         = 'ignored';
				$findings[ $i ]['score_excluded'] = true;
				$findings[ $i ]['ignore_reason']  = (string) ( $ignores[ $id ]['reason'] ?? '' );
			}
			if ( in_array( $id, STMC_Score::UNSCORED_IDS, true ) ) {
				$findings[ $i ]['score_excluded'] = true;
			}
		}
		return $findings;
	}

	/**
	 * @param array<int, array<string, mixed>> $findings Findings.
	 * @return array<int, array<string, mixed>>
	 */
	private function open_findings( array $findings ): array {
		return array_values(
			array_filter(
				$findings,
				static function ( $f ) {
					return 'passed' !== ( $f['status'] ?? '' );
				}
			)
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $findings Findings.
	 * @return array<int, array<string, mixed>>
	 */
	private function passed_findings( array $findings ): array {
		return array_values(
			array_filter(
				$findings,
				static function ( $f ) {
					return 'passed' === ( $f['status'] ?? '' );
				}
			)
		);
	}

	/**
	 * Soften + annotate findings for a terminal scan write.
	 *
	 * @param array<int, array<string, mixed>> $findings Raw findings after ignores.
	 * @return array{findings: array<int, array<string, mixed>>, changes: array{new: int, resolved: int, unchanged: int, worsened: int}}
	 */
	private function prepare_terminal_findings( array $findings ): array {
		$previous = $this->previous_findings_for_diff();
		$env      = new STMC_Environment( $this->options );
		$findings = ( new STMC_Softening() )->apply( $findings, $env->treat_as_production() );

		$diff     = new STMC_History_Diff();
		$findings = $diff->annotate( $findings, $previous );
		$changes  = $diff->counts( $findings, $previous );

		return array(
			'findings' => $findings,
			'changes'  => $changes,
		);
	}

	/**
	 * Findings from the last terminal payload for history comparison.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function previous_findings_for_diff(): array {
		$latest = $this->options->get_latest_results();
		if ( empty( $latest ) ) {
			return array();
		}

		$open   = is_array( $latest['findings'] ?? null ) ? $latest['findings'] : array();
		$passed = is_array( $latest['passed'] ?? null ) ? $latest['passed'] : array();

		return array_merge( $open, $passed );
	}

	/**
	 * @param array<string, mixed>                                                   $payload  Terminal payload.
	 * @param string                                                                 $terminal Terminal state.
	 * @param array{new?: int, resolved?: int, unchanged?: int, worsened?: int}|null $changes  Diff counts.
	 * @return array<string, mixed>
	 */
	private function history_row( array $payload, string $terminal, ?array $changes = null ): array {
		$summary = is_array( $payload['summary'] ?? null ) ? $payload['summary'] : array();
		$env     = is_array( $payload['environment'] ?? null ) ? $payload['environment'] : $this->environment_payload();

		if ( null === $changes ) {
			$changes = array(
				'new'       => 0,
				'resolved'  => 0,
				'unchanged' => 0,
				'worsened'  => 0,
			);
		}

		return array(
			'id'              => 'hist_' . wp_generate_uuid4(),
			'timestamp'       => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'score'           => (int) ( $summary['score'] ?? 0 ),
			'provisional'     => ! empty( $summary['provisional'] ),
			'environment'     => (string) ( $env['type'] ?? 'production' ),
			'softened'        => ! empty( $env['softened'] ),
			'partial_catalog' => ! empty( $payload['status']['partial_catalog'] ),
			'counts'          => array(
				'critical' => (int) ( $summary['critical'] ?? 0 ),
				'warning'  => (int) ( $summary['warning'] ?? 0 ),
				'info'     => (int) ( $summary['info'] ?? 0 ),
				'passed'   => (int) ( $summary['passed'] ?? 0 ),
				'ignored'  => (int) ( $summary['ignored'] ?? 0 ),
			),
			'terminal_state'  => $terminal,
			'changes'         => array(
				'new'       => (int) ( $changes['new'] ?? 0 ),
				'resolved'  => (int) ( $changes['resolved'] ?? 0 ),
				'unchanged' => (int) ( $changes['unchanged'] ?? 0 ),
				'worsened'  => (int) ( $changes['worsened'] ?? 0 ),
			),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function environment_payload(): array {
		$env      = new STMC_Environment( $this->options );
		$treat    = $env->treat_as_production();
		$settings = $this->options->get_settings();
		$force    = ! empty( $settings['force_production_severity'] )
			|| ( defined( 'STMC_FORCE_PRODUCTION_SEVERITY' ) && STMC_FORCE_PRODUCTION_SEVERITY );

		if ( ! $force && function_exists( 'apply_filters' ) ) {
			$force = true === apply_filters( 'stmc_treat_as_production', null );
		}

		return array(
			'type'             => $env->type(),
			'softened'         => ! $treat,
			'force_production' => (bool) $force,
		);
	}
}
