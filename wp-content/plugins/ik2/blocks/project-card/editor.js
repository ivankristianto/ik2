/**
 * IK2 — Project Card (editor script).
 *
 * `ik2/project-card` resolves its post via (1) the `postId` attribute, (2)
 * Query Loop context, or (3) the current post. In the site editor canvas,
 * only #1 reliably works for standalone insertions, so the editor exposes a
 * NumberControl to set `postId`; with no value, a placeholder is shown.
 * Inside a Query Loop / Post Template, the parent block supplies `postId`
 * via context and `ServerSideRender` picks the right project automatically.
 *
 * @param {Object} wp The global WordPress namespace exposed by core.
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.serverSideRender ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, __experimentalNumberControl, TextControl, ToggleControl, Placeholder } =
		wp.components;
	const NumberControl = __experimentalNumberControl || TextControl;
	const { __ } = wp.i18n;
	const ServerSideRender = wp.serverSideRender;

	function Edit( { attributes, setAttributes, context } ) {
		const blockProps = useBlockProps();
		const contextPostId = context && parseInt( context.postId, 10 );
		const resolvedId =
			( attributes.postId && attributes.postId > 0 ) ? attributes.postId :
			( Number.isInteger( contextPostId ) && contextPostId > 0 ? contextPostId : 0 );

		return el(
			'div',
			blockProps,
			el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Project Card', 'ik2' ), initialOpen: true },
					el( NumberControl, {
						label: __( 'Project ID', 'ik2' ),
						help: __(
							'Leave empty when used inside a Query Loop — the loop supplies the post via context. Set an ID to preview a specific project here.',
							'ik2'
						),
						value: attributes.postId || 0,
						min: 0,
						onChange: ( value ) =>
							setAttributes( {
								postId: parseInt( value, 10 ) || 0,
							} ),
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Compact', 'ik2' ),
						checked: !! attributes.compact,
						onChange: ( compact ) => setAttributes( { compact } ),
						__nextHasNoMarginBottom: true,
					} )
				)
			),
			resolvedId > 0
				? el( ServerSideRender, {
						block: 'ik2/project-card',
						attributes: { ...attributes, postId: resolvedId },
				  } )
				: el(
						Placeholder,
						{
							label: __( 'Project Card', 'ik2' ),
							instructions: __(
								'Pick a Project ID in the sidebar to preview, or insert this block inside a Query Loop set to the Project post type.',
								'ik2'
							),
						}
				  )
		);
	}

	registerBlockType( 'ik2/project-card', {
		edit: Edit,
		save: () => null,
	} );
} )( window.wp );
