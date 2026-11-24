import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const template = readFileSync(
	'wp-content/themes/ik2/templates/page-about.html',
	'utf8'
);
const pagePattern = readFileSync(
	'wp-content/themes/ik2/patterns/about-page.php',
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
const introPattern = readFileSync(
	'wp-content/themes/ik2/patterns/about-page-intro.php',
	'utf8'
);

assert.doesNotMatch(
	template,
	/<!-- wp:html\b/,
	'Expected the About template to avoid Custom HTML blocks so the Site Editor can preview it reliably.'
);

assert.match(
	template,
	/<!-- wp:post-content\b/,
	'Expected the About template to render post content, so the About page owns its sections.'
);

assert.match(
	pagePattern,
	/<!-- wp:pattern \{"slug":"ik2\/about-page-intro"\} \/-->\s*<!-- wp:pattern \{"slug":"ik2\/about-page-columns"\} \/-->\s*<!-- wp:pattern \{"slug":"ik2\/about-page-elsewhere"\} \/-->/,
	'Expected the about-page pattern to compose the intro, columns and elsewhere sections in that order.'
);

assert.match(
	pagePattern,
	/<!-- wp:group \{"className":"ik-about__profile"/,
	'Expected the pattern wrapper to scope Profile styling away from legacy saved blocks.'
);

assert.match(
	introPattern,
	/<h1\b[^>]*>Ivan Kristianto<\/h1>[\s\S]*href="\/articles\/"[\s\S]*href="\/projects\/"/,
	'Expected one profile heading followed by links to articles and projects.'
);

assert.match(
	columnsPattern,
	/<!-- wp:group \{"className":"ik-about__cols"/,
	'Expected the About focus and open-source sections to share a native Group block.'
);

assert.match(
	elsewherePattern,
	/<!-- wp:buttons\b/,
	'Expected the About elsewhere pattern to use native buttons for the elsewhere CTA section.'
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
