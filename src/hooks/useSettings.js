/**
 * Settings load / save hook.
 */

import { useCallback, useEffect, useState } from '@wordpress/element';
import { fetchSettings, saveSettings as apiSave } from '../api/client';

const DEFAULTS = {
	force_production_severity: false,
	show_further_tools: true,
	max_products: 1000,
	max_variations: 2000,
	ignored_count: 0,
	constants: { force_production_defined: false },
};

/**
 * @return {Object} Settings hook API.
 */
export function useSettings() {
	const [ settings, setSettings ] = useState( DEFAULTS );
	const [ draft, setDraft ] = useState( {
		force_production_severity: false,
		show_further_tools: true,
		max_products: 1000,
		max_variations: 2000,
	} );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ saved, setSaved ] = useState( false );

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			const data = await fetchSettings();
			setSettings( data );
			setDraft( {
				force_production_severity: Boolean(
					data.force_production_severity
				),
				show_further_tools: Boolean( data.show_further_tools ),
				max_products: Number( data.max_products ) || 1000,
				max_variations: Number( data.max_variations ) || 2000,
			} );
		} catch ( err ) {
			setError( err?.message || String( err ) );
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		load();
	}, [ load ] );

	const updateDraft = useCallback( ( patch ) => {
		setDraft( ( current ) => ( { ...current, ...patch } ) );
		setSaved( false );
	}, [] );

	const resetDefaults = useCallback( () => {
		setDraft( {
			force_production_severity: false,
			show_further_tools: true,
			max_products: 1000,
			max_variations: 2000,
		} );
		setSaved( false );
	}, [] );

	const save = useCallback( async () => {
		setSaving( true );
		setError( null );
		setSaved( false );
		try {
			const data = await apiSave( draft );
			setSettings( data );
			setDraft( {
				force_production_severity: Boolean(
					data.force_production_severity
				),
				show_further_tools: Boolean( data.show_further_tools ),
				max_products: Number( data.max_products ) || 1000,
				max_variations: Number( data.max_variations ) || 2000,
			} );
			setSaved( true );
			return data;
		} catch ( err ) {
			setError( err?.message || String( err ) );
			throw err;
		} finally {
			setSaving( false );
		}
	}, [ draft ] );

	return {
		settings,
		draft,
		loading,
		saving,
		error,
		saved,
		updateDraft,
		resetDefaults,
		save,
		reload: load,
	};
}
