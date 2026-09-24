/**
 * Dark sidebar + white canvas chrome (Site Editor–style app shell).
 */

import { __ } from '@wordpress/i18n';
import SidebarNav from './SidebarNav';
import { getAdminConfig } from '../api/client';

/**
 * @param {Object}                    props
 * @param {string}                    props.page         Active page key.
 * @param {number}                    [props.openCount]  Open findings badge.
 * @param {string}                    [props.footerText] Sidebar footer text.
 * @param {Function}                  props.onNavigate   Navigate callback.
 * @param {import('react').ReactNode} props.children     Canvas content.
 */
export default function AppShell( {
	page,
	openCount = 0,
	footerText,
	onNavigate,
	children,
} ) {
	const { adminUrl } = getAdminConfig();
	const backHref = adminUrl || '#/';

	return (
		<div className="stmc-app">
			<aside className="stmc-sidebar">
				<div className="stmc-sidebar-header">
					<div className="stmc-sidebar-title-row">
						<a
							className="stmc-back-link"
							href={ backHref }
							aria-label={ __(
								'Back to Dashboard',
								'store-maintenance-checklist'
							) }
						>
							<svg
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="1.5"
								aria-hidden="true"
							>
								<path d="M15 18l-6-6 6-6" />
							</svg>
						</a>
						<h1 className="stmc-sidebar-title">
							{ __(
								'Maintenance Checklist',
								'store-maintenance-checklist'
							) }
						</h1>
					</div>
					<p className="stmc-sidebar-desc">
						{ __(
							'Recurring ops checklist for catalog, checkout, email, and order health — not a maintenance-mode tool.',
							'store-maintenance-checklist'
						) }
					</p>
				</div>

				<SidebarNav
					page={ page }
					openCount={ openCount }
					onNavigate={ onNavigate }
				/>

				<div className="stmc-sidebar-footer">
					{ footerText ||
						__(
							'StoreCrafted · free on WordPress.org',
							'store-maintenance-checklist'
						) }
				</div>
			</aside>

			<div className="stmc-canvas-wrap">{ children }</div>
		</div>
	);
}
