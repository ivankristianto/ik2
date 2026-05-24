const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const defaultEntries =
	typeof defaultConfig.entry === 'function'
		? defaultConfig.entry()
		: defaultConfig.entry || {};

const themeConfig = {
	...defaultConfig,
	name: 'theme',
	entry: {
		...defaultEntries,
		index: path.resolve( __dirname, 'wp-content/themes/ik2/src/index.js' ),
		editor: path.resolve( __dirname, 'wp-content/themes/ik2/src/editor.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'wp-content/themes/ik2/build' ),
	},
};

const pluginConfig = {
	...defaultConfig,
	name: 'plugin-ik2',
	entry: {
		editor: path.resolve( __dirname, 'wp-content/plugins/ik2/src/editor.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'wp-content/plugins/ik2/build' ),
	},
};

module.exports = [ themeConfig, pluginConfig ];
