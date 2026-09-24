<?php
/**
 * Docker smoke: start scan and advance only via GET /scan/status (no QueueRunner).
 *
 * @package Store_Maintenance_Checklist
 */

wp_set_current_user( 1 );

$cancel = rest_do_request( new WP_REST_Request( 'POST', '/stmc/v1/scan/cancel' ) );
echo 'CANCEL HTTP ' . $cancel->get_status() . PHP_EOL;

$start = rest_do_request( new WP_REST_Request( 'POST', '/stmc/v1/scan' ) );
echo 'START HTTP ' . $start->get_status() . PHP_EOL;
$start_data = $start->get_data();
echo 'start_progress=' . ( $start_data['status']['progress_percent'] ?? '?' ) . PHP_EOL;

$guard = 0;
while ( $guard < 40 ) {
	++$guard;
	$status = rest_do_request( new WP_REST_Request( 'GET', '/stmc/v1/scan/status' ) )->get_data();
	$state  = is_array( $status ) ? ( $status['state'] ?? '' ) : '';
	echo 'POLL ' . $guard . ' ' . $state
		. ' progress=' . ( $status['progress_percent'] ?? '?' )
		. ' batch=' . ( $status['batch_current'] ?? 0 ) . '/' . ( $status['batch_total'] ?? 0 )
		. ' products=' . ( $status['products_scanned'] ?? 0 )
		. PHP_EOL;

	if ( in_array( $state, array( 'completed', 'cancelled', 'failed', 'idle' ), true ) ) {
		break;
	}
}

echo 'DONE state=' . ( $status['state'] ?? '?' ) . ' progress=' . ( $status['progress_percent'] ?? '?' ) . PHP_EOL;
