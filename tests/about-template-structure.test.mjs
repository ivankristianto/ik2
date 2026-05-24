import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const template = readFileSync(
	'wp-content/themes/ik2/templates/page-about.html',
	'utf8'
);
const columnsPattern = readFileSync(
	'wp-content/themes/ik2/patterns/about-page-columns.php',
	'utf8'
);
const elsewherePattern = readFileSync(
	'wp-content/themes/ik2/patterns/about-page-elsewhere.php',
	'utf8'
);

assert.doesNotMatch(
	template,
	/<!-- wp:html\b/,
	'Expected the About template to avoid Custom HTML blocks so the Site Editor can preview it reliably.'
);

assert.match(
	template,
	/<!-- wp:columns\b/,
	'Expected the About template to use native columns for the focus and timeline section.'
);

assert.match(
	template,
	/<!-- wp:buttons\b/,
	'Expected the About template to use native buttons for the elsewhere CTA section.'
);

assert.doesNotMatch(
	columnsPattern,
	/<!-- wp:html\b/,
	'Expected the About columns pattern to avoid Custom HTML blocks.'
);

assert.doesNotMatch(
	elsewherePattern,
	/<!-- wp:html\b/,
	'Expected the About elsewhere pattern to avoid Custom HTML blocks.'
);
