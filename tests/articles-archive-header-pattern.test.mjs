import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const template = readFileSync(
	'wp-content/themes/ik2/templates/page-articles.html',
	'utf8'
);

assert.doesNotMatch(
	template,
	/wp_count_posts\s*\(/,
	'Expected the articles archive header markup to avoid runtime post counts inside static block markup.'
);

assert.doesNotMatch(
	template,
	/&rsquo;/,
	'Expected the articles archive header markup to use literal punctuation in block text, not HTML entities.'
);
