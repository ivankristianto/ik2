import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const pattern = readFileSync(
	'wp-content/themes/ik2/patterns/articles-archive-header.php',
	'utf8'
);

assert.doesNotMatch(
	pattern,
	/wp_count_posts\s*\(/,
	'Expected the articles archive header pattern to avoid runtime post counts inside static block markup.'
);

assert.doesNotMatch(
	pattern,
	/&rsquo;/,
	'Expected the articles archive header pattern to use literal punctuation in block text, not HTML entities.'
);
