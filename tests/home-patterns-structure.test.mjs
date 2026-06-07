import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const frontPage = readFileSync(
	'wp-content/themes/ik2/templates/front-page.html',
	'utf8'
);

assert.match(
	frontPage,
	/<!-- wp:post-content\b/,
	'Expected front-page.html to render core/post-content so the static Home page owns the homepage sections.'
);

assert.doesNotMatch(
	frontPage,
	/<!-- wp:ik2\/home-/,
	'Expected front-page.html to contain no hardcoded ik2/home-* blocks; the Home page content decides which sections render.'
);

const homePagePattern = readFileSync(
	'wp-content/themes/ik2/patterns/home-page.php',
	'utf8'
);

assert.match(
	homePagePattern,
	/^ \* Slug: ik2\/home-page$/m,
	'Expected the home-page pattern to register as ik2/home-page (Home_Page_Step seeds the Home page from this slug).'
);

assert.match(
	homePagePattern,
	/^ \* Block Types: core\/post-content$/m,
	'Expected the home-page pattern to declare Block Types: core/post-content so WordPress offers it as a page starter pattern.'
);

assert.match(
	homePagePattern,
	/^ \* Post Types: page$/m,
	'Expected the home-page pattern to declare Post Types: page.'
);

assert.doesNotMatch(
	homePagePattern,
	/<!-- wp:html\b|WP_Query|get_term_by|get_post_meta|file_exists|wp_count_posts|taxQuery/,
	'Expected patterns/home-page.php to stay a thin composition of dynamic section blocks, free of inline logic and Custom HTML.'
);

const sectionOrder = [
	'ik2/home-hero',
	'ik2/home-featured-topics',
	'ik2/home-evergreen-guides',
	'ik2/home-latest-notes',
	'ik2/home-projects-preview',
	'ik2/home-speaking-preview',
];

let cursor = -1;

for ( const blockName of sectionOrder ) {
	const at = homePagePattern.indexOf( `<!-- wp:${ blockName } /-->` );

	assert.notEqual(
		at,
		-1,
		`Expected ${ blockName } to appear in patterns/home-page.php.`
	);

	assert.ok(
		at > cursor,
		`Expected ${ blockName } to appear in patterns/home-page.php after the previous section (template order).`
	);

	cursor = at;
}
