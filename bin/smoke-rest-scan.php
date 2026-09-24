<?php
/**
 * Docker smoke: GET /stmc/v1/scan as admin.
 *
 * @package Store_Maintenance_Checklist
 */

wp_set_current_user( 1 );
$request  = new WP_REST_Request( 'GET', '/stmc/v1/scan' );
$response = rest_do_request( $request );
$status   = $response->get_status();
$data     = $response->get_data();

echo 'HTTP ' . $status . PHP_EOL;
echo 'state=' . ( $data['status']['state'] ?? 'missing' ) . PHP_EOL;
echo wp_json_encode( $data['status'] ?? array(), JSON_PRETTY_PRINT ) . PHP_EOL;
