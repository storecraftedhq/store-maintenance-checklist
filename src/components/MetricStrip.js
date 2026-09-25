/**
 * Metric strip: score (disabled) + Open / Critical / Warning / Info filters.
 */

import { __ } from '@wordpress/i18n';

/**
 * @param {Object}   props
 * @param {Object}   [props.summary]
 * @param {string}   props.activeFilter
 * @param {Function} props.onFilter
 * @param {boolean}  [props.dimmed]
 */
export default function MetricStrip( {
	summary,
	activeFilter,
	onFilter,
	dimmed = false,
} ) {
	const s = summary || {};
	const score = s.score ?? '—';
	const provisional = Boolean( s.provisional );

	const cards = [
		{
			key: 'score',
			filter: null,
			disabled: true,
			className: 'stmc-metric-card score accent',
			title: __(
				'Score is informational',
				'store-maintenance-checklist-for-woocommerce'
			),
			content: (
				<>
					<div className="stmc-metric-label">
						{ __(
							'Checklist score',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</div>
					<div className="stmc-metric-row">
						<div className="stmc-metric-value">{ score }</div>
						{ provisional ? (
							<span className="stmc-pill">
								{ __(
									'Provisional',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</span>
						) : null }
					</div>
					<div className="stmc-metric-sub">
						{ __(
							'Not a security or PCI rating',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</div>
				</>
			),
		},
		{
			key: 'all',
			filter: 'all',
			className: 'stmc-metric-card',
			content: (
				<>
					<div className="stmc-metric-value">{ s.open ?? 0 }</div>
					<div className="stmc-metric-label">
						{ __( 'Open', 'store-maintenance-checklist-for-woocommerce' ) }
					</div>
					<div className="stmc-metric-sub">
						{ __(
							'All severities',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</div>
				</>
			),
		},
		{
			key: 'critical',
			filter: 'critical',
			className: 'stmc-metric-card danger',
			content: (
				<>
					<div className="stmc-metric-value">{ s.critical ?? 0 }</div>
					<div className="stmc-metric-label">
						{ __( 'Critical', 'store-maintenance-checklist-for-woocommerce' ) }
					</div>
					<div className="stmc-metric-sub">
						{ __( 'Fix first', 'store-maintenance-checklist-for-woocommerce' ) }
					</div>
				</>
			),
		},
		{
			key: 'warning',
			filter: 'warning',
			className: 'stmc-metric-card warning',
			content: (
				<>
					<div className="stmc-metric-value">{ s.warning ?? 0 }</div>
					<div className="stmc-metric-label">
						{ __( 'Warning', 'store-maintenance-checklist-for-woocommerce' ) }
					</div>
					<div className="stmc-metric-sub">
						{ __( 'Ops risk', 'store-maintenance-checklist-for-woocommerce' ) }
					</div>
				</>
			),
		},
		{
			key: 'info',
			filter: 'info',
			className: 'stmc-metric-card info',
			content: (
				<>
					<div className="stmc-metric-value">{ s.info ?? 0 }</div>
					<div className="stmc-metric-label">
						{ __( 'Info', 'store-maintenance-checklist-for-woocommerce' ) }
					</div>
					<div className="stmc-metric-sub">
						{ __( 'Hygiene', 'store-maintenance-checklist-for-woocommerce' ) }
					</div>
				</>
			),
		},
	];

	return (
		<section
			className={
				dimmed ? 'stmc-metrics-bar is-dimmed' : 'stmc-metrics-bar'
			}
			aria-label={ __( 'Scan summary', 'store-maintenance-checklist-for-woocommerce' ) }
		>
			{ cards.map( ( card ) => {
				const isActive =
					card.filter &&
					( activeFilter === card.filter ||
						( card.filter === 'all' &&
							( activeFilter === 'all' ||
								activeFilter === 'open' ) ) );
				const className = [
					card.className,
					isActive ? 'is-active' : '',
				]
					.filter( Boolean )
					.join( ' ' );

				return (
					<button
						key={ card.key }
						type="button"
						className={ className }
						disabled={ card.disabled }
						title={ card.title }
						aria-pressed={
							card.filter ? Boolean( isActive ) : undefined
						}
						onClick={ () => {
							if ( card.filter ) {
								onFilter( card.filter );
							}
						} }
					>
						{ card.content }
					</button>
				);
			} ) }
		</section>
	);
}
