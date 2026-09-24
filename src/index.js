/**
 * Mount App into #stmc-admin-root.
 */

import { createRoot } from '@wordpress/element';
import domReady from '@wordpress/dom-ready';
import { ensureApiMiddleware } from './api/client';
import App from './App';
import './styles/admin.css';

domReady( () => {
	ensureApiMiddleware();
	const rootEl = document.getElementById( 'stmc-admin-root' );
	if ( ! rootEl ) {
		return;
	}
	createRoot( rootEl ).render( <App /> );
} );
