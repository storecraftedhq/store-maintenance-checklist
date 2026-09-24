/**
 * White rounded canvas with toolbar + scroll panel.
 */

/**
 * @param {Object}                    props
 * @param {string}                    props.title
 * @param {string}                    [props.meta]
 * @param {import('react').ReactNode} [props.actions]
 * @param {import('react').ReactNode} props.children
 * @param {string}                    [props.className]
 */
export default function Canvas( {
	title,
	meta,
	actions,
	children,
	className = '',
} ) {
	return (
		<main className={ `stmc-canvas ${ className }`.trim() }>
			<header className="stmc-canvas-toolbar">
				<div>
					<div className="stmc-page-title">{ title }</div>
					{ meta ? (
						<div className="stmc-page-meta">{ meta }</div>
					) : null }
				</div>
				{ actions ? (
					<div className="stmc-toolbar-actions">{ actions }</div>
				) : null }
			</header>
			<div className="stmc-panel-scroll">{ children }</div>
		</main>
	);
}
