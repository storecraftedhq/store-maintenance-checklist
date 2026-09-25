/**
 * Sidebar navigation: Checklist / Settings / History / About.
 */

import { __ } from '@wordpress/i18n';

const ITEMS = [
	{
		page: 'checklist',
		path: '/',
		label: __( 'Checklist', 'store-maintenance-checklist-for-woocommerce' ),
		icon: <path d="M9 11l3 3L22 4" />,
		iconExtra: (
			<path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
		),
		badge: true,
	},
	{
		page: 'settings',
		path: '/settings',
		label: __( 'Settings', 'store-maintenance-checklist-for-woocommerce' ),
		icon: (
			<>
				<circle cx="12" cy="12" r="3" />
				<path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
			</>
		),
	},
	{
		page: 'history',
		path: '/history',
		label: __( 'History', 'store-maintenance-checklist-for-woocommerce' ),
		icon: (
			<>
				<circle cx="12" cy="12" r="10" />
				<path d="M12 6v6l4 2" />
			</>
		),
	},
	{
		page: 'about',
		path: '/about',
		label: __( 'About', 'store-maintenance-checklist-for-woocommerce' ),
		icon: (
			<>
				<circle cx="12" cy="12" r="10" />
				<path d="M12 16v-4M12 8h.01" />
			</>
		),
	},
];

/**
 * @param {Object}   props
 * @param {string}   props.page
 * @param {number}   props.openCount
 * @param {Function} props.onNavigate
 */
export default function SidebarNav( { page, openCount, onNavigate } ) {
	return (
		<nav
			className="stmc-sidebar-nav"
			aria-label={ __(
				'Plugin navigation',
				'store-maintenance-checklist-for-woocommerce'
			) }
		>
			{ ITEMS.map( ( item ) => {
				const active = page === item.page;
				return (
					<a
						key={ item.page }
						className={
							active ? 'stmc-nav-item is-active' : 'stmc-nav-item'
						}
						href={ `#${ item.path === '/' ? '/' : item.path }` }
						onClick={ ( e ) => {
							e.preventDefault();
							onNavigate( item.path );
						} }
					>
						<svg
							className="stmc-nav-icon"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="1.5"
						>
							{ item.icon }
							{ item.iconExtra }
						</svg>
						<span className="stmc-nav-label">{ item.label }</span>
						{ item.badge && openCount > 0 ? (
							<span className="stmc-nav-badge">
								{ openCount }
							</span>
						) : null }
						<svg
							className="stmc-nav-chevron"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M9 18l6-6-6-6" />
						</svg>
					</a>
				);
			} ) }
		</nav>
	);
}
