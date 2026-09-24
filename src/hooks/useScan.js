/**
 * Load scan, start/cancel/retry, and status polling.
 */

import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import {
	cancelScan as apiCancel,
	fetchScan,
	fetchScanStatus,
	retryScan as apiRetry,
	startScan as apiStart,
	ignoreCheck as apiIgnore,
	restoreCheck as apiRestore,
} from '../api/client';
import { getPollIntervalMs } from '../utils/progress';

/**
 * @return {Object} Scan hook API.
 */
export function useScan() {
	const [ scan, setScan ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( false );
	const pollTimer = useRef( null );
	const scanRef = useRef( null );

	useEffect( () => {
		scanRef.current = scan;
	}, [ scan ] );

	const clearPoll = useCallback( () => {
		if ( pollTimer.current ) {
			clearTimeout( pollTimer.current );
			pollTimer.current = null;
		}
	}, [] );

	const applyScan = useCallback( ( data ) => {
		setScan( data );
		setError( null );
	}, [] );

	const refresh = useCallback( async () => {
		const data = await fetchScan();
		applyScan( data );
		return data;
	}, [ applyScan ] );

	const load = useCallback( async () => {
		setLoading( true );
		try {
			await refresh();
		} catch ( err ) {
			setError( err?.message || String( err ) );
		} finally {
			setLoading( false );
		}
	}, [ refresh ] );

	useEffect( () => {
		load();
	}, [ load ] );

	// Polling while running.
	useEffect( () => {
		clearPoll();
		const status = scan?.status;
		const interval = getPollIntervalMs( status );
		if ( ! interval ) {
			return clearPoll;
		}

		const tick = async () => {
			try {
				const nextStatus = await fetchScanStatus();
				const prev = scanRef.current;
				const wasRunning = prev?.status?.state === 'running';
				const stillRunning = nextStatus?.state === 'running';

				if ( wasRunning && ! stillRunning ) {
					await refresh();
					return;
				}

				setScan( ( current ) => ( {
					...( current || {} ),
					status: nextStatus,
				} ) );

				const nextInterval = getPollIntervalMs( nextStatus );
				if ( nextInterval ) {
					pollTimer.current = setTimeout( tick, nextInterval );
				}
			} catch ( err ) {
				setError( err?.message || String( err ) );
				pollTimer.current = setTimeout( tick, interval );
			}
		};

		pollTimer.current = setTimeout( tick, interval );
		return clearPoll;
		// Poll only when running state / stalled flag changes (not every status field update).
		// eslint-disable-next-line react-hooks/exhaustive-deps -- poll keys: state + stalled
	}, [ scan?.status?.state, scan?.status?.stalled, clearPoll, refresh ] );

	const start = useCallback( async () => {
		setBusy( true );
		setError( null );
		try {
			const res = await apiStart();
			setScan( ( current ) => ( {
				...( current || {} ),
				status: res.status || res,
			} ) );
			// Full refresh keeps prior findings visible while running.
			await refresh();
		} catch ( err ) {
			setError( err?.message || String( err ) );
			throw err;
		} finally {
			setBusy( false );
		}
	}, [ refresh ] );

	const cancel = useCallback( async () => {
		setBusy( true );
		setError( null );
		try {
			await apiCancel();
			await refresh();
		} catch ( err ) {
			setError( err?.message || String( err ) );
			throw err;
		} finally {
			setBusy( false );
		}
	}, [ refresh ] );

	const retry = useCallback( async () => {
		setBusy( true );
		setError( null );
		try {
			const res = await apiRetry();
			setScan( ( current ) => ( {
				...( current || {} ),
				status: res.status || res,
			} ) );
			await refresh();
		} catch ( err ) {
			setError( err?.message || String( err ) );
			throw err;
		} finally {
			setBusy( false );
		}
	}, [ refresh ] );

	const ignore = useCallback(
		async ( checkId, reason ) => {
			await apiIgnore( checkId, reason );
			await refresh();
		},
		[ refresh ]
	);

	const restore = useCallback(
		async ( checkId ) => {
			await apiRestore( checkId );
			await refresh();
		},
		[ refresh ]
	);

	return {
		scan,
		loading,
		error,
		busy,
		refresh,
		start,
		cancel,
		retry,
		ignore,
		restore,
	};
}
