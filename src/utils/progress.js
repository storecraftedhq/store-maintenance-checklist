/**
 * Scan progress UI state helpers (unit-tested).
 */

export const POLL_INTERVAL_RUNNING_MS = 2500;
export const POLL_INTERVAL_STALLED_MS = 10000;

/**
 * @typedef {Object} ScanStatus
 * @property {string}  [state]   idle|running|completed|cancelled|failed
 * @property {boolean} [stalled] Stalled flag while running.
 */

/**
 * Poll interval while a scan is active, or null when idle.
 *
 * @param {ScanStatus|null|undefined} status Scan status.
 * @return {number|null} Interval ms or null if polling should stop.
 */
export function getPollIntervalMs( status ) {
	if ( ! status || status.state !== 'running' ) {
		return null;
	}
	return status.stalled ? POLL_INTERVAL_STALLED_MS : POLL_INTERVAL_RUNNING_MS;
}

/**
 * Whether the progress panel should render.
 *
 * @param {ScanStatus|null|undefined} status Scan status.
 * @return {boolean} True when running.
 */
export function shouldShowProgressPanel( status ) {
	return Boolean( status && status.state === 'running' );
}

/**
 * Whether the stalled banner should render.
 *
 * @param {ScanStatus|null|undefined} status Scan status.
 * @return {boolean} True when running and stalled.
 */
export function shouldShowStalledBanner( status ) {
	return Boolean( status && status.state === 'running' && status.stalled );
}

/**
 * Whether the empty “never scanned” state should show.
 *
 * @param {Object|null|undefined} scan Payload from GET /scan.
 * @return {boolean} True when idle with no completed scan.
 */
export function shouldShowEmptyState( scan ) {
	if ( ! scan ) {
		return true;
	}
	const status = scan.status || {};
	if ( status.state === 'running' ) {
		return false;
	}
	if ( scan.last_completed_at ) {
		return false;
	}
	const findings = scan.findings || [];
	const passed = scan.passed || [];
	return findings.length === 0 && passed.length === 0;
}

/**
 * Coarse UI mode for checklist chrome.
 *
 * @param {ScanStatus|null|undefined} status           Scan status.
 * @param {boolean}                   hasCompletedScan Whether a prior scan exists.
 * @return {'empty'|'idle'|'running'|'stalled'} UI mode.
 */
export function getProgressUiState( status, hasCompletedScan ) {
	if ( status && status.state === 'running' ) {
		return status.stalled ? 'stalled' : 'running';
	}
	if ( ! hasCompletedScan ) {
		return 'empty';
	}
	return 'idle';
}
