/**
 * Theme build config.
 *
 * Extends the @wordpress/scripts default config so the build is reproducible
 * from the repo (the root `wp-scripts build` would look for `src/` at the repo
 * root and silently produce nothing). It:
 *
 *  - builds from THIS theme's `src/`, regardless of the cwd the build runs from;
 *  - emits two entries — `index` (front-end JS + `style-index.css`) and
 *    `editor` (`editor.css`, loaded via `add_editor_style()` in inc/setup.php);
 *  - writes to THIS theme's `build/`.
 *
 * Run via the root scripts: `pnpm build` / `pnpm start`.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'src/index.js' ),
		editor: path.resolve( __dirname, 'src/editor.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};
