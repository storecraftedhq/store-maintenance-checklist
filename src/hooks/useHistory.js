/**
 * History list / clear hook.
 */

import { useCallback, useEffect, useState } from '@wordpress/element';
import { clearHistory as apiClear, fetchHistory } from '../api/client';

/**
 * @return {Object} History hook API.
 */
export function useHistory() {
	const [ items, setItems ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( false );

	const load = useCallback( async ( params = {} ) => {
		setLoading( true );
		setError( null );
		try {
			const data = await fetchHistory( params );
			setItems( Array.isArray( data?.items ) ? data.items : [] );
		} catch ( err ) {
			setError( err?.message || String( err ) );
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const clear = useCallback( async () => {
		setBusy( true );
		setError( null );
		try {
			await apiClear();
			setItems( [] );
		} catch ( err ) {
			setError( err?.message || String( err ) );
			throw err;
		} finally {
			setBusy( false );
		}
	}, [] );

	return {
		items,
		loading,
		error,
		busy,
		reload: load,
		clear,
	};
}
