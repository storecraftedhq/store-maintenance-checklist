/**
 * @jest-environment jsdom
 */

import {
	POLL_INTERVAL_RUNNING_MS,
	POLL_INTERVAL_STALLED_MS,
	getPollIntervalMs,
	getProgressUiState,
	shouldShowEmptyState,
	shouldShowProgressPanel,
	shouldShowStalledBanner,
} from './progress';

describe( 'getPollIntervalMs', () => {
	it( 'returns 2500ms while running and not stalled', () => {
		expect(
			getPollIntervalMs( { state: 'running', stalled: false } )
		).toBe( POLL_INTERVAL_RUNNING_MS );
	} );

	it( 'returns 10000ms when stalled', () => {
		expect( getPollIntervalMs( { state: 'running', stalled: true } ) ).toBe(
			POLL_INTERVAL_STALLED_MS
		);
	} );

	it( 'returns null when idle / completed / missing', () => {
		expect( getPollIntervalMs( { state: 'idle' } ) ).toBeNull();
		expect( getPollIntervalMs( { state: 'completed' } ) ).toBeNull();
		expect( getPollIntervalMs( null ) ).toBeNull();
	} );
} );

describe( 'progress panel / stalled banner', () => {
	it( 'shows progress panel only while running', () => {
		expect(
			shouldShowProgressPanel( { state: 'running', stalled: false } )
		).toBe( true );
		expect(
			shouldShowProgressPanel( { state: 'running', stalled: true } )
		).toBe( true );
		expect( shouldShowProgressPanel( { state: 'idle' } ) ).toBe( false );
	} );

	it( 'shows stalled banner only when running and stalled', () => {
		expect(
			shouldShowStalledBanner( { state: 'running', stalled: true } )
		).toBe( true );
		expect(
			shouldShowStalledBanner( { state: 'running', stalled: false } )
		).toBe( false );
		expect(
			shouldShowStalledBanner( { state: 'idle', stalled: true } )
		).toBe( false );
	} );
} );

describe( 'shouldShowEmptyState', () => {
	it( 'is true when never scanned', () => {
		expect(
			shouldShowEmptyState( {
				status: { state: 'idle' },
				findings: [],
				passed: [],
				last_completed_at: null,
			} )
		).toBe( true );
		expect( shouldShowEmptyState( null ) ).toBe( true );
	} );

	it( 'is false while running even without completed results', () => {
		expect(
			shouldShowEmptyState( {
				status: { state: 'running' },
				findings: [],
				passed: [],
				last_completed_at: null,
			} )
		).toBe( false );
	} );

	it( 'is false after a completed scan', () => {
		expect(
			shouldShowEmptyState( {
				status: { state: 'idle' },
				findings: [ { id: 'x', status: 'open' } ],
				passed: [],
				last_completed_at: '2026-09-23T14:02:00Z',
			} )
		).toBe( false );
	} );
} );

describe( 'getProgressUiState', () => {
	it( 'maps idle / running / stalled / empty', () => {
		expect( getProgressUiState( { state: 'idle' }, false ) ).toBe(
			'empty'
		);
		expect( getProgressUiState( { state: 'idle' }, true ) ).toBe( 'idle' );
		expect(
			getProgressUiState( { state: 'running', stalled: false }, true )
		).toBe( 'running' );
		expect(
			getProgressUiState( { state: 'running', stalled: true }, true )
		).toBe( 'stalled' );
	} );
} );
