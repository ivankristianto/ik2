/**
 * IK2 — Lately card (editor script). Hand-authored, no build step.
 *
 * The card is filled on the server from GitHub and the latest talk, so the
 * canvas shows a note instead of editable rows. `save` returns null: saving
 * the page drops any inner /now items left from the old hand-written card.
 *
 * @param {Object} wp The global WordPress namespace exposed by core.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps } = wp.blockEditor;
	const { __ } = wp.i18n;

	function Edit() {
		const blockProps = useBlockProps( { className: 'ik-now' } );

		return el(
			'section',
			blockProps,
			el(
				'p',
				{ className: 'ik-now__head' },
				el( 'span', { className: 'ik-now__label' }, 'lately' )
			),
			el(
				'p',
				{ className: 'ik-now__foot' },
				__(
					'Filled in automatically from GitHub push dates on linked projects and the latest talk. Nothing to edit here.',
					'ik2'
				)
			)
		);
	}

	registerBlockType( 'ik2/now-card', {
		apiVersion: 3,
		edit: Edit,
		save: () => null,
	} );
} )( window.wp );
