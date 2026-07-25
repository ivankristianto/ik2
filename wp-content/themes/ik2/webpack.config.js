/**
 * Theme build config.
 *
 * Extends the @wordpress/scripts default config so the build is reproducible
 * from the repo (the root `wp-scripts build` would look for `src/` at the repo
 * root and silently produce nothing). It:
 *
 *  - builds from THIS theme's `src/`, regardless of the cwd the build runs from;
 *  - writes to THIS theme's `build/`.
 *
 * Entries:
 *
 *  - `index`  — front-end JS (command palette).
 *  - `editor` — `editor.css`, loaded via `add_editor_style()` in inc/setup.php.
 *  - one entry per split stylesheet. The theme's front-end CSS is delivered in
 *    pieces (see inc/assets.php): `critical.css` is inlined in <head> on every
 *    page, `section-*.css` load only on the templates that use them, and
 *    `palette.css` loads async. Each SCSS file is its own entry so the
 *    wp-scripts pipeline (sass → autoprefixer → cssnano) emits a standalone
 *    `[name].css`. webpack-remove-empty-scripts drops the empty `[name].js`
 *    runtime a CSS-only entry would otherwise leave behind.
 *
 * Block styles are hand-authored plain CSS in each block dir and need no build.
 *
 * Run via the root scripts: `pnpm build` / `pnpm start`.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

const src = ( file ) => path.resolve( __dirname, 'src', file );

module.exports = {
	...defaultConfig,
	entry: {
		index: src( 'index.js' ),
		editor: src( 'editor.js' ),
		critical: src( 'critical.scss' ),
		'section-home': src( 'sections/home.scss' ),
		'section-articles': src( 'styles/_articles.scss' ),
		'section-single': src( 'styles/_article-single.scss' ),
		'section-about': src( 'styles/_about.scss' ),
		'section-contact': src( 'styles/_contact.scss' ),
		'section-resume': src( 'styles/_resume.scss' ),
		'section-speaking': src( 'styles/_speaking-page.scss' ),
		palette: src( 'styles/_palette.scss' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
	plugins: [ ...defaultConfig.plugins, new RemoveEmptyScriptsPlugin() ],
};
