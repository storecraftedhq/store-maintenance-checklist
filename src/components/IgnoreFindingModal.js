/**
 * Confirm ignore for a finding — reason + confirmation actions.
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Modal from './Modal';

const DEFAULT_REASON = __(
	'Expected for this store',
	'store-maintenance-checklist-for-woocommerce'
);
const MAX_REASON = 200;

/**
 * @param {Object}      props
 * @param {Object|null} props.finding
 * @param {boolean}     props.busy
 * @param {Function}    props.onClose
 * @param {Function}    props.onConfirm (reason: string) => void | Promise
 */
export default function IgnoreFindingModal( {
	finding,
	busy = false,
	onClose,
	onConfirm,
} ) {
	const [ reason, setReason ] = useState( DEFAULT_REASON );

	useEffect( () => {
		if ( finding ) {
			setReason( DEFAULT_REASON );
		}
	}, [ finding ] );

	const open = Boolean( finding );

	return (
		<Modal
			isOpen={ open }
			title={ __( 'Ignore finding', 'store-maintenance-checklist-for-woocommerce' ) }
			onClose={ onClose }
			footer={
				<>
					<button
						type="button"
						className="stmc-btn stmc-btn-sm"
						onClick={ onClose }
						disabled={ busy }
					>
						{ __( 'Cancel', 'store-maintenance-checklist-for-woocommerce' ) }
					</button>
					<button
						type="button"
						className="stmc-btn stmc-btn-primary stmc-btn-sm"
						onClick={ () => onConfirm( reason.trim() ) }
						disabled={ busy }
					>
						{ busy
							? __( 'Ignoring…', 'store-maintenance-checklist-for-woocommerce' )
							: __(
									'Ignore finding',
									'store-maintenance-checklist-for-woocommerce'
							  ) }
					</button>
				</>
			}
		>
			{ finding?.title ? (
				<p className="stmc-modal-lead">
					{ __(
						'This check will be excluded from the score until you restore it.',
						'store-maintenance-checklist-for-woocommerce'
					) }
				</p>
			) : null }
			{ finding?.title ? (
				<p className="stmc-modal-finding">
					<strong>{ finding.title }</strong>
					{ finding.id ? (
						<>
							{ ' · ' }
							<code>{ finding.id }</code>
						</>
					) : null }
				</p>
			) : null }
			<label className="stmc-field-label" htmlFor="stmc-ignore-reason">
				{ __( 'Reason (optional)', 'store-maintenance-checklist-for-woocommerce' ) }
			</label>
			<textarea
				id="stmc-ignore-reason"
				className="stmc-modal-textarea"
				rows={ 3 }
				maxLength={ MAX_REASON }
				value={ reason }
				onChange={ ( event ) => setReason( event.target.value ) }
				disabled={ busy }
			/>
			<p className="stmc-field-hint">
				{ __(
					'Shown on the ignored finding. Max 200 characters.',
					'store-maintenance-checklist-for-woocommerce'
				) }
			</p>
		</Modal>
	);
}
