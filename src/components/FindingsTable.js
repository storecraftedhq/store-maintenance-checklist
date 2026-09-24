/**
 * FindingsTable — custom mockup-faithful table.
 *
 * DataViews (@wordpress/dataviews) was evaluated but cannot match the mockup’s
 * multi-line finding cell (title / why / evidence / further tools / meta) plus
 * stacked action column without fighting the visual contract. This reusable
 * custom table mirrors design-mockup/index.html instead.
 */

import { __, sprintf } from '@wordpress/i18n';
import { formatEvidenceText, normalizeEvidenceSample } from '../utils/evidence';

const AREA_LABELS = {
	catalog: __( 'Catalog', 'store-maintenance-checklist' ),
	payments: __( 'Payments', 'store-maintenance-checklist' ),
	shipping: __( 'Shipping', 'store-maintenance-checklist' ),
	email: __( 'Email', 'store-maintenance-checklist' ),
	orders: __( 'Orders', 'store-maintenance-checklist' ),
	environment: __( 'Environment', 'store-maintenance-checklist' ),
};

const CHANGE_LABELS = {
	new: __( 'New', 'store-maintenance-checklist' ),
	resolved: __( 'Resolved', 'store-maintenance-checklist' ),
	unchanged: __( 'Unchanged', 'store-maintenance-checklist' ),
	worsened: __( 'Worsened', 'store-maintenance-checklist' ),
};

/**
 * @param {Object} finding Finding.
 * @return {string} Label text.
 */
function severityLabel( finding ) {
	if ( finding.status === 'ignored' ) {
		return __( 'Ignored', 'store-maintenance-checklist' );
	}
	const map = {
		critical: __( 'Critical', 'store-maintenance-checklist' ),
		warning: __( 'Warning', 'store-maintenance-checklist' ),
		info: __( 'Info', 'store-maintenance-checklist' ),
	};
	return map[ finding.severity ] || finding.severity;
}

/**
 * @param {Object} finding Finding.
 * @return {string} CSS class list.
 */
function severityClass( finding ) {
	if ( finding.status === 'ignored' ) {
		return 'stmc-severity stmc-severity-ignored';
	}
	return `stmc-severity stmc-severity-${ finding.severity || 'info' }`;
}

/**
 * @param {Object}   props
 * @param {Array}    props.findings
 * @param {string}   props.search
 * @param {string}   props.area
 * @param {string}   props.status
 * @param {Function} props.onSearch
 * @param {Function} props.onArea
 * @param {Function} props.onStatus
 * @param {Function} props.onIgnore
 * @param {Function} props.onRestore
 * @param {boolean}  [props.dimmed]
 * @param {boolean}  [props.showFurtherTools]
 */
export default function FindingsTable( {
	findings,
	search,
	area,
	status,
	onSearch,
	onArea,
	onStatus,
	onIgnore,
	onRestore,
	dimmed = false,
	showFurtherTools = true,
} ) {
	return (
		<section
			className={
				dimmed ? 'stmc-findings-panel is-dimmed' : 'stmc-findings-panel'
			}
			aria-label={ __( 'Findings', 'store-maintenance-checklist' ) }
		>
			<div className="stmc-toolbar">
				<input
					className="stmc-search"
					type="search"
					value={ search }
					onChange={ ( e ) => onSearch( e.target.value ) }
					placeholder={ __(
						'Search findings…',
						'store-maintenance-checklist'
					) }
					aria-label={ __(
						'Search findings',
						'store-maintenance-checklist'
					) }
				/>
				<select
					className="stmc-select"
					value={ area }
					onChange={ ( e ) => onArea( e.target.value ) }
					aria-label={ __(
						'Filter by area',
						'store-maintenance-checklist'
					) }
				>
					<option value="all">
						{ __( 'All areas', 'store-maintenance-checklist' ) }
					</option>
					{ Object.entries( AREA_LABELS ).map( ( [ key, label ] ) => (
						<option key={ key } value={ key }>
							{ label }
						</option>
					) ) }
				</select>
				<select
					className="stmc-select"
					value={ status }
					onChange={ ( e ) => onStatus( e.target.value ) }
					aria-label={ __(
						'Filter by status',
						'store-maintenance-checklist'
					) }
				>
					<option value="open">
						{ __( 'Open findings', 'store-maintenance-checklist' ) }
					</option>
					<option value="ignored">
						{ __( 'Ignored', 'store-maintenance-checklist' ) }
					</option>
					<option value="all">
						{ __(
							'Open + ignored',
							'store-maintenance-checklist'
						) }
					</option>
				</select>
			</div>

			<div className="stmc-table-wrap">
				<table className="stmc-data-table">
					<thead>
						<tr>
							<th scope="col">
								{ __(
									'Severity',
									'store-maintenance-checklist'
								) }
							</th>
							<th scope="col">
								{ __(
									'Finding',
									'store-maintenance-checklist'
								) }
							</th>
							<th scope="col">
								{ __( 'Area', 'store-maintenance-checklist' ) }
							</th>
							<th scope="col">
								{ __(
									'Actions',
									'store-maintenance-checklist'
								) }
							</th>
						</tr>
					</thead>
					<tbody>
						{ findings.length === 0 ? (
							<tr>
								<td colSpan={ 4 }>
									{ __(
										'No findings match the current filters.',
										'store-maintenance-checklist'
									) }
								</td>
							</tr>
						) : (
							findings.map( ( finding ) => (
								<FindingRow
									key={ finding.id }
									finding={ finding }
									showFurtherTools={ showFurtherTools }
									onIgnore={ onIgnore }
									onRestore={ onRestore }
								/>
							) )
						) }
					</tbody>
				</table>
			</div>
		</section>
	);
}

