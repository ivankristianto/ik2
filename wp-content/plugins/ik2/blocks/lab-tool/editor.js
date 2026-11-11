/**
 * IK2 — Lab tool (editor script). Hand-authored, no build step.
 *
 * The frame is built on the server from the entry's "Lab tool" sidebar fields,
 * so the canvas shows a note instead of loading the tool.
 *
 * @param {Object} wp The global WordPress namespace exposed by core.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps } = wp.blockEditor;
	const { __ } = wp.i18n;

	function Edit() {
		return el(
			'div',
			useBlockProps( { className: 'ik-lab-tool' } ),
			el(
				'p',
				{ className: 'ik-lab-tool__note' },
				__(
					'The tool appears here on the published page. Set its address in the Lab tool panel of the entry’s sidebar.',
					'ik2'
				)
			)
		);
	}

	registerBlockType( 'ik2/lab-tool', {
		apiVersion: 3,
		edit: Edit,
		save: () => null,
	} );
} )( window.wp );
