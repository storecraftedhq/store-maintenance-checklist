/**
 * Checklist page — metrics, progress, findings, passed.
 */

import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Canvas from '../components/Canvas';
import MetricStrip from '../components/MetricStrip';
import ScanProgress from '../components/ScanProgress';
import FindingsTable from '../components/FindingsTable';
import EmptyState from '../components/EmptyState';
import Banners from '../components/Banners';
import IgnoreFindingModal from '../components/IgnoreFindingModal';
import { downloadCsv } from '../api/client';
import { filterFindings } from '../utils/filters';
import {
	shouldShowEmptyState,
	shouldShowProgressPanel,
	shouldShowStalledBanner,
} from '../utils/progress';

/**
 * @param {Object}   props
 * @param {Object}   props.scanHook
 * @param {Function} props.onNavigate
 * @param {string}   [props.initialStatusFilter]
 */
export default function ChecklistPage( {
	scanHook,
	onNavigate,
	initialStatusFilter = 'open',
} ) {
	const {
		scan,
		loading,
		error,
		busy,
		start,
		cancel,
		retry,
		ignore,
		restore,
	} = scanHook;

	const [ metric, setMetric ] = useState( 'all' );
	const [ search, setSearch ] = useState( '' );
	const [ area, setArea ] = useState( 'all' );
	const [ statusFilter, setStatusFilter ] = useState( initialStatusFilter );
	const [ ignoreTarget, setIgnoreTarget ] = useState( null );
	const [ ignoreBusy, setIgnoreBusy ] = useState( false );

	useEffect( () => {
		setStatusFilter( initialStatusFilter );
	}, [ initialStatusFilter ] );

	const status = scan?.status;
	const summary = scan?.summary;
	const passed = scan?.passed || [];
	const running = shouldShowProgressPanel( status );
	const stalled = shouldShowStalledBanner( status );
	const empty = shouldShowEmptyState( scan );
	const showFurtherTools =
		scan?.settings?.show_further_tools !== false &&
		scan?.environment?.show_further_tools !== false;

	const filtered = useMemo(
		() =>
			filterFindings( scan?.findings || [], {
				metric,
				area,
				status: statusFilter,
				search,
			} ),
		[ scan?.findings, metric, area, statusFilter, search ]
	);

	const onIgnoreRequest = ( finding ) => {
		setIgnoreTarget( finding );
	};

	const onIgnoreConfirm = async ( reason ) => {
		if ( ! ignoreTarget?.id ) {
			return;
		}
		setIgnoreBusy( true );
		try {
			await ignore( ignoreTarget.id, reason );
			setIgnoreTarget( null );
		} catch ( err ) {
			/* eslint-disable-next-line no-alert -- surface REST errors without a toast system */
			window.alert( err?.message || String( err ) );
		} finally {
			setIgnoreBusy( false );
		}
	};

	const onRestore = async ( checkId ) => {
		try {
			await restore( checkId );
		} catch ( err ) {
			/* eslint-disable-next-line no-alert -- surface REST errors without a toast system */
			window.alert( err?.message || String( err ) );
		}
	};

	const onExport = async () => {
		try {
			await downloadCsv();
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
				onClick={ () => onNavigate( '/history' ) }
			>
				{ __( 'History', 'store-maintenance-checklist' ) }
			</button>
			<button
				type="button"
				className="stmc-btn stmc-btn-sm"
				onClick={ onExport }
				disabled={ running || empty }
			>
				{ __( 'Export CSV', 'store-maintenance-checklist' ) }
			</button>
			{ running ? (
				<button
					type="button"
					className="stmc-btn stmc-btn-sm stmc-btn-danger-outline"
					onClick={ () => cancel() }
					disabled={ busy }
				>
					{ __( 'Cancel scan', 'store-maintenance-checklist' ) }
				</button>
			) : null }
			<button
				type="button"
				className="stmc-btn stmc-btn-primary stmc-btn-sm"
				onClick={ () => start() }
				disabled={ running || busy }
			>
				{ running
					? __( 'Scanning…', 'store-maintenance-checklist' )
					: __( 'Run scan', 'store-maintenance-checklist' ) }
			</button>
		</>
	);

	return (
		<Canvas
			title={ __( 'Checklist', 'store-maintenance-checklist' ) }
			meta={ __(
				'Prioritized findings · score is secondary · ignored checks excluded',
				'store-maintenance-checklist'
			) }
			actions={ actions }
		>
			<h2 className="stmc-section-heading">
				{ __(
					'Store health this scan',
					'store-maintenance-checklist'
				) }
			</h2>
			<p className="stmc-section-desc">
				{ __(
					'Read-only checks for sellability, payments, shipping, email, and order ops. Fix paths use WooCommerce core first; optional tools appear only when relevant.',
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

			{ loading && ! scan ? (
				<p className="stmc-section-desc">
					{ __( 'Loading…', 'store-maintenance-checklist' ) }
				</p>
			) : null }

			{ empty ? (
				<EmptyState onScan={ () => start() } busy={ busy } />
			) : (
				<>
					<Banners
						environment={ scan?.environment }
						summary={ summary }
						status={ status }
						showStalled={ stalled }
						onNavigate={ onNavigate }
						onRetry={ () => retry() }
						onCancel={ () => cancel() }
						busy={ busy }
					/>

					{ running ? (
						<ScanProgress
							status={ status }
							stalled={ stalled }
							onCancel={ () => cancel() }
							busy={ busy }
						/>
					) : null }

					<MetricStrip
						summary={ summary }
						activeFilter={ metric }
						onFilter={ setMetric }
						dimmed={ running }
					/>

					<FindingsTable
						findings={ filtered }
						search={ search }
						area={ area }
						status={ statusFilter }
						onSearch={ setSearch }
						onArea={ setArea }
						onStatus={ setStatusFilter }
						onIgnore={ onIgnoreRequest }
						onRestore={ onRestore }
						dimmed={ running }
						showFurtherTools={ showFurtherTools }
					/>

					<IgnoreFindingModal
						finding={ ignoreTarget }
						busy={ ignoreBusy }
						onClose={ () => {
							if ( ! ignoreBusy ) {
								setIgnoreTarget( null );
							}
						} }
						onConfirm={ onIgnoreConfirm }
					/>

					{ passed.length > 0 ? (
						<details className="stmc-passed">
							<summary>
								{ sprintf(
									/* translators: %d: number of passed checks */
									__(
										'%d checks passed',
										'store-maintenance-checklist'
									),
									passed.length
								) }
							</summary>
							<ul className="stmc-passed-list">
								{ passed.map( ( item ) => (
									<li key={ item.id }>
										{ item.title || item.id }
									</li>
								) ) }
							</ul>
						</details>
					) : null }

					<footer className="stmc-footer-note">
						<nav
							aria-label={ __(
								'Plugin links',
								'store-maintenance-checklist'
							) }
						>
							<button
								type="button"
								className="stmc-btn-link"
								onClick={ () => onNavigate( '/settings' ) }
							>
								{ __(
									'Settings',
									'store-maintenance-checklist'
								) }
							</button>
							<button
								type="button"
								className="stmc-btn-link"
								onClick={ () => onNavigate( '/history' ) }
							>
								{ __(
									'History',
									'store-maintenance-checklist'
								) }
							</button>
							<button
								type="button"
								className="stmc-btn-link"
								onClick={ () => onNavigate( '/about' ) }
							>
								{ __(
									'About StoreCrafted',
									'store-maintenance-checklist'
								) }
							</button>
						</nav>
						<p>
							{ __(
								'Score excludes ignored checks · Partial catalog scans are marked provisional · No outbound telemetry.',
								'store-maintenance-checklist'
							) }
						</p>
					</footer>
				</>
			) }
		</Canvas>
	);
}
