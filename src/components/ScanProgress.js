/**
 * Hybrid AS scan progress panel.
 */

import { __, sprintf } from '@wordpress/i18n';

/**
 * @param {Object}   props
 * @param {Object}   props.status
 * @param {boolean}  props.stalled
 * @param {Function} props.onCancel
 * @param {boolean}  [props.busy]
 */
export default function ScanProgress( {
	status,
	stalled,
	onCancel,
	busy = false,
} ) {
	const pct = Math.max(
		0,
		Math.min( 100, Number( status?.progress_percent ) || 0 )
	);
	const phaseLabel =
		status?.phase_label ||
		__( 'Scanning store…', 'store-maintenance-checklist' );
	const batchCurrent = status?.batch_current;
	const batchTotal = status?.batch_total;
	const productsScanned = status?.products_scanned;
	const productsBound = status?.products_bound;

	let detail = __(
		'Previous findings stay visible until complete',
		'store-maintenance-checklist'
	);
	if (
		typeof productsScanned === 'number' &&
		typeof productsBound === 'number' &&
		productsBound > 0
	) {
		detail = sprintf(
			/* translators: 1: products scanned, 2: product bound */
			__(
				'~%1$s of %2$s products in this bound · previous findings stay visible until complete',
				'store-maintenance-checklist'
			),
			Number( productsScanned ).toLocaleString(),
			Number( productsBound ).toLocaleString()
		);
	}

	let phase = phaseLabel;
	if ( typeof batchCurrent === 'number' && typeof batchTotal === 'number' ) {
		phase = sprintf(
			/* translators: 1: current batch, 2: total batches */
			__(
				'Catalog batch %1$s of %2$s · Action Scheduler',
				'store-maintenance-checklist'
			),
			String( batchCurrent ),
			String( batchTotal )
		);
	}
	if ( stalled ) {
		phase = __(
			'Last batch completed 8+ minutes ago · Action Scheduler idle',
			'store-maintenance-checklist'
		);
		detail = __(
			'WP-Cron may be delayed — findings below are from the last completed batches only',
			'store-maintenance-checklist'
		);
	}

	const title = stalled
		? __(
				'Scan waiting on background jobs…',
				'store-maintenance-checklist'
		  )
		: __( 'Scanning store…', 'store-maintenance-checklist' );
	const pill = stalled
		? __( 'Stalled', 'store-maintenance-checklist' )
		: __( 'In progress', 'store-maintenance-checklist' );

	return (
		<section
			className={
				stalled ? 'stmc-scan-progress is-stalled' : 'stmc-scan-progress'
			}
			aria-label={ __(
				'Scan in progress',
				'store-maintenance-checklist'
			) }
		>
			<div className="stmc-scan-progress-header">
				<div className="stmc-scan-progress-title-row">
					<span className="stmc-scan-spinner" aria-hidden="true" />
					<strong>{ title }</strong>
					<span
						className={
							stalled ? 'stmc-pill stmc-pill-danger' : 'stmc-pill'
						}
					>
						{ pill }
					</span>
				</div>
				<p className="stmc-scan-progress-phase">{ phase }</p>
			</div>
			<div
				className="stmc-scan-progress-track"
				role="progressbar"
				aria-valuemin={ 0 }
				aria-valuemax={ 100 }
				aria-valuenow={ pct }
			>
				<div
					className="stmc-scan-progress-fill"
					style={ { width: `${ pct }%` } }
				/>
			</div>
			<div className="stmc-scan-progress-meta">
				<span>{ `${ pct }%` }</span>
				<span className="stmc-scan-progress-detail">{ detail }</span>
			</div>
			<div className="stmc-scan-progress-actions">
				<button
					type="button"
					className="stmc-btn stmc-btn-sm stmc-btn-danger-outline"
					onClick={ onCancel }
					disabled={ busy }
				>
					{ __( 'Cancel scan', 'store-maintenance-checklist' ) }
				</button>
				<span className="stmc-scan-progress-hint">
					{ __(
						'One scan at a time · cancel stops further batches',
						'store-maintenance-checklist'
					) }
				</span>
			</div>
		</section>
	);
}
