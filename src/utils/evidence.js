/**
 * Evidence display helpers for findings table.
 */

/**
 * Normalize a sample into { label, url } or null when empty.
 *
 * @param {*} sample Raw sample (id, string, or object).
 * @return {{label: string, url: string}|null} Normalized sample or null.
 */
export function normalizeEvidenceSample( sample ) {
	if ( sample === null || sample === undefined || sample === '' ) {
		return null;
	}

	if ( typeof sample === 'number' || typeof sample === 'string' ) {
		const value = String( sample ).trim();
		if ( ! value ) {
			return null;
		}
		return {
			label: /^\d+$/.test( value ) ? `#${ value }` : value,
			url: '',
		};
	}

	if ( typeof sample !== 'object' ) {
		return null;
	}

	if ( sample.label || sample.url ) {
		const label = String( sample.label || sample.url ).trim();
		if ( ! label ) {
			return null;
		}
		return { label, url: sample.url ? String( sample.url ) : '' };
	}

	if ( ( sample.id !== null && sample.id !== undefined ) || sample.name ) {
		const id = sample.id;
		const name = sample.name ? String( sample.name ).trim() : '';
		let label = name;
		if (
			! label &&
			id !== null &&
			id !== undefined &&
			String( id ).trim() !== ''
		) {
			const idStr = String( id ).trim();
			label = /^\d+$/.test( idStr ) ? `#${ idStr }` : idStr;
		}
		if ( ! label ) {
			return null;
		}
		return { label, url: sample.url ? String( sample.url ) : '' };
	}

	return null;
}

/**
 * Split evidence into a bold count lead and summary without duplicating the number.
 *
 * @param {Object} [evidence] Finding evidence.
 * @return {{lead: string|null, summary: string}} Lead count and cleaned summary.
 */
export function formatEvidenceText( evidence ) {
	const count = evidence?.count;
	let summary = evidence?.summary ? String( evidence.summary ) : '';

	if ( typeof count !== 'number' ) {
		return { lead: null, summary };
	}

	const lead = Number( count ).toLocaleString();
	const raw = String( count );

	if ( summary === raw || summary.startsWith( `${ raw } ` ) ) {
		summary = summary.slice( raw.length ).trimStart();
	} else if ( summary === lead || summary.startsWith( `${ lead } ` ) ) {
		summary = summary.slice( lead.length ).trimStart();
	}

	return { lead, summary };
}
