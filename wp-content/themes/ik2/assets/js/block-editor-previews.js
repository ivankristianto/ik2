/**
 * IK2 — Shared editor previews for server-rendered theme blocks.
 *
 * The blocks below are registered server-side via `register_block_type()` with
 * a `render.php` file. The site editor needs a matching client-side `edit`
 * component, otherwise it shows the "block not supported" placeholder. This
 * script wires up a generic `ServerSideRender` preview for each, so the editor
 * canvas shows the same HTML the front end ships.
 *
 * `home-projects-preview` has its own custom editor.js (curated picker UI) and
 * is intentionally not listed here.
 *
 * @param {Object} wp The global WordPress namespace exposed by core.
 */
( function ( wp ) {
	if ( ! wp || ! wp.blocks || ! wp.element || ! wp.serverSideRender ) {
		return;
	}

	const { registerBlockType } = wp.blocks;
	const { createElement: el } = wp.element;
	const { useBlockProps } = wp.blockEditor;
	const ServerSideRender = wp.serverSideRender;

	const BLOCKS = [
		'ik2/articles-filters',
		'ik2/contact-channels',
		'ik2/projects-archive',
		'ik2/resume-experience',
		'ik2/resume-skills',
	];

	function makeEdit( name ) {
		return function Edit( props ) {
			const blockProps = useBlockProps();
			return el(
				'div',
				blockProps,
				el( ServerSideRender, {
					block: name,
					attributes: props.attributes,
				} )
			);
		};
	}

	BLOCKS.forEach( ( name ) => {
		registerBlockType( name, {
			edit: makeEdit( name ),
			save: () => null,
		} );
	} );
} )( window.wp );
