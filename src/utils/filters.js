/**
 * Pure findings / metric strip filters (unit-tested).
 */

/**
 * Whether a finding counts as an open issue (not passed/ignored).
 *
 * @param {Object} finding Finding object.
 * @return {boolean} True when open with a display severity.
 */
export function isOpenFinding( finding ) {
	if ( ! finding || finding.status !== 'open' ) {
		return false;
	}
	return [ 'critical', 'warning', 'info' ].includes( finding.severity );
}

/**
 * Apply metric-strip filter to findings.
 *
 * Open / all → all open findings; critical|warning|info → that severity among open.
 * Score is not a filter (callers pass disabled).
 *
 * @param {Array}  findings Findings list (open + ignored; not passed).
 * @param {string} metric   'all' | 'open' | 'critical' | 'warning' | 'info'.
 * @return {Array} Filtered findings.
 */
export function filterByMetric( findings, metric ) {
	const list = Array.isArray( findings ) ? findings : [];
	const key = metric === 'open' || ! metric ? 'all' : metric;

	if ( key === 'all' ) {
		return list.filter( isOpenFinding );
	}

	return list.filter( ( f ) => isOpenFinding( f ) && f.severity === key );
}

/**
 * Filter findings by metric, area, status, and search text.
 *
 * @param {Array}  findings             Findings list.
 * @param {Object} opts                 Filter options.
 * @param {string} [opts.metric='all']  Metric strip key.
 * @param {string} [opts.area='all']    Area slug or 'all'.
 * @param {string} [opts.status='open'] 'open' | 'ignored' | 'all'.
 * @param {string} [opts.search='']     Free-text search.
 * @return {Array} Filtered findings.
 */
export function filterFindings( findings, opts = {} ) {
	const { metric = 'all', area = 'all', status = 'open', search = '' } = opts;

	const list = Array.isArray( findings ) ? findings : [];
	const q = String( search ).trim().toLowerCase();
	const metricKey = metric === 'open' ? 'all' : metric;

	return list.filter( ( finding ) => {
		const rowStatus = finding.status || '';
		const rowArea = finding.area || '';
		const rowSeverity = finding.severity || '';

		// Metric strip only applies among open findings when status allows open.
		if ( metricKey !== 'all' ) {
			if ( rowStatus !== 'open' || rowSeverity !== metricKey ) {
				return false;
			}
		}

		if ( area !== 'all' && rowArea !== area ) {
			return false;
		}

		if ( status === 'open' && rowStatus !== 'open' ) {
			return false;
		}
		if ( status === 'ignored' && rowStatus !== 'ignored' ) {
			return false;
		}
		if (
			status === 'all' &&
			rowStatus !== 'open' &&
			rowStatus !== 'ignored'
		) {
			return false;
		}

		if ( q ) {
			const hay = [
				finding.id,
				finding.title,
				finding.why,
				finding.evidence?.summary,
				finding.ignore_reason,
				rowArea,
				rowSeverity,
				rowStatus,
			]
				.filter( Boolean )
				.join( ' ' )
				.toLowerCase();
			if ( ! hay.includes( q ) ) {
				return false;
			}
		}

		return true;
	} );
}

/**
 * Filter history items by environment and search.
 *
 * @param {Array}  items                    History items.
 * @param {Object} opts                     Options.
 * @param {string} [opts.environment='all'] Environment filter.
 * @param {string} [opts.search='']         Search string.
 * @return {Array} Filtered items.
 */
export function filterHistory( items, opts = {} ) {
	const { environment = 'all', search = '' } = opts;
	const list = Array.isArray( items ) ? items : [];
	const q = String( search ).trim().toLowerCase();

	return list.filter( ( item ) => {
		const env = item.environment || '';
		if ( environment !== 'all' && env !== environment ) {
			return false;
		}
		if ( q ) {
			const hay = [
				item.timestamp,
				env,
				item.terminal_state,
				item.partial_catalog ? 'partial' : '',
				item.provisional ? 'provisional' : '',
				String( item.score ?? '' ),
			]
				.join( ' ' )
				.toLowerCase();
			if ( ! hay.includes( q ) ) {
				return false;
			}
		}
		return true;
	} );
}