/**
 * @param {Object}   props
 * @param {Object}   props.finding          Finding row.
 * @param {boolean}  props.showFurtherTools Whether to show further tools.
 * @param {Function} props.onIgnore         Ignore handler (receives finding).
 * @param {Function} props.onRestore        Restore handler.
 */
function FindingRow( { finding, showFurtherTools, onIgnore, onRestore } ) {
	const ignored = finding.status === 'ignored';
	const areaLabel = AREA_LABELS[ finding.area ] || finding.area || '';
	const change =
		finding.change && CHANGE_LABELS[ finding.change ]
			? CHANGE_LABELS[ finding.change ]
			: null;
	const samples = ( finding.evidence?.samples || [] )
		.map( normalizeEvidenceSample )
		.filter( Boolean );
	const { lead: evidenceLead, summary: evidenceSummary } = formatEvidenceText(
		finding.evidence
	);
	const tools = showFurtherTools ? finding.further_tools || [] : [];

	return (
		<tr
			className={ ignored ? 'is-ignored' : undefined }
			data-severity={ finding.severity }
			data-area={ finding.area }
			data-status={ finding.status }
		>
			<td>
				<span className={ severityClass( finding ) }>
					{ severityLabel( finding ) }
				</span>
			</td>
			<td>
				<div className="stmc-finding-title">{ finding.title }</div>
				{ finding.why ? (
					<p className="stmc-finding-why">{ finding.why }</p>
				) : null }
				{ evidenceLead ||
				evidenceSummary ||
				samples.length > 0 ||
				( ignored && finding.ignore_reason ) ? (
					<div className="stmc-finding-evidence">
						{ evidenceLead ? (
							<>
								<strong>{ evidenceLead }</strong>{ ' ' }
							</>
						) : null }
						{ evidenceSummary }
						{ samples.length > 0 ? (
							<>
								{ ' · ' }
								{ samples.map( ( sample, i ) => (
									<span
										key={ sample.url || sample.label || i }
									>
										{ i > 0 ? ', ' : null }
										{ sample.url ? (
											<a href={ sample.url }>
												{ sample.label }
											</a>
										) : (
											<code>{ sample.label }</code>
										) }
									</span>
								) ) }
							</>
						) : null }
						{ ignored && finding.ignore_reason ? (
							<>
								{ ' · ' }
								{ sprintf(
									/* translators: %s: ignore reason */
									__(
										'Reason: “%s”',
										'store-maintenance-checklist'
									),
									finding.ignore_reason
								) }
							</>
						) : null }
					</div>
				) : null }
				{ tools.length > 0 ? (
					<p className="stmc-finding-further">
						{ __(
							'Optional further tools:',
							'store-maintenance-checklist'
						) }{ ' ' }
						{ tools.map( ( tool, i ) => (
							<span key={ tool.url || tool.label || i }>
								{ i > 0 ? ', ' : null }
								<a
									href={ tool.url }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ tool.label }
								</a>
							</span>
						) ) }
					</p>
				) : null }
				<p className="stmc-finding-meta">
					ID: <code>{ finding.id }</code>
					{ change ? ` · ${ change }` : null }
					{ finding.score_excluded
						? ` · ${ __(
								'Excluded from score',
								'store-maintenance-checklist'
						  ) }`
						: null }
				</p>
			</td>
			<td>
				<span className="stmc-area-tag">{ areaLabel }</span>
			</td>
			<td>
				<div className="stmc-finding-actions">
					{ ignored ? (
						<button
							type="button"
							className="stmc-btn-link"
							onClick={ () => onRestore( finding.id ) }
						>
							{ __( 'Restore', 'store-maintenance-checklist' ) }
						</button>
					) : (
						<>
							{ finding.primary_action?.url ? (
								<a
									className="stmc-btn stmc-btn-primary stmc-btn-sm"
									href={ finding.primary_action.url }
									{ ...( finding.primary_action.external
										? {
												target: '_blank',
												rel: 'noopener noreferrer',
										  }
										: {} ) }
								>
									{ finding.primary_action.label ||
										__(
											'Open',
											'store-maintenance-checklist'
										) }
								</a>
							) : null }
							<button
								type="button"
								className="stmc-btn-link"
								onClick={ () => onIgnore( finding ) }
							>
								{ __(
									'Ignore…',
									'store-maintenance-checklist'
								) }
							</button>
						</>
					) }
				</div>
			</td>
		</tr>
	);
}
