<?php
/**
 * Docker smoke: cancel stuck scan, start, run AS until terminal.
 *
 * @package Store_Maintenance_Checklist
 */

wp_set_current_user( 1 );

// Clear any stuck scan from prior smoke runs.
$cancel = rest_do_request( new WP_REST_Request( 'POST', '/stmc/v1/scan/cancel' ) );
echo 'CANCEL HTTP ' . $cancel->get_status() . PHP_EOL;

$start = rest_do_request( new WP_REST_Request( 'POST', '/stmc/v1/scan' ) );
echo 'START HTTP ' . $start->get_status() . PHP_EOL;
echo 'start_body=' . wp_json_encode( $start->get_data() ) . PHP_EOL;

$guard = 0;
while ( $guard < 30 ) {
	++$guard;
	$status = rest_do_request( new WP_REST_Request( 'GET', '/stmc/v1/scan/status' ) )->get_data();
	$state  = is_array( $status ) ? ( $status['state'] ?? '' ) : '';
	echo 'STATUS ' . $state
		. ' progress=' . ( $status['progress_percent'] ?? '?' )
		. ' batch=' . ( $status['batch_current'] ?? 0 ) . '/' . ( $status['batch_total'] ?? 0 )
		. ' provisional=' . ( ! empty( $status['provisional'] ) ? '1' : '0' )
		. PHP_EOL;

	if ( in_array( $state, array( 'completed', 'cancelled', 'failed', 'idle' ), true ) ) {
		break;
	}

	if ( class_exists( 'ActionScheduler_QueueRunner' ) ) {
		ActionScheduler_QueueRunner::instance()->run();
	}
}

$scan = rest_do_request( new WP_REST_Request( 'GET', '/stmc/v1/scan' ) )->get_data();
echo 'FINAL state=' . ( $scan['status']['state'] ?? '?' ) . PHP_EOL;
echo 'summary=' . wp_json_encode( $scan['summary'] ?? null ) . PHP_EOL;
echo 'findings=' . count( $scan['findings'] ?? array() ) . ' passed=' . count( $scan['passed'] ?? array() ) . PHP_EOL;
$ids = array();
foreach ( array_merge( $scan['findings'] ?? array(), $scan['passed'] ?? array() ) as $f ) {
	$ids[] = ( $f['id'] ?? '' ) . ':' . ( $f['status'] ?? '' );
}
sort( $ids );
echo 'results=' . implode( ', ', $ids ) . PHP_EOL;
