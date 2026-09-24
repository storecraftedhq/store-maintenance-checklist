/**
 * History page.
 */

import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Canvas from '../components/Canvas';
import Modal from '../components/Modal';
import { useHistory } from '../hooks/useHistory';
import { filterHistory } from '../utils/filters';

/**
 * @param {string} iso ISO timestamp.
 * @return {string} Localized datetime.
 */
function formatWhen( iso ) {
	if ( ! iso ) {
		return '—';
	}
	try {
		const d = new Date( iso );
		return d.toLocaleString( undefined, {
			dateStyle: 'medium',
			timeStyle: 'short',
		} );
	} catch ( e ) {
		return iso;
	}
}

/**
 * @param {Object}   props
 * @param {Function} props.onNavigate
 */
export default function HistoryPage( { onNavigate } ) {
	const { items, loading, error, busy, clear } = useHistory();
	const [ search, setSearch ] = useState( '' );
	const [ env, setEnv ] = useState( 'all' );
	const [ clearOpen, setClearOpen ] = useState( false );

	const filtered = useMemo(
		() => filterHistory( items, { environment: env, search } ),
		[ items, env, search ]
	);

	const latest = items[ 0 ] || null;
	const changes = latest?.changes || {};

	const onClearConfirm = async () => {
		try {
			await clear();
			setClearOpen( false );
		} catch ( err ) {
			/* eslint-disable-next-line no-alert -- surface REST errors without a toast system */
			window.alert( err?.message || String( err ) );
		}
	};

	const actions = (
		<>
			<button
				type="button"
				className="stmc-btn stmc-btn-sm"
				onClick={ () => setClearOpen( true ) }
				disabled={ busy || items.length === 0 }
			>
				{ __( 'Clear history', 'store-maintenance-checklist' ) }
			</button>
			<button
				type="button"
				className="stmc-btn stmc-btn-primary stmc-btn-sm"
				onClick={ () => onNavigate( '/' ) }
			>
				{ __( 'Open latest', 'store-maintenance-checklist' ) }
			</button>
		</>
	);

	return (
		<Canvas
			title={ __( 'History', 'store-maintenance-checklist' ) }
			meta={ __(
				'Last 5–10 scan summaries · New / Resolved / Unchanged vs previous run',
				'store-maintenance-checklist'
			) }
			actions={ actions }
		>
			<h2 className="stmc-section-heading">
				{ __( 'Recent scans', 'store-maintenance-checklist' ) }
			</h2>
			<p className="stmc-section-desc">
				{ __(
					'Local summaries only — score, severity counts, environment mode, and change markers. Full finding payloads are not stored forever.',
					'store-maintenance-checklist'
				) }
			</p>

			{ error ? (
				<div className="stmc-danger-banner" role="alert">
					<div className="stmc-banner-body">
						<strong>
							{ __( 'Error', 'store-maintenance-checklist' ) }
						</strong>
						<p>{ error }</p>
					</div>
				</div>
			) : null }

			<div className="stmc-history-metrics">
				<div
					className="stmc-metric-card"
					style={ { cursor: 'default' } }
				>
					<div
						className="stmc-metric-value"
						style={ { color: 'var(--stmc-accent)' } }
					>
						{ latest?.score ?? '—' }
					</div>
					<div className="stmc-metric-label">
						{ __( 'Latest score', 'store-maintenance-checklist' ) }
					</div>
					<div className="stmc-metric-sub">
						{ latest?.provisional
							? __(
									'Provisional',
									'store-maintenance-checklist'
							  ) + ' · '
							: '' }
						{ latest
							? formatWhen( latest.timestamp )
							: __(
									'No scans yet',
									'store-maintenance-checklist'
							  ) }
					</div>
				</div>
				<div
					className="stmc-metric-card"
					style={ { cursor: 'default' } }
				>
					<div className="stmc-metric-value">
						{ typeof changes.new === 'number'
							? `+${ changes.new }`
							: '—' }
					</div>
					<div className="stmc-metric-label">
						{ __( 'New findings', 'store-maintenance-checklist' ) }
					</div>
					<div className="stmc-metric-sub">
						{ __(
							'Since previous scan',
							'store-maintenance-checklist'
						) }
					</div>
				</div>
				<div
					className="stmc-metric-card"
					style={ { cursor: 'default' } }
				>
					<div
						className="stmc-metric-value"
						style={ { color: 'var(--stmc-success)' } }
					>
						{ changes.resolved ?? '—' }
					</div>
					<div className="stmc-metric-label">
						{ __( 'Resolved', 'store-maintenance-checklist' ) }
					</div>
					<div className="stmc-metric-sub">
						{ __(
							'No longer failing',
							'store-maintenance-checklist'
						) }
					</div>
				</div>
				<div
					className="stmc-metric-card"
					style={ { cursor: 'default' } }
				>
					<div className="stmc-metric-value">{ items.length }</div>
					<div className="stmc-metric-label">
						{ __( 'Stored runs', 'store-maintenance-checklist' ) }
					</div>
					<div className="stmc-metric-sub">
						{ __(
							'Cap 10 · oldest dropped next',
							'store-maintenance-checklist'
						) }
					</div>
				</div>
			</div>

			<section
				className="stmc-findings-panel"
				aria-label={ __(
					'Scan history',
					'store-maintenance-checklist'
				) }
			>
				<div className="stmc-toolbar">
					<input
						className="stmc-search"
						type="search"
						value={ search }
						onChange={ ( e ) => setSearch( e.target.value ) }
						placeholder={ __(
							'Search by date or environment…',
							'store-maintenance-checklist'
						) }
						aria-label={ __(
							'Search history',
							'store-maintenance-checklist'
						) }
					/>
					<select
						className="stmc-select"
						value={ env }
						onChange={ ( e ) => setEnv( e.target.value ) }
						aria-label={ __(
							'Filter by environment',
							'store-maintenance-checklist'
						) }
					>
						<option value="all">
							{ __(
								'All environments',
								'store-maintenance-checklist'
							) }
						</option>
						<option value="local">
							{ __( 'Local', 'store-maintenance-checklist' ) }
						</option>
						<option value="development">
							{ __(
								'Development',
								'store-maintenance-checklist'
							) }
						</option>
						<option value="staging">
							{ __( 'Staging', 'store-maintenance-checklist' ) }
						</option>
						<option value="production">
							{ __(
								'Production',
								'store-maintenance-checklist'
							) }
						</option>
					</select>
					<div className="stmc-toolbar-spacer" />
					<span className="stmc-history-compare-hint">
						{ __(
							'Compared to previous run',
							'store-maintenance-checklist'
						) }
					</span>
				</div>
				<div className="stmc-table-wrap">
					{ loading ? (
						<p
							className="stmc-section-desc"
							style={ { padding: 16 } }
						>
							{ __( 'Loading…', 'store-maintenance-checklist' ) }
						</p>
					) : (
						<table className="stmc-data-table">
							<thead>
								<tr>
									<th scope="col">
										{ __(
											'When',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col">
										{ __(
											'Score',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col">
										{ __(
											'Open',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col">
										{ __(
											'Critical',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col">
										{ __(
											'Warning',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col">
										{ __(
											'Info',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col">
										{ __(
											'Environment',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col">
										{ __(
											'Changes',
											'store-maintenance-checklist'
										) }
									</th>
									<th scope="col" />
								</tr>
							</thead>
							<tbody>
								{ filtered.length === 0 ? (
									<tr>
										<td colSpan={ 9 }>
											{ __(
												'No history entries yet.',
												'store-maintenance-checklist'
											) }
										</td>
									</tr>
								) : (
									filtered.map( ( item, index ) => (
										<HistoryRow
											key={ item.id || item.timestamp }
											item={ item }
											isLatest={
												index === 0 &&
												! search &&
												env === 'all'
											}
											onNavigate={ onNavigate }
										/>
									) )
								) }
							</tbody>
						</table>
					) }
				</div>
			</section>

			<p className="stmc-footer-note">
				{ __(
					'History is stored in plugin options on this site only. Clearing history does not change live findings or ignores.',
					'store-maintenance-checklist'
				) }
			</p>

			<Modal
				isOpen={ clearOpen }
				title={ __( 'Clear history', 'store-maintenance-checklist' ) }
				onClose={ () => {
					if ( ! busy ) {
						setClearOpen( false );
					}
				} }
				footer={
					<>
						<button
							type="button"
							className="stmc-btn stmc-btn-sm"
							onClick={ () => setClearOpen( false ) }
							disabled={ busy }
						>
							{ __( 'Cancel', 'store-maintenance-checklist' ) }
						</button>
						<button
							type="button"
							className="stmc-btn stmc-btn-primary stmc-btn-sm"
							onClick={ onClearConfirm }
							disabled={ busy }
						>
							{ busy
								? __(
										'Clearing…',
										'store-maintenance-checklist'
								  )
								: __(
										'Clear history',
										'store-maintenance-checklist'
								  ) }
						</button>
					</>
				}
			>
				<p className="stmc-modal-lead">
					{ __(
						'Clear stored scan summaries on this site? Live findings and ignores are not changed.',
						'store-maintenance-checklist'
					) }
				</p>
			</Modal>
		</Canvas>
	);
}

/**
 * @param {Object}   props
 * @param {Object}   props.item       History item.
 * @param {boolean}  props.isLatest   Whether this is the latest row.
 * @param {Function} props.onNavigate Navigate helper.
 */
function HistoryRow( { item, isLatest, onNavigate } ) {
	const counts = item.counts || {};
	const open =
		( counts.critical || 0 ) +
		( counts.warning || 0 ) +
		( counts.info || 0 );
	const changes = item.changes || {};
	const pills = [];
	if ( changes.new ) {
		pills.push(
			<span key="new" className="stmc-change-pill stmc-change-pill--new">
				{ sprintf(
					/* translators: %d: new findings */
					__( '%d new', 'store-maintenance-checklist' ),
					changes.new
				) }
			</span>
		);
	}
	if ( changes.resolved ) {
		pills.push(
			<span
				key="resolved"
				className="stmc-change-pill stmc-change-pill--resolved"
			>
				{ sprintf(
					/* translators: %d: resolved findings */
					__( '%d resolved', 'store-maintenance-checklist' ),
					changes.resolved
				) }
			</span>
		);
	}
	if ( changes.worsened ) {
		pills.push(
			<span
				key="worsened"
				className="stmc-change-pill stmc-change-pill--worsened"
			>
				{ sprintf(
					/* translators: %d: worsened findings */
					__( '%d worsened', 'store-maintenance-checklist' ),
					changes.worsened
				) }
			</span>
		);
	}
	if ( pills.length === 0 ) {
		pills.push(
			<span
				key="unchanged"
				className="stmc-change-pill stmc-change-pill--unchanged"
			>
				{ __( 'Unchanged', 'store-maintenance-checklist' ) }
			</span>
		);
	}

	return (
		<tr data-env={ item.environment }>
			<td>
				<strong>{ formatWhen( item.timestamp ) }</strong>
				<div className="stmc-finding-meta">
					{ item.partial_catalog
						? __( 'Partial catalog', 'store-maintenance-checklist' )
						: __(
								'Full bounded pass',
								'store-maintenance-checklist'
						  ) }
					{ isLatest
						? ` · ${ __(
								'current',
								'store-maintenance-checklist'
						  ) }`
						: null }
				</div>
			</td>
			<td className="stmc-score-cell">
				{ item.score }
				{ item.provisional ? (
					<>
						{ ' ' }
						<span className="stmc-pill">
							{ __(
								'Provisional',
								'store-maintenance-checklist'
							) }
						</span>
					</>
				) : null }
			</td>
			<td>{ open }</td>
			<td>{ counts.critical ?? 0 }</td>
			<td>{ counts.warning ?? 0 }</td>
			<td>{ counts.info ?? 0 }</td>
			<td>
				<span className="stmc-area-tag">
					{ item.environment || '—' }
				</span>
			</td>
			<td>{ pills }</td>
			<td>
				{ isLatest ? (
					<button
						type="button"
						className="stmc-btn-link"
						onClick={ () => onNavigate( '/' ) }
					>
						{ __( 'View', 'store-maintenance-checklist' ) }
					</button>
				) : (
					<span className="stmc-finding-meta">
						{ __( 'Summary', 'store-maintenance-checklist' ) }
					</span>
				) }
			</td>
		</tr>
	);
}
