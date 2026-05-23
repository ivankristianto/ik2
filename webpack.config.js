const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const defaultEntries =
	typeof defaultConfig.entry === 'function'
		? defaultConfig.entry()
		: defaultConfig.entry || {};

module.exports = {
	...defaultConfig,
	entry: {
		...defaultEntries,
		index: path.resolve( __dirname, 'wp-content/themes/ik2/src/index.js' ),
		editor: path.resolve( __dirname, 'wp-content/themes/ik2/src/editor.js' ),
		'blocks/articles-filters/view': path.resolve(
			__dirname,
			'wp-content/themes/ik2/blocks/articles-filters/view.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'wp-content/themes/ik2/build' ),
	},
};
