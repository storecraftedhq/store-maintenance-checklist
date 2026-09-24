<?php
/**
 * Seed aged WooCommerce orders for Docker QA (older than ~3 years by default).
 *
 * Usage (via WP-CLI):
 *   wp eval-file bin/seed-aged-orders.php 40 2019-01-01 2022-09-01
 *
 * Positional args: [count] [date-start] [date-end]
 * Env fallbacks: AGED_GEN_COUNT, AGED_GEN_START, AGED_GEN_END
 *
 * @package Store_Maintenance_Checklist
 */

if ( ! function_exists( 'wc_create_order' ) || ! function_exists( 'wc_get_products' ) ) {
	WP_CLI::error( 'WooCommerce is required.' );
}

$cli_args = ( isset( $args ) && is_array( $args ) ) ? array_values( $args ) : array();

$count      = max( 1, (int) ( $cli_args[0] ?? getenv( 'AGED_GEN_COUNT' ) ?: 40 ) );
$date_start = (string) ( $cli_args[1] ?? getenv( 'AGED_GEN_START' ) ?: '2019-01-01' );
$date_end   = (string) ( $cli_args[2] ?? getenv( 'AGED_GEN_END' ) ?: '2022-09-01' );

$start = strtotime( $date_start . ' UTC' );
$end   = strtotime( $date_end . ' UTC' );
if ( false === $start || false === $end || $end < $start ) {
	WP_CLI::error( 'Invalid date-start / date-end.' );
}

$products = wc_get_products(
	array(
		'limit'  => 20,
		'status' => 'publish',
		'return' => 'ids',
	)
);
if ( empty( $products ) ) {
	WP_CLI::error( 'No published products found. Generate products first.' );
}

$statuses = array(
	'completed',
	'completed',
	'completed',
	'completed',
	'cancelled',
	'cancelled',
	'pending',
	'on-hold',
	'failed',
	'completed',
);

$created = 0;
for ( $i = 0; $i < $count; $i++ ) {
	$product_id = $products[ array_rand( $products ) ];
	$product    = wc_get_product( $product_id );
	if ( ! $product ) {
		continue;
	}

	$ts     = random_int( $start, $end );
	$date   = gmdate( 'Y-m-d H:i:s', $ts );
	$status = $statuses[ $i % count( $statuses ) ];

	$order = wc_create_order();
	$order->add_product( $product, 1 );
	$order->set_billing_first_name( 'Aged' );
	$order->set_billing_last_name( 'Customer' . ( $i + 1 ) );
	$order->set_billing_email( 'aged' . ( $i + 1 ) . '@example.com' );
	$order->set_billing_country( 'US' );
	$order->set_date_created( $date );
	$order->calculate_totals();
	$order->set_status( $status );
	$order->save();

	// Status transitions can rewrite dates — pin them again.
	$order->set_date_created( $date );
	if ( method_exists( $order, 'set_date_paid' ) && in_array( $status, array( 'completed', 'processing' ), true ) ) {
		$order->set_date_paid( $date );
	}
	$order->save();
	++$created;
}

WP_CLI::success(
	sprintf(
		'Created %d aged orders (%s–%s).',
		$created,
		$date_start,
		$date_end
	)
);
