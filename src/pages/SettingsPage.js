/**
 * Settings page.
 */

import { __, sprintf, _n } from '@wordpress/i18n';
import Canvas from '../components/Canvas';
import { useSettings } from '../hooks/useSettings';

/**
 * @param {Object}   props
 * @param {Function} props.onNavigate
 */
export default function SettingsPage( { onNavigate } ) {
	const {
		settings,
		draft,
		loading,
		saving,
		error,
		saved,
		updateDraft,
		resetDefaults,
		save,
	} = useSettings();

	const forceDefined = Boolean(
		settings?.constants?.force_production_defined
	);
	const ignoredCount = Number( settings?.ignored_count ) || 0;

	const actions = (
		<>
			<button
				type="button"
				className="stmc-btn stmc-btn-sm"
				onClick={ resetDefaults }
				disabled={ saving }
			>
				{ __( 'Reset defaults', 'store-maintenance-checklist-for-woocommerce' ) }
			</button>
			<button
				type="button"
				className="stmc-btn stmc-btn-primary stmc-btn-sm"
				onClick={ () => save() }
				disabled={ saving }
			>
				{ saving
					? __( 'Saving…', 'store-maintenance-checklist-for-woocommerce' )
					: __( 'Save settings', 'store-maintenance-checklist-for-woocommerce' ) }
			</button>
		</>
	);

	return (
		<Canvas
			title={ __( 'Settings', 'store-maintenance-checklist-for-woocommerce' ) }
			meta={ __(
				'Severity overrides, scan bounds, and ignore housekeeping',
				'store-maintenance-checklist-for-woocommerce'
			) }
			actions={ actions }
		>
			<h2 className="stmc-section-heading">
				{ __( 'Scan behaviour', 'store-maintenance-checklist-for-woocommerce' ) }
			</h2>
			<p className="stmc-section-desc">
				{ __(
					'Controls how severity is calculated and how large catalogs are scanned. Nothing here sends data off-site.',
					'store-maintenance-checklist-for-woocommerce'
				) }
			</p>

			{ error ? (
				<div className="stmc-danger-banner" role="alert">
					<div className="stmc-banner-body">
						<strong>
							{ __( 'Error', 'store-maintenance-checklist-for-woocommerce' ) }
						</strong>
						<p>{ error }</p>
					</div>
				</div>
			) : null }

			{ saved ? (
				<div className="stmc-info-banner" role="status">
					<div className="stmc-banner-body">
						<strong>
							{ __(
								'Settings saved',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</strong>
					</div>
				</div>
			) : null }

			{ loading ? (
				<p className="stmc-section-desc">
					{ __( 'Loading…', 'store-maintenance-checklist-for-woocommerce' ) }
				</p>
			) : (
				<div className="stmc-settings-stack">
					<div className="stmc-settings-card">
						<div className="stmc-toggle-row">
							<div className="stmc-toggle-copy">
								<div className="stmc-toggle-label">
									{ __(
										'Treat as production for scan severity',
										'store-maintenance-checklist-for-woocommerce'
									) }
								</div>
								<div className="stmc-toggle-desc">
									{ __(
										'Use full production severities even when WP_ENVIRONMENT_TYPE is local, development, or staging. Useful for QA on staging. Constant STMC_FORCE_PRODUCTION_SEVERITY overrides this when defined.',
										'store-maintenance-checklist-for-woocommerce'
									) }
									{ forceDefined ? (
										<>
											{ ' ' }
											<strong>
												{ __(
													'Constant is currently defined.',
													'store-maintenance-checklist-for-woocommerce'
												) }
											</strong>
										</>
									) : null }
								</div>
							</div>
							<button
								type="button"
								className={
									draft.force_production_severity
										? 'stmc-toggle on'
										: 'stmc-toggle'
								}
								aria-pressed={ draft.force_production_severity }
								aria-label={ __(
									'Treat as production for scan severity',
									'store-maintenance-checklist-for-woocommerce'
								) }
								disabled={ forceDefined }
								onClick={ () =>
									updateDraft( {
										force_production_severity:
											! draft.force_production_severity,
									} )
								}
							/>
						</div>
						<div className="stmc-toggle-row">
							<div className="stmc-toggle-copy">
								<div className="stmc-toggle-label">
									{ __(
										'Show further tools links',
										'store-maintenance-checklist-for-woocommerce'
									) }
								</div>
								<div className="stmc-toggle-desc">
									{ __(
										'When a finding has an optional StoreCrafted product, show a clearly labeled “Further tools” line after the free/core fix path.',
										'store-maintenance-checklist-for-woocommerce'
									) }
								</div>
							</div>
							<button
								type="button"
								className={
									draft.show_further_tools
										? 'stmc-toggle on'
										: 'stmc-toggle'
								}
								aria-pressed={ draft.show_further_tools }
								aria-label={ __(
									'Show further tools links',
									'store-maintenance-checklist-for-woocommerce'
								) }
								onClick={ () =>
									updateDraft( {
										show_further_tools:
											! draft.show_further_tools,
									} )
								}
							/>
						</div>
					</div>

					<h3 className="stmc-subsection-title">
						{ __(
							'Catalog scan bounds',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</h3>
					<div className="stmc-settings-card">
						<div className="stmc-field-block">
							<label
								className="stmc-field-label"
								htmlFor="stmc-bound-products"
							>
								{ __(
									'Max products per scan',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</label>
							<p className="stmc-field-hint">
								{ __(
									'Keeps admin requests predictable on large catalogs. Partial scans mark the score as provisional.',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</p>
							<input
								className="stmc-field-input"
								id="stmc-bound-products"
								type="number"
								min={ 100 }
								max={ 10000 }
								step={ 100 }
								value={ draft.max_products }
								onChange={ ( e ) =>
									updateDraft( {
										max_products: Number( e.target.value ),
									} )
								}
							/>
						</div>
						<div className="stmc-field-block">
							<label
								className="stmc-field-label"
								htmlFor="stmc-bound-variations"
							>
								{ __(
									'Max variations per scan',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</label>
							<p className="stmc-field-hint">
								{ __(
									'Applied after the product bound when checking incomplete variation prices.',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</p>
							<input
								className="stmc-field-input"
								id="stmc-bound-variations"
								type="number"
								min={ 100 }
								max={ 20000 }
								step={ 100 }
								value={ draft.max_variations }
								onChange={ ( e ) =>
									updateDraft( {
										max_variations: Number(
											e.target.value
										),
									} )
								}
							/>
						</div>
					</div>

					<h3 className="stmc-subsection-title">
						{ __(
							'Developer override',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</h3>
					<div className="stmc-settings-card">
						<div className="stmc-field-block">
							<div className="stmc-field-label">
								{ __(
									'wp-config constant',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</div>
							<p className="stmc-field-hint">
								{ __(
									'Add this to force production severities in local/CI without using the admin toggle.',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</p>
							<pre className="stmc-code-block">
								{
									"define( 'STMC_FORCE_PRODUCTION_SEVERITY', true );"
								}
							</pre>
						</div>
						<div className="stmc-field-block">
							<div className="stmc-field-label">
								{ __(
									'Filter',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</div>
							<p className="stmc-field-hint">
								{ __(
									'For PHPUnit and advanced customization.',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</p>
							<pre className="stmc-code-block">
								{
									"add_filter( 'stmc_treat_as_production', '__return_true' );"
								}
							</pre>
						</div>
					</div>

					<h3 className="stmc-subsection-title">
						{ __(
							'Ignored checks',
							'store-maintenance-checklist-for-woocommerce'
						) }
					</h3>
					<div className="stmc-settings-card">
						<div className="stmc-toggle-row">
							<div className="stmc-toggle-copy">
								<div className="stmc-toggle-label">
									{ sprintf(
										/* translators: %d: ignored check count */
										_n(
											'%d ignored check on this site',
											'%d ignored checks on this site',
											ignoredCount,
											'store-maintenance-checklist-for-woocommerce'
										),
										ignoredCount
									) }
								</div>
								<div className="stmc-toggle-desc">
									{ __(
										'Ignored checks are excluded from the score. Manage them on the Checklist with the Ignored filter.',
										'store-maintenance-checklist-for-woocommerce'
									) }
								</div>
							</div>
							<button
								type="button"
								className="stmc-btn stmc-btn-sm"
								onClick={ () =>
									onNavigate( '/', { status: 'ignored' } )
								}
							>
								{ __(
									'Manage on Checklist',
									'store-maintenance-checklist-for-woocommerce'
								) }
							</button>
						</div>
					</div>

					<div className="stmc-settings-actions">
						<button
							type="button"
							className="stmc-btn stmc-btn-primary"
							onClick={ () => save() }
							disabled={ saving }
						>
							{ __(
								'Save settings',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</button>
						<button
							type="button"
							className="stmc-btn"
							onClick={ () => onNavigate( '/' ) }
						>
							{ __(
								'Back to Checklist',
								'store-maintenance-checklist-for-woocommerce'
							) }
						</button>
					</div>
				</div>
			) }
		</Canvas>
	);
}
