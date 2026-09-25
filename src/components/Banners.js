/**
 * Environment, provisional, and stalled banners.
 */

import { __, sprintf } from '@wordpress/i18n';
import { getAdminConfig } from '../api/client';

/**
 * @param {Object}   props
 * @param {Object}   [props.environment]
 * @param {Object}   [props.summary]
 * @param {Object}   [props.status]
 * @param {boolean}  [props.showStalled]
 * @param {Function} [props.onNavigate]
 * @param {Function} [props.onRetry]
 * @param {Function} [props.onCancel]
 * @param {boolean}  [props.busy]
 */
export default function Banners( {
	environment,
	summary,
	status,
	showStalled = false,
	onNavigate,
	onRetry,
	onCancel,
	busy = false,
} ) {
	const { scheduledActionsUrl } = getAdminConfig();
	const softened = Boolean( environment?.softened );
	const envType = environment?.type || 'local';
	const provisional = Boolean(
		summary?.provisional || status?.provisional || status?.partial_catalog
	);
	const productsScanned = status?.products_scanned;
	const productsBound = status?.products_bound;
	// Prefer summary from last scan when status lacks bound fields.
	const scanned = productsScanned ?? summary?.products_scanned ?? null;
	const bound = productsBound ?? summary?.products_bound ?? null;

	return (
		<>
			{ softened ? (
				<div className="stmc-info-banner" role="status">
					<div className="stmc-banner-body">
						<strong>
							{ __(
								'Non-production environment',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</strong>
						<p>
							<code>{ `WP_ENVIRONMENT_TYPE=${ envType }` }</code>
							{ ' — ' }
							{ __(
								'severities are relaxed. Force production scoring in Settings or via',
								'store-maintenance-checklist-for-woocommerce'
							) }{ ' ' }
							<code>STMC_FORCE_PRODUCTION_SEVERITY</code>.
						</p>
					</div>
					<div className="stmc-banner-actions">
						<button
							type="button"
							className="stmc-btn stmc-btn-sm"
							onClick={ () => onNavigate?.( '/settings' ) }
						>
							{ __( 'Settings', 'store-maintenance-checklist-for-woocommerce' ) }
						</button>
					</div>
				</div>
			) : null }

			{ provisional && ! showStalled ? (
				<div className="stmc-warning-banner" role="status">
					<div className="stmc-banner-body">
						<strong>
							{ __(
								'Partial catalog scan',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</strong>
						<p>
							{ typeof scanned === 'number' &&
							typeof bound === 'number'
								? sprintf(
										/* translators: 1: products scanned, 2: total products bound */
										__(
											'Checked %1$s of %2$s products. Score is',
											'store-maintenance-checklist-for-woocommerce'
										),
										Number( scanned ).toLocaleString(),
										Number( bound ).toLocaleString()
								  )
								: __(
										'Catalog scan was bounded. Score is',
										'store-maintenance-checklist-for-woocommerce'
								  ) }{ ' ' }
							<span className="stmc-pill">
								{ __(
									'Provisional',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</span>{ ' ' }
							{ __(
								'until a full bounded pass completes.',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</p>
					</div>
				</div>
			) : null }

			{ showStalled ? (
				<div
					className="stmc-danger-banner stmc-scan-stalled"
					role="alert"
				>
					<div className="stmc-banner-body">
						<strong>
							{ __(
								'Background jobs may be stalled',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</strong>
						<p>
							{ __(
								'This scan is waiting on WooCommerce Action Scheduler, but no batch has completed recently. WP-Cron may be delayed on this host — open Scheduled Actions, or retry when cron is healthy.',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</p>
					</div>
					<div className="stmc-banner-actions stmc-banner-actions-stack">
						<button
							type="button"
							className="stmc-btn stmc-btn-sm stmc-btn-primary"
							onClick={ onRetry }
							disabled={ busy }
						>
							{ __(
								'Retry / nudge',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</button>
						{ scheduledActionsUrl ? (
							<a
								className="stmc-btn stmc-btn-sm"
								href={ scheduledActionsUrl }
							>
								{ __(
									'Scheduled Actions',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</a>
						) : null }
						<button
							type="button"
							className="stmc-btn stmc-btn-sm stmc-btn-danger-outline"
							onClick={ onCancel }
							disabled={ busy }
						>
							{ __(
								'Cancel scan',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</button>
					</div>
				</div>
			) : null }
		</>
	);
}
