/**
 * Hash router: #/, #/settings, #/history, #/about (default #/).
 */

import { useEffect, useState, useCallback } from '@wordpress/element';

const ROUTES = {
	'/': 'checklist',
	'/settings': 'settings',
	'/history': 'history',
	'/about': 'about',
};

/**
 * Parse location.hash into a route key.
 *
 * @param {string} hash location.hash
 * @return {{ path: string, page: string, query: URLSearchParams }} Route info.
 */
export function parseHash( hash ) {
	const raw = String( hash || '' ).replace( /^#/, '' ) || '/';
	const [ pathPart, queryPart = '' ] = raw.split( '?' );
	let path = pathPart || '/';
	if ( ! path.startsWith( '/' ) ) {
		path = `/${ path }`;
	}
	// Normalize trailing slash except root.
	if ( path.length > 1 && path.endsWith( '/' ) ) {
		path = path.slice( 0, -1 );
	}
	const page = ROUTES[ path ] || 'checklist';
	const query = new URLSearchParams( queryPart );
	return { path: ROUTES[ path ] ? path : '/', page, query };
}

/**
 * Navigate by setting location.hash.
 *
 * @param {string} path    Route path e.g. '/settings'.
 * @param {Object} [query] Optional query map.
 */
export function navigate( path, query = {} ) {
	let next = path.startsWith( '/' ) ? path : `/${ path }`;
	const params = new URLSearchParams();
	Object.entries( query ).forEach( ( [ key, value ] ) => {
		if ( value !== undefined && value !== null && value !== '' ) {
			params.set( key, String( value ) );
		}
	} );
	const qs = params.toString();
	if ( qs ) {
		next += `?${ qs }`;
	}
	window.location.hash = next;
}

/**
 * React hook for hash routing.
 *
 * @return {{ path: string, page: string, query: URLSearchParams, navigate: Function }} Route state.
 */
export function useHashRoute() {
	const [ route, setRoute ] = useState( () =>
		parseHash( typeof window !== 'undefined' ? window.location.hash : '#/' )
	);

	useEffect( () => {
		const onHash = () => setRoute( parseHash( window.location.hash ) );
		if ( ! window.location.hash || window.location.hash === '#' ) {
			window.location.hash = '/';
		}
		window.addEventListener( 'hashchange', onHash );
		return () => window.removeEventListener( 'hashchange', onHash );
	}, [] );

	const go = useCallback( ( path, query ) => navigate( path, query ), [] );

	return { ...route, navigate: go };
}
