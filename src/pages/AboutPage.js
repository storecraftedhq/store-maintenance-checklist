/**
 * About page — aligned with design-mockup/about.html.
 */

import { __, sprintf } from '@wordpress/i18n';
import Canvas from '../components/Canvas';
import { getAdminConfig } from '../api/client';

/**
 * @param {Object}   props
 * @param {Function} props.onNavigate
 */
export default function AboutPage( { onNavigate } ) {
	const { pluginVersion } = getAdminConfig();
	const version = pluginVersion || '1.0.0';

	const actions = (
		<>
			<a
				className="stmc-btn stmc-btn-sm"
				href="https://storecrafted.com"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'storecrafted.com', 'store-maintenance-checklist-for-woocommerce' ) }
			</a>
			<button
				type="button"
				className="stmc-btn stmc-btn-primary stmc-btn-sm"
				onClick={ () => onNavigate( '/' ) }
			>
				{ __( 'Open Checklist', 'store-maintenance-checklist-for-woocommerce' ) }
			</button>
		</>
	);

	return (
		<Canvas
			title={ __( 'About', 'store-maintenance-checklist-for-woocommerce' ) }
			meta={ __(
				'What this plugin is — and what it is not',
				'store-maintenance-checklist-for-woocommerce'
			) }
			actions={ actions }
		>
			<div className="stmc-about-hero">
				<h2>
					{ __(
						'Store Maintenance Checklist for WooCommerce',
						'store-maintenance-checklist-for-woocommerce'
					) }
				</h2>
				<p>
					{ sprintf(
						/* translators: %s: brand name StoreCrafted */
						__(
							'A read-only ops checklist that helps merchants find catalog, checkout, email, and order issues before customers do. Built by %s. Free on WordPress.org.',
							'store-maintenance-checklist-for-woocommerce'
						),
						'StoreCrafted'
					) }
				</p>
			</div>

			<div className="stmc-info-banner" role="note">
				<div className="stmc-banner-body">
					<strong>
						{ __(
							'Not a maintenance-mode plugin',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</strong>
					<p>
						{ __(
							'This does not close your shop, show a coming-soon page, or put WooCommerce in maintenance mode. It is a recurring checklist you re-run after updates or on a monthly cadence.',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</p>
				</div>
			</div>

			<div className="stmc-about-grid">
				<div className="stmc-about-card">
					<h3>
						{ __(
							'Useful without StoreCrafted',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</h3>
					<p>
						{ __(
							'Every finding includes a free/core fix path in WooCommerce or WordPress. You never need to buy anything to clear a Critical.',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</p>
				</div>
				<div className="stmc-about-card">
					<h3>
						{ __(
							'Optional further tools',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</h3>
					<p>
						{ __(
							'When a finding matches a StoreCrafted product, a clearly labeled optional link may appear — after the free remediation steps.',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</p>
				</div>
				<div className="stmc-about-card">
					<h3>
						{ __(
							'Local & private',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</h3>
					<p>
						{ __(
							'Scans run on your site. No remote scan service, no usage telemetry, no account required.',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</p>
				</div>
				<div className="stmc-about-card">
					<h3>
						{ __(
							'Honest scoring',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</h3>
					<p>
						{ __(
							'Ignored checks are excluded. Partial catalog scans are marked provisional. The score is secondary to findings — not a security rating.',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</p>
				</div>
			</div>

			<h3 className="stmc-subsection-title">
				{ __(
					'How recommendations work',
					'store-maintenance-checklist-for-woocommerce'
				) }
			</h3>
			<ul className="stmc-about-list">
				<li>
					<span className="stmc-icon-dot">1</span>
					<div>
						<strong>
							{ __(
								'Primary action',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</strong>
						<span>
							{ __(
								'Always a WooCommerce / WordPress / hosting step you can take for free.',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</span>
					</div>
				</li>
				<li>
					<span className="stmc-icon-dot">2</span>
					<div>
						<strong>
							{ __(
								'Further tools (optional)',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</strong>
						<span>
							{ __(
								'Only on matching findings — e.g. aged order debt may mention Auto Archive after core housekeeping.',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</span>
					</div>
				</li>
				<li>
					<span className="stmc-icon-dot">3</span>
					<div>
						<strong>
							{ __(
								'Never Critical for “plugin missing”',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</strong>
						<span>
							{ __(
								'We will not raise severity because a StoreCrafted product is not installed.',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</span>
					</div>
				</li>
			</ul>

			<h3 className="stmc-subsection-title">
				{ __(
					'Also from StoreCrafted',
					'store-maintenance-checklist-for-woocommerce'
				) }
			</h3>
			<ul className="stmc-about-list">
				<li>
					<span className="stmc-icon-dot">SC</span>
					<div>
						<strong>
							<a
								href="https://storecrafted.com"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __(
									'Premium WooCommerce extensions',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</a>
						</strong>
						<span>
							{ __(
								'Quantity rules, loyalty, LTV, invoices, order archive, delivery ETAs, and more.',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</span>
					</div>
				</li>
			</ul>

			<div className="stmc-settings-actions">
				<button
					type="button"
					className="stmc-btn stmc-btn-primary"
					onClick={ () => onNavigate( '/' ) }
				>
					{ __( 'Back to Checklist', 'store-maintenance-checklist-for-woocommerce' ) }
				</button>
			</div>

			<p className="stmc-footer-note" style={ { marginTop: 20 } }>
				{ sprintf(
					/* translators: %s: plugin version */
					__(
						'Version %s · PHP 8.1+ · WordPress 7.0+ · WooCommerce 10.0+ · Prefix stmc_',
						'store-maintenance-checklist-for-woocommerce'
					),
					version
				) }
			</p>
		</Canvas>
	);
}
