/**
 * @jest-environment jsdom
 */

import { filterByMetric, filterFindings, isOpenFinding } from './filters';

const SAMPLE = [
	{
		id: 'payments.gateway_test_mode',
		title: 'Payment gateway in test mode',
		severity: 'critical',
		area: 'payments',
		status: 'open',
		why: 'Live customers may not be charged.',
	},
	{
		id: 'catalog.incomplete_variation_prices',
		title: 'Incomplete variation prices',
		severity: 'warning',
		area: 'catalog',
		status: 'open',
		why: 'Shoppers can hit a product they cannot purchase.',
	},
	{
		id: 'orders.aged_completed_volume',
		title: 'Aged completed orders',
		severity: 'warning',
		area: 'orders',
		status: 'open',
		why: 'Large order tables can slow admin.',
	},
	{
		id: 'email.weak_from_address',
		title: 'Weak From address',
		severity: 'info',
		area: 'email',
		status: 'open',
		why: 'May land in spam.',
	},
	{
		id: 'shipping.empty_default_zone',
		title: 'Empty default zone',
		severity: 'warning',
		area: 'shipping',
		status: 'ignored',
		why: 'Blocks checkout.',
		ignore_reason: 'B2B quote-only',
	},
	{
		id: 'catalog.required_pages',
		title: 'Pages assigned',
		severity: 'passed',
		area: 'catalog',
		status: 'passed',
	},
];

describe( 'isOpenFinding', () => {
	it( 'accepts open critical/warning/info only', () => {
		expect( isOpenFinding( SAMPLE[ 0 ] ) ).toBe( true );
		expect( isOpenFinding( SAMPLE[ 4 ] ) ).toBe( false );
		expect( isOpenFinding( SAMPLE[ 5 ] ) ).toBe( false );
	} );
} );

describe( 'filterByMetric', () => {
	it( 'open / all returns all open findings (excludes ignored and passed)', () => {
		const open = filterByMetric( SAMPLE, 'open' );
		const all = filterByMetric( SAMPLE, 'all' );
		expect( open ).toHaveLength( 4 );
		expect( all ).toHaveLength( 4 );
		expect( open.every( ( f ) => f.status === 'open' ) ).toBe( true );
	} );

	it( 'critical returns only open critical', () => {
		const result = filterByMetric( SAMPLE, 'critical' );
		expect( result ).toHaveLength( 1 );
		expect( result[ 0 ].id ).toBe( 'payments.gateway_test_mode' );
	} );

	it( 'warning returns only open warnings', () => {
		const result = filterByMetric( SAMPLE, 'warning' );
		expect( result ).toHaveLength( 2 );
		expect( result.map( ( f ) => f.id ) ).toEqual( [
			'catalog.incomplete_variation_prices',
			'orders.aged_completed_volume',
		] );
	} );

	it( 'info returns only open info', () => {
		const result = filterByMetric( SAMPLE, 'info' );
		expect( result ).toHaveLength( 1 );
		expect( result[ 0 ].id ).toBe( 'email.weak_from_address' );
	} );

	it( 'handles empty / null input', () => {
		expect( filterByMetric( null, 'all' ) ).toEqual( [] );
		expect( filterByMetric( [], 'critical' ) ).toEqual( [] );
	} );
} );

describe( 'filterFindings', () => {
	it( 'combines metric, area, status, and search', () => {
		const result = filterFindings( SAMPLE, {
			metric: 'warning',
			area: 'orders',
			status: 'open',
			search: 'aged',
		} );
		expect( result ).toHaveLength( 1 );
		expect( result[ 0 ].id ).toBe( 'orders.aged_completed_volume' );
	} );

	it( 'status ignored shows ignored rows only', () => {
		const result = filterFindings( SAMPLE, {
			metric: 'all',
			status: 'ignored',
		} );
		expect( result ).toHaveLength( 1 );
		expect( result[ 0 ].status ).toBe( 'ignored' );
	} );

	it( 'status all includes open and ignored', () => {
		const result = filterFindings( SAMPLE, {
			metric: 'all',
			status: 'all',
		} );
		expect( result ).toHaveLength( 5 );
	} );

	it( 'search matches id and ignore reason', () => {
		const byId = filterFindings( SAMPLE, {
			status: 'all',
			search: 'gateway_test',
		} );
		expect( byId ).toHaveLength( 1 );

		const byReason = filterFindings( SAMPLE, {
			status: 'ignored',
			search: 'quote-only',
		} );
		expect( byReason ).toHaveLength( 1 );
	} );
} );
