import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const template = readFileSync(
	'wp-content/themes/ik2/templates/404.html',
	'utf8'
);

assert.match(
	template,
	/<!-- wp:ik2\/not-found\b/,
	'Expected the 404 template to render through the ik2/not-found block so the Site Editor can preview it.'
);

assert.doesNotMatch(
	template,
	/<!-- wp:html -->/,
	'Expected the 404 template to avoid wrapping the page chrome in a Custom HTML block.'
);
