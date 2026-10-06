/**
 * IK2 plugin build config, composed with the theme's in the root config.
 * `uniqueName` keeps its chunk-loading global apart from the theme runtime's.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const src = ( file ) => path.resolve( __dirname, 'src', file );

module.exports = {
	...defaultConfig,
	name: 'plugin-ik2',
	entry: {
		'code-highlight': src( 'code-highlight/view.js' ),
		'code-highlight-editor': src( 'code-highlight/editor.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
		uniqueName: 'ik2-plugin',
	},
};
