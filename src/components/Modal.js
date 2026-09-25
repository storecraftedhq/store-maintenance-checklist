/**
 * Reusable modal dialog for the admin shell.
 */

import { useEffect, useId, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * @param {Object}                    props
 * @param {boolean}                   props.isOpen
 * @param {string}                    props.title
 * @param {Function}                  props.onClose
 * @param {import('react').ReactNode} props.children
 * @param {import('react').ReactNode} [props.footer]
 * @param {string}                    [props.className]
 */
export default function Modal( {
	isOpen,
	title,
	onClose,
	children,
	footer = null,
	className = '',
} ) {
	const titleId = useId();
	const panelRef = useRef( null );
	const previousFocusRef = useRef( null );

	useEffect( () => {
		if ( ! isOpen ) {
			return undefined;
		}
		const panel = panelRef.current;
		const ownerDocument = panel?.ownerDocument || document;
		previousFocusRef.current = ownerDocument.activeElement;

		const onKey = ( event ) => {
			if ( event.key === 'Escape' ) {
				onClose();
			}
		};
		ownerDocument.addEventListener( 'keydown', onKey );

		const focusable = panel?.querySelector(
			'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
		);
		if ( focusable && typeof focusable.focus === 'function' ) {
			focusable.focus();
		}

		return () => {
			ownerDocument.removeEventListener( 'keydown', onKey );
			const previous = previousFocusRef.current;
			if ( previous && typeof previous.focus === 'function' ) {
				previous.focus();
			}
		};
	}, [ isOpen, onClose ] );

	if ( ! isOpen ) {
		return null;
	}

	return (
		<div className="stmc-modal-overlay">
			<button
				type="button"
				className="stmc-modal-backdrop"
				aria-label={ __(
					'Close dialog',
					'store-maintenance-checklist-for-woocommerce'
				) }
				onClick={ onClose }
			/>
			<div
				ref={ panelRef }
				className={
					className ? `stmc-modal ${ className }` : 'stmc-modal'
				}
				role="dialog"
				aria-modal="true"
				aria-labelledby={ titleId }
			>
				<div className="stmc-modal-header">
					<h2 id={ titleId } className="stmc-modal-title">
						{ title }
					</h2>
					<button
						type="button"
						className="stmc-modal-close"
						onClick={ onClose }
						aria-label={ __(
							'Close',
							'store-maintenance-checklist-for-woocommerce'
						) }
					>
						×
					</button>
				</div>
				<div className="stmc-modal-body">{ children }</div>
				{ footer ? (
					<div className="stmc-modal-footer">{ footer }</div>
				) : null }
			</div>
		</div>
	);
}
