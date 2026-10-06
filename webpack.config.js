/**
 * One `wp-scripts build` for every first-party bundle. Each config lives next
 * to the code it builds.
 */
module.exports = [
	{ name: 'theme', ...require( './wp-content/themes/ik2/webpack.config.js' ) },
	require( './wp-content/plugins/ik2/webpack.config.js' ),
];
