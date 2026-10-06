/**
 * Adds a `language` attribute and sidebar picker to core/code. The canvas
 * painter (view.js) reads the `data-language` this puts on the block wrapper.
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import LANGUAGES from './languages.json';

const OPTIONS = [ { value: '', label: __( 'Plain text', 'ik2' ) } ].concat(
	LANGUAGES
);

function addLanguageAttribute( settings, name ) {
	if ( name !== 'core/code' ) {
		return settings;
	}
	return {
		...settings,
		attributes: {
			...settings.attributes,
			language: { type: 'string' },
		},
	};
}

const withLanguagePicker = createHigherOrderComponent(
	( BlockEdit ) =>
		function CodeLanguageEdit( props ) {
			if ( props.name !== 'core/code' ) {
				return <BlockEdit { ...props } />;
			}
			const { attributes, setAttributes } = props;
			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<PanelBody title={ __( 'Syntax highlighting', 'ik2' ) }>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Language', 'ik2' ) }
								value={ attributes.language || '' }
								options={ OPTIONS }
								onChange={ ( language ) =>
									setAttributes( {
										language: language || undefined,
									} )
								}
							/>
						</PanelBody>
					</InspectorControls>
				</>
			);
		},
	'withLanguagePicker'
);

const withLanguageData = createHigherOrderComponent(
	( BlockListBlock ) =>
		function CodeLanguageBlock( props ) {
			const language = props.attributes?.language;
			if ( props.name !== 'core/code' || ! language ) {
				return <BlockListBlock { ...props } />;
			}
			return (
				<BlockListBlock
					{ ...props }
					wrapperProps={ {
						...props.wrapperProps,
						'data-language': language,
					} }
				/>
			);
		},
	'withLanguageData'
);

addFilter(
	'blocks.registerBlockType',
	'ik2/code-language/attribute',
	addLanguageAttribute
);
addFilter( 'editor.BlockEdit', 'ik2/code-language/picker', withLanguagePicker );
addFilter(
	'editor.BlockListBlock',
	'ik2/code-language/data',
	withLanguageData
);
