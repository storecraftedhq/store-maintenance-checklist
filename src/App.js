/**
 * Root app: hash router + AppShell + pages.
 */

import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import AppShell from './components/AppShell';
import ChecklistPage from './pages/ChecklistPage';
import SettingsPage from './pages/SettingsPage';
import HistoryPage from './pages/HistoryPage';
import AboutPage from './pages/AboutPage';
import { useHashRoute } from './hooks/useHashRoute';
import { useScan } from './hooks/useScan';

/**
 * @param {string|null} iso     ISO timestamp.
 * @param {string}      envType Environment type.
 * @return {string} Footer label.
 */
function formatFooter( iso, envType ) {
	if ( ! iso ) {
		return __(
			'StoreCrafted · free on WordPress.org',
			'store-maintenance-checklist'
		);
	}
	try {
		const d = new Date( iso );
		const when = d.toLocaleString( undefined, {
			dateStyle: 'medium',
			timeStyle: 'short',
		} );
		return sprintf(
			/* translators: 1: datetime, 2: environment type */
			__( 'Last scan · %1$s · %2$s', 'store-maintenance-checklist' ),
			when,
			envType || 'production'
		);
	} catch ( e ) {
		return __(
			'StoreCrafted · free on WordPress.org',
			'store-maintenance-checklist'
		);
	}
}

export default function App() {
	const { page, query, navigate } = useHashRoute();
	const scanHook = useScan();
	const openCount = Number( scanHook.scan?.summary?.open ) || 0;
	const footerText = useMemo(
		() =>
			formatFooter(
				scanHook.scan?.last_completed_at,
				scanHook.scan?.environment?.type
			),
		[ scanHook.scan?.last_completed_at, scanHook.scan?.environment?.type ]
	);

	const initialStatus =
		query.get( 'status' ) === 'ignored' ? 'ignored' : 'open';

	let content = null;
	switch ( page ) {
		case 'settings':
			content = <SettingsPage onNavigate={ navigate } />;
			break;
		case 'history':
			content = <HistoryPage onNavigate={ navigate } />;
			break;
		case 'about':
			content = <AboutPage onNavigate={ navigate } />;
			break;
		default:
			content = (
				<ChecklistPage
					scanHook={ scanHook }
					onNavigate={ navigate }
					initialStatusFilter={ initialStatus }
				/>
			);
	}

	return (
		<AppShell
			page={ page }
			openCount={ openCount }
			footerText={ footerText }
			onNavigate={ navigate }
		>
			{ content }
		</AppShell>
	);
}
