/**
 * ESLint flat config (ESLint 9 / @wordpress/scripts 33+).
 *
 * Replaces the legacy .eslintrc.json, which ESLint 9 no longer reads. Reuses
 * the default config bundled with @wordpress/scripts (so parser/babel
 * resolution stays inside that package) and layers this project's ignores and
 * rule overrides on top.
 */

/**
 * WordPress dependencies
 */
const baseConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	// Project ignores (accumulate with the base config's own global ignores,
	// which already cover build/, node_modules/ and vendor/). Ported from the
	// former .eslintignore, which ESLint 9 no longer reads.
	{
		ignores: [
			// Composer-managed third-party plugins — see CLAUDE.md.
			'wp-content/plugins/**',
			// WordPress core's bundled default themes (seeded from the WP image).
			'wp-content/themes/twenty*/**',
			// Build output.
			'**/dist/**',
			// Sandboxes / prototypes (intentionally lax).
			'samples/**',
			'design-system/ui_kits/**',
			// Generated.
			'**/*.min.js',
		],
	},

	...baseConfig,

	// @wordpress/* modules are runtime externals provided by WordPress and
	// extracted at build time, so they are intentionally not installed.
	{
		rules: {
			'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/' ] } ],
		},
	},
];
