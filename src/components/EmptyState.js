/**
 * Empty state when never scanned.
 */

import { __ } from '@wordpress/i18n';

/**
 * @param {Object}   props
 * @param {Function} props.onScan
 * @param {boolean}  [props.busy]
 */
export default function EmptyState( { onScan, busy = false } ) {
	return (
		<section
			className="stmc-empty"
			aria-label={ __( 'Empty state', 'store-maintenance-checklist-for-woocommerce' ) }
		>
			<h2>{ __( 'No scan yet', 'store-maintenance-checklist-for-woocommerce' ) }</h2>
			<p>
				{ __(
					'Run a checklist scan to see prioritized findings. Scans stay on your site — no remote telemetry.',
					'store-maintenance-checklist-for-woocommerce'
				) }
			</p>
			<button
				type="button"
				className="stmc-btn stmc-btn-primary"
				onClick={ onScan }
				disabled={ busy }
			>
				{ __( 'Run first scan', 'store-maintenance-checklist-for-woocommerce' ) }
			</button>
		</section>
	);
}
