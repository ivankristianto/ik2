import assert from 'node:assert/strict';
import test from 'node:test';

import {
	readArticleRef,
	articleUrl,
	CATEGORY_SLUGS,
	categoryIdsFor,
	listUrl,
	readListInput,
	readSearchInput,
	searchUrl,
	toArticle,
	toResults,
} from '../wp-content/themes/ik2/src/webmcp-rules.js';

const ORIGIN = 'https://www.ivankristianto.com';
const REST = `${ORIGIN}/wp-json/`;
const stripTags = (html) => html.replace(/<[^>]+>/g, '').trim();
const params = (url) => new URL(url).searchParams;

const post = (overrides) => ({
	id: 1,
	date: '2026-10-06T09:29:18',
	link: `${ORIGIN}/chrome-modern-web-guidance/`,
	title: { rendered: 'Chrome Modern Web Guidance' },
	excerpt: { rendered: '<p>Notes to my future self.</p>', protected: false },
	...overrides,
});

test('a search needs words to look for', () => {
	assert.ok(readSearchInput({}).error);
	assert.ok(readSearchInput({ query: '   ' }).error);
});

test('a missing input object comes back as an error, not a throw', () => {
	assert.ok(readSearchInput(null).error);
	assert.deepEqual(readListInput(null), {
		topic: 'all',
		format: 'all',
		limit: 10,
		page: 1,
	});
});

test('a search fills in the paging defaults the browser leaves out', () => {
	assert.deepEqual(readSearchInput({ query: ' webmcp ' }), {
		query: 'webmcp',
		limit: 10,
		page: 1,
	});
});

test('a limit outside 1 to 20 comes back as an error', () => {
	assert.ok(readSearchInput({ query: 'x', limit: 0 }).error);
	assert.ok(readSearchInput({ query: 'x', limit: 21 }).error);
	assert.ok(readSearchInput({ query: 'x', limit: 2.5 }).error);
	assert.ok(readSearchInput({ query: 'x', page: 0 }).error);
});

test('every posts query asks for published posts only', () => {
	assert.equal(
		params(searchUrl(REST, { query: 'a', limit: 5, page: 1 })).get(
			'status'
		),
		'publish'
	);
	assert.equal(
		params(listUrl(REST, { categoryIds: [], limit: 5, page: 1 })).get(
			'status'
		),
		'publish'
	);
	assert.equal(
		params(articleUrl(REST, { slug: 'some-post' })).get('status'),
		'publish'
	);
});

test('an unknown topic or format is rejected with the allowed values', () => {
	const topic = readListInput({ topic: 'php' });
	assert.ok(topic.error);
	assert.ok(topic.topics.includes('wordpress'));
	assert.ok(readListInput({ format: 'video' }).formats);
});

const idsBySlug = new Map([
	['ai', 3],
	['guide', 4],
]);

test('"all" adds no category filter', () => {
	assert.deepEqual(categoryIdsFor(readListInput({}), idsBySlug), []);
	assert.deepEqual(
		categoryIdsFor(
			readListInput({ topic: 'ai', format: 'guide' }),
			idsBySlug
		),
		[3, 4]
	);
});

test('a topic with no category yet matches nothing instead of everything', () => {
	assert.equal(
		categoryIdsFor(readListInput({ topic: 'security' }), idsBySlug),
		null
	);
});

test('the category lookup covers every topic and format but not "all"', () => {
	assert.ok(!CATEGORY_SLUGS.includes('all'));
	assert.ok(CATEGORY_SLUGS.includes('web-apis'));
	assert.ok(CATEGORY_SLUGS.includes('experiment'));
});

test('a topic plus a format must both match', () => {
	const search = params(
		listUrl(REST, { categoryIds: [3, 4], limit: 10, page: 1 })
	);
	assert.equal(search.get('categories[terms]'), '3,4');
	assert.equal(search.get('categories[operator]'), 'AND');
});

test('an article can be named by its URL or its slug', () => {
	const slug = { slug: 'chrome-modern-web-guidance' };
	assert.deepEqual(
		readArticleRef(`${ORIGIN}/chrome-modern-web-guidance/`, ORIGIN),
		slug
	);
	assert.deepEqual(
		readArticleRef(
			`${ORIGIN}/chrome-modern-web-guidance/?utm=x#top`,
			ORIGIN
		),
		slug
	);
	assert.deepEqual(
		readArticleRef('chrome-modern-web-guidance', ORIGIN),
		slug
	);
});

test('a page of a split article resolves to the article', () => {
	assert.deepEqual(readArticleRef(`${ORIGIN}/long-read/2/`, ORIGIN), {
		slug: 'long-read',
	});
});

test('a ?p= link resolves by post ID', () => {
	assert.deepEqual(readArticleRef(`${ORIGIN}/?p=1981`, ORIGIN), {
		id: 1981,
	});
	assert.equal(params(articleUrl(REST, { id: 1981 })).get('include'), '1981');
});

test('real slugs with underscores or non-Latin letters are accepted', () => {
	assert.deepEqual(
		readArticleRef(`${ORIGIN}/tips-write-print_r-output-to-file/`, ORIGIN),
		{ slug: 'tips-write-print_r-output-to-file' }
	);
	assert.deepEqual(readArticleRef(`${ORIGIN}/%E6%97%A5%E6%9C%AC/`, ORIGIN), {
		slug: '日本',
	});
});

test('archive URLs, other sites and malformed input are not articles', () => {
	assert.equal(readArticleRef(`${ORIGIN}/category/wordpress/`, ORIGIN), null);
	assert.equal(readArticleRef(`${ORIGIN}/tag/php/page/2/`, ORIGIN), null);
	assert.equal(readArticleRef(`${ORIGIN}/`, ORIGIN), null);
	assert.equal(
		readArticleRef('https://example.com/some-post/', ORIGIN),
		null
	);
	assert.equal(readArticleRef(`${ORIGIN}/%E0%A4%A/`, ORIGIN), null);
	assert.equal(readArticleRef('', ORIGIN), null);
	assert.equal(readArticleRef('../wp-admin?x=1', ORIGIN), null);
});

test('password-protected posts are dropped from results', () => {
	const results = toResults(
		[post(), post({ id: 2, excerpt: { rendered: '', protected: true } })],
		2,
		1,
		stripTags
	);
	assert.equal(results.articles.length, 1);
	assert.deepEqual(results.articles[0], {
		title: 'Chrome Modern Web Guidance',
		url: `${ORIGIN}/chrome-modern-web-guidance/`,
		date: '2026-10-06T09:29:18',
		summary: 'Notes to my future self.',
	});
});

test('an article comes back as plain text', () => {
	const article = toArticle(
		post({
			modified: '2026-10-07T10:00:00',
			content: { rendered: '<h2>Setup</h2>', protected: false },
		}),
		stripTags
	);
	assert.equal(article.content, 'Setup');
	assert.equal(article.modified, '2026-10-07T10:00:00');
});
