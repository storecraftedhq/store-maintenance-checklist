/**
 * apiFetch wrappers for stmc/v1.
 */

import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Localized admin bootstrap data.
 *
 * @return {Object} stmcAdmin object (empty object if missing).
 */
export function getAdminConfig() {
	return typeof window !== 'undefined' && window.stmcAdmin
		? window.stmcAdmin
		: {};
}

/**
 * Ensure apiFetch nonce from localized data (once).
 * Paths use `/stmc/v1/…` against the default wp-json root (avoids rest_no_route).
 */
let middlewareReady = false;

export function ensureApiMiddleware() {
	if ( middlewareReady ) {
		return;
	}
	const { nonce } = getAdminConfig();
	if ( nonce ) {
		apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	}
	middlewareReady = true;
}

/**
 * @param {string} path      Path after namespace (e.g. 'scan').
 * @param {Object} [options] apiFetch options.
 * @return {Promise<*>} Response data.
 */
function stmcFetch( path, options = {} ) {
	ensureApiMiddleware();
	const clean = String( path ).replace( /^\//, '' );
	return apiFetch( {
		path: `/stmc/v1/${ clean }`,
		...options,
	} );
}

export function fetchScan() {
	return stmcFetch( 'scan' );
}

export function fetchScanStatus() {
	return stmcFetch( 'scan/status' );
}

export function startScan() {
	return stmcFetch( 'scan', { method: 'POST', data: {} } );
}

export function cancelScan() {
	return stmcFetch( 'scan/cancel', { method: 'POST', data: {} } );
}

export function retryScan() {
	return stmcFetch( 'scan/retry', { method: 'POST', data: {} } );
}

export function ignoreCheck( checkId, reason = '' ) {
	return stmcFetch( `checks/${ encodeURIComponent( checkId ) }/ignore`, {
		method: 'POST',
		data: { reason: reason || '' },
	} );
}

export function restoreCheck( checkId ) {
	return stmcFetch( `checks/${ encodeURIComponent( checkId ) }/ignore`, {
		method: 'DELETE',
	} );
}

export function fetchSettings() {
	return stmcFetch( 'settings' );
}

export function saveSettings( data ) {
	return stmcFetch( 'settings', { method: 'PUT', data } );
}

export function fetchHistory( params = {} ) {
	const query = new URLSearchParams();
	if ( params.environment && params.environment !== 'all' ) {
		query.set( 'environment', params.environment );
	}
	if ( params.search ) {
		query.set( 'search', params.search );
	}
	const qs = query.toString();
	return stmcFetch( qs ? `history?${ qs }` : 'history' );
}

export function clearHistory() {
	return stmcFetch( 'history', { method: 'DELETE' } );
}

/**
 * Download CSV export as a file.
 *
 * @return {Promise<void>}
 */
export async function downloadCsv() {
	ensureApiMiddleware();
	let response;
	try {
		response = await apiFetch( {
			path: '/stmc/v1/export/csv',
			parse: false,
		} );
	} catch ( err ) {
		// apiFetch with parse:false rejects with the Response on HTTP errors.
		if ( err && typeof err.blob === 'function' ) {
			response = err;
		} else if ( err instanceof Error ) {
			throw err;
		} else {
			throw new Error(
				err && err.message ? String( err.message ) : String( err )
			);
		}
	}
	if ( response && response.ok === false ) {
		let message = sprintf(
			/* translators: %s: HTTP status code */
			__( 'CSV export failed (HTTP %s).', 'store-maintenance-checklist-for-woocommerce' ),
			String( response.status || '?' )
		);
		if ( typeof response.json === 'function' ) {
			try {
				const data = await response.json();
				if ( data && data.message ) {
					message = String( data.message );
				}
			} catch ( e ) {
				// Keep status fallback.
			}
		}
		throw new Error( message );
	}
	if ( ! response || typeof response.blob !== 'function' ) {
		throw new Error(
			typeof response === 'string'
				? response
				: __(
						'Unexpected CSV export response.',
						'store-maintenance-checklist-for-woocommerce'
				  )
		);
	}
	const blob = await response.blob();
	const url = URL.createObjectURL( blob );
	const a = document.createElement( 'a' );
	a.href = url;
	a.download = 'store-maintenance-checklist-findings.csv';
	document.body.appendChild( a );
	a.click();
	a.remove();
	URL.revokeObjectURL( url );
}
