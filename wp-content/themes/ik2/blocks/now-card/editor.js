/**
 * IK2 — Now Card (editor script).
 *
 * Hand-authored, no build step: uses WordPress globals (`wp.blocks`,
 * `wp.element`, `wp.blockEditor`, `wp.i18n`) exposed by core, so this file
 * is enqueued verbatim.
 *
 * Renders the /now card chrome in the canvas — dot, `// /now` label, an
 * inline-editable date and footer note — around an InnerBlocks list of
 * ik2/now-item entries. Entries are added with the appender and removed or
 * reordered like any other block.
 *
 * @param {Object} wp The global WordPress namespace exposed by core.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps, useInnerBlocksProps, RichText, InnerBlocks } =
		wp.blockEditor;
	const { __ } = wp.i18n;

	const ALLOWED_BLOCKS = [ 'ik2/now-item' ];
	const TEMPLATE = [ [ 'ik2/now-item' ] ];

	function Edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( { className: 'ik-now' } );
		const innerBlocksProps = useInnerBlocksProps(
			{},
			{
				allowedBlocks: ALLOWED_BLOCKS,
				template: TEMPLATE,
				templateLock: false,
				renderAppender: InnerBlocks.ButtonBlockAppender,
			}
		);

		return el(
			'aside',
			blockProps,
			el(
				'p',
				{ className: 'ik-now__head' },
				el( 'span', {
					className: 'ik-now__dot',
					'aria-hidden': 'true',
				} ),
				el( 'span', { className: 'ik-now__label' }, '// /now' ),
				el( RichText, {
					tagName: 'span',
					className: 'ik-now__date',
					value: attributes.date,
					allowedFormats: [],
					withoutInteractiveFormatting: true,
					placeholder: __( 'Last updated…', 'ik2' ),
					onChange: ( date ) => setAttributes( { date } ),
				} )
			),
			el( 'div', innerBlocksProps ),
			el( RichText, {
				tagName: 'p',
				className: 'ik-now__foot',
				value: attributes.foot,
				allowedFormats: [ 'core/link', 'core/italic' ],
				placeholder: __( 'Footer note…', 'ik2' ),
				onChange: ( foot ) => setAttributes( { foot } ),
			} )
		);
	}

	registerBlockType( 'ik2/now-card', {
		apiVersion: 3,
		edit: Edit,
		save: () => el( InnerBlocks.Content ),
	} );
} )( window.wp );
