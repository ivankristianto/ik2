/**
 * IK2 — Home Projects Preview (editor script).
 *
 * Hand-authored, no build step: uses WordPress globals (`wp.blocks`,
 * `wp.element`, `wp.blockEditor`, `wp.components`, `wp.data`, `wp.i18n`)
 * exposed by core, so this file is enqueued verbatim.
 *
 * Provides a Sidebar panel that lets the editor curate which Projects show
 * on the homepage by selecting from the list of published Project CPT entries.
 * Up to four picks are shown, in the chosen order. The front end always
 * renders an even count (2 or 4); odd picks are padded up with the latest
 * non-curated projects.
 *
 * @param {Object} wp The global WordPress namespace exposed by core.
 */
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, Button, Notice, Spinner } = wp.components;
	const { useSelect } = wp.data;
	const { __, sprintf } = wp.i18n;

	const META_KEY = 'ik2/home-projects-preview';

	function StatusBadge( { status } ) {
		if ( ! status ) {
			return null;
		}
		return el(
			'span',
			{
				className: 'ik-project__status',
				'data-status': status,
				style: {
					fontFamily: 'monospace',
					fontSize: '11px',
					padding: '2px 8px',
					borderRadius: '999px',
					border: '1px solid #ddd',
					marginLeft: '8px',
					color: '#666',
				},
			},
			status.toLowerCase()
		);
	}

	function ProjectPicker( { value, onChange } ) {
		const projects = useSelect( ( select ) => {
			const records = select( 'core' ).getEntityRecords(
				'postType',
				'project',
				{ per_page: 100, status: 'publish', _fields: 'id,title,meta' }
			);
			return records || null;
		}, [] );

		if ( projects === null ) {
			return el(
				'div',
				{
					style: {
						display: 'flex',
						gap: '8px',
						alignItems: 'center',
					},
				},
				el( Spinner ),
				__( 'Loading projects…', 'ik2' )
			);
		}

		if ( projects.length === 0 ) {
			return el(
				Notice,
				{ status: 'warning', isDismissible: false },
				__(
					'No published Projects yet. Add some from the Projects admin screen.',
					'ik2'
				)
			);
		}

		const options = [
			{ label: __( '— pick a project —', 'ik2' ), value: '' },
		].concat(
			projects.map( ( p ) => ( {
				label: p.title?.raw || `Project #${ p.id }`,
				value: String( p.id ),
			} ) )
		);

		const slots = [ 0, 1, 2, 3 ];
		const current = Array.isArray( value ) ? value : [];

		const setSlot = ( index, idStr ) => {
			const next = [ ...current ];
			const id = parseInt( idStr, 10 );
			if ( Number.isInteger( id ) && id > 0 ) {
				next[ index ] = id;
			} else {
				next[ index ] = undefined;
			}
			onChange( next.filter( ( v ) => Number.isInteger( v ) && v > 0 ) );
		};

		return el(
			Fragment,
			{},
			slots.map( ( index ) =>
				el( SelectControl, {
					key: index,
					label: sprintf(
						/* translators: %d: slot number, 1-indexed. */
						__( 'Card %d', 'ik2' ),
						index + 1
					),
					value: current[ index ] ? String( current[ index ] ) : '',
					options,
					onChange: ( v ) => setSlot( index, v ),
					__nextHasNoMarginBottom: true,
				} )
			),
			el(
				Button,
				{
					variant: 'link',
					isDestructive: true,
					onClick: () => onChange( [] ),
					style: { marginTop: '8px' },
				},
				__( 'Clear all', 'ik2' )
			)
		);
	}

	function Edit( { attributes, setAttributes } ) {
		const blockProps = useBlockProps( {
			style: {
				padding: '16px',
				border: '1px dashed #c3c4c7',
				borderRadius: '8px',
			},
		} );

		const previewProjects = useSelect(
			( select ) => {
				if (
					! Array.isArray( attributes.projectIds ) ||
					attributes.projectIds.length === 0
				) {
					return null;
				}
				return attributes.projectIds.map( ( id ) =>
					select( 'core' ).getEntityRecord(
						'postType',
						'project',
						id,
						{ _fields: 'id,title,excerpt,meta' }
					)
				);
			},
			[ attributes.projectIds ]
		);

		return el(
			'div',
			blockProps,
			el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{
						title: __( 'Curated Projects', 'ik2' ),
						initialOpen: true,
					},
					el( ProjectPicker, {
						value: attributes.projectIds,
						onChange: ( projectIds ) =>
							setAttributes( { projectIds } ),
					} )
				)
			),
			previewProjects === null
				? el(
						Notice,
						{ status: 'info', isDismissible: false },
						__(
							'No projects curated yet — pick up to four from the sidebar. The front end always shows an even count (2 or 4); odd picks are padded up with the latest projects, and an empty list falls back to the four latest.',
							'ik2'
						)
				  )
				: el(
						'ul',
						{
							style: {
								listStyle: 'none',
								margin: 0,
								padding: 0,
								display: 'grid',
								gap: '8px',
							},
						},
						previewProjects.map( ( p, idx ) =>
							el(
								'li',
								{
									key: idx,
									style: {
										padding: '8px 12px',
										background: '#fff',
										border: '1px solid #eee',
										borderRadius: '6px',
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'space-between',
										gap: '8px',
									},
								},
								el(
									'span',
									{},
									( p && p.title?.raw ) ||
										__( 'Loading…', 'ik2' )
								),
								el( StatusBadge, {
									status: p && p.meta?.status,
								} )
							)
						)
				  )
		);
	}

	registerBlockType( META_KEY, {
		apiVersion: 3,
		edit: Edit,
		save: () => null,
	} );
} )( window.wp );
