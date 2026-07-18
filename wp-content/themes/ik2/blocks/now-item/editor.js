/**
 * IK2 — Now Item (editor script).
 *
 * Hand-authored, no build step: uses WordPress globals (`wp.blocks`,
 * `wp.element`, `wp.blockEditor`, `wp.i18n`) exposed by core, so this file
 * is enqueued verbatim.
 *
 * One /now card entry, edited inline: a small mono label above a line of
 * text. Only lives inside ik2/now-card (see `parent` in block.json).
 *
 * @param {Object} wp The global WordPress namespace exposed by core.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps, RichText } = wp.blockEditor;
	const { __ } = wp.i18n;

	function Edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( { className: 'ik-now__group' } );

		return el(
			'div',
			blockProps,
			el( RichText, {
				tagName: 'p',
				className: 'ik-now__group-title',
				value: attributes.label,
				allowedFormats: [],
				withoutInteractiveFormatting: true,
				placeholder: __( 'Currently…', 'ik2' ),
				onChange: ( label ) => setAttributes( { label } ),
			} ),
			el( RichText, {
				tagName: 'p',
				className: 'ik-now__item',
				value: attributes.text,
				allowedFormats: [
					'core/bold',
					'core/italic',
					'core/code',
					'core/link',
				],
				placeholder: __( 'What you are up to…', 'ik2' ),
				onChange: ( text ) => setAttributes( { text } ),
			} )
		);
	}

	registerBlockType( 'ik2/now-item', {
		apiVersion: 3,
		edit: Edit,
		save: () => null,
	} );
} )( window.wp );
