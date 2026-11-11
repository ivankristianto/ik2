/**
 * Editor entry for the IK2 plugin. Loaded inside the block editor only.
 * Add block filters, editor-only stores, or sidebar plugins here.
 */

import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { createElement } from '@wordpress/element';

const STATUS_OPTIONS = [
	{ label: __( 'Active', 'ik2' ), value: 'Active' },
	{ label: __( 'Experiment', 'ik2' ), value: 'Experiment' },
	{ label: __( 'Archived', 'ik2' ), value: 'Archived' },
];

function normalizeListForEditor( value ) {
	return value.split( '|' ).join( '\n' );
}

function normalizeListForStorage( value ) {
	return value
		.split( /\r?\n/ )
		.map( ( item ) => item.trim() )
		.filter( Boolean )
		.join( '|' );
}

function ProjectMetaPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const postId = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostId(),
		[]
	);

	if ( 'project' !== postType || ! postId ) {
		return null;
	}

	const [ meta = {}, setMeta ] = useEntityProp(
		'postType',
		postType,
		'meta',
		postId
	);

	function updateMetaValue( key, value ) {
		setMeta( {
			...meta,
			[ key ]: value,
		} );
	}

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: 'ik2-project-details',
			title: __( 'Project details', 'ik2' ),
		},
		createElement( SelectControl, {
			label: __( 'Status', 'ik2' ),
			value: meta.status || 'Active',
			options: STATUS_OPTIONS,
			onChange: ( value ) => updateMetaValue( 'status', value ),
		} ),
		createElement( TextareaControl, {
			label: __( 'Stack', 'ik2' ),
			help: __( 'One item per line. Stored as a project tech list.', 'ik2' ),
			value: normalizeListForEditor( meta.tech || '' ),
			onChange: ( value ) =>
				updateMetaValue( 'tech', normalizeListForStorage( value ) ),
		} ),
		createElement( TextareaControl, {
			label: __( 'Links', 'ik2' ),
			help: __(
				'One link per line using Label::URL, for example GitHub::https://github.com/you/repo.',
				'ik2'
			),
			value: normalizeListForEditor( meta.links || '' ),
			onChange: ( value ) =>
				updateMetaValue( 'links', normalizeListForStorage( value ) ),
		} ),
		createElement( TextareaControl, {
			label: __( 'What I learned', 'ik2' ),
			help: __(
				'Short reflection shown on project cards and the single project summary.',
				'ik2'
			),
			value: meta.learned || '',
			onChange: ( value ) => updateMetaValue( 'learned', value ),
		} )
	);
}

registerPlugin( 'ik2-project-meta-panel', {
	render: ProjectMetaPanel,
} );

function LabToolFields() {
	const [ meta = {}, setMeta ] = useEntityProp( 'postType', 'lab', 'meta' );

	function updateMetaValue( key, value ) {
		setMeta( {
			...meta,
			[ key ]: value,
		} );
	}

	return createElement(
		PluginDocumentSettingPanel,
		{
			name: 'ik2-lab-tool',
			title: __( 'Lab tool', 'ik2' ),
		},
		createElement( TextControl, {
			label: __( 'Tool URL', 'ik2' ),
			help: __(
				'Address of the tool on the lab host. Leave empty for a write-up with no tool. Any other host is ignored.',
				'ik2'
			),
			type: 'url',
			value: meta.lab_tool_url || '',
			onChange: ( value ) =>
				updateMetaValue( 'lab_tool_url', value.trim() ),
		} ),
		createElement( TextControl, {
			label: __( 'Source URL', 'ik2' ),
			help: __( 'Link to the source code of the tool. Optional.', 'ik2' ),
			type: 'url',
			value: meta.lab_source_url || '',
			onChange: ( value ) =>
				updateMetaValue( 'lab_source_url', value.trim() ),
		} ),
		createElement( TextControl, {
			label: __( 'Starting height (px)', 'ik2' ),
			help: __(
				'Height of the tool when it first paints, so the text below does not jump. Empty uses the default.',
				'ik2'
			),
			type: 'number',
			min: 0,
			value: meta.lab_min_height || '',
			onChange: ( value ) =>
				updateMetaValue(
					'lab_min_height',
					Math.max( 0, parseInt( value, 10 ) || 0 )
				),
		} )
	);
}

function LabToolPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	return 'lab' === postType ? createElement( LabToolFields ) : null;
}

registerPlugin( 'ik2-lab-tool-panel', {
	render: LabToolPanel,
} );
