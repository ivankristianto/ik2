# Research: Giving browser AI agents read-only tools on a WordPress blog

Idea size: medium. Source idea: `.claude/blog/ideas/2026-10-24.md`, item 2.

## 1. Thesis

A WordPress site can hand a browser AI agent three small, read-only tools through WebMCP using nothing but the public REST API and about 440 lines of JavaScript, comments included. The hard parts are keeping drafts out of reach and making cancellation and bad input come back as messages the agent can act on, not thrown errors.

## 2. Audience

WordPress developers who know the REST API and have heard of MCP but have not tried WebMCP. They can read modern JavaScript and webpack config. They do not know the WebMCP API shape.

## 3. Story

- I wanted an agent in the browser to answer "has Ivan written about X?" without scraping HTML. WebMCP lets a page register tools with `document.modelContext.registerTool()`, and the agent calls them like MCP tools.
- First version used `fetch()` with default credentials. While logged in as admin, `search_articles` returned a draft. Anonymous REST requests only see published posts, but my cookie made the request authenticated. Fix: `credentials: 'omit'` on every request, plus `status=publish` in the query string as a second guard.
- Second problem: the agent never saw thrown error messages, only a bare failure. I moved every expected failure (bad `limit`, unknown topic, missing article) into a returned `{ error: "..." }` object. Only cancellation still throws, because the agent asked to stop.
- Third: concurrent `list_articles` calls share one category lookup. Passing the first caller's `AbortSignal` into that shared fetch meant cancelling one call failed the others. The shared lookup now takes no signal, and each caller races it against its own signal with a small `untilAborted()` helper.
- The module loads only where `document.modelContext` exists, through a dynamic `import()`. Every other browser skips the chunk.
- I tested with the node test runner for the pure logic and a headless Chrome smoke script against the local Docker stack. I have not tested with any agent other than the one built into Chrome's testing flag.

## 4. Claims table

| # | Claim | Status | How verified | Source |
|---|-------|--------|--------------|--------|
| 1 | WebMCP tools are registered with `document.modelContext.registerTool()` and take `name`, `description`, `inputSchema`, `execute`, `annotations`. | sourced | Read the explainer | https://github.com/webmachinelearning/webmcp |
| 2 | Chromium exposes WebMCP behind the `#enable-webmcp-testing` flag. | verified | Enabled the flag locally, `'modelContext' in document` returned `true` | https://github.com/webmachinelearning/webmcp |
| 3 | The WebMCP testing flag first shipped in Chrome 146. | unverified | Recalled from a changelog skim, not checked | none |
| 4 | Anonymous `GET /wp/v2/posts` returns only published posts; an authenticated request with `edit_posts` can see other statuses. | sourced | Read the REST reference | https://developer.wordpress.org/rest-api/reference/posts/ |
| 5 | `fetch()` with `credentials: 'omit'` sends no cookies, even same-origin. | sourced | MDN | https://developer.mozilla.org/en-US/docs/Web/API/RequestInit#credentials |
| 6 | Logged in as admin, the default `fetch()` returned a draft from `search_articles`; with `credentials: 'omit'` it did not. | verified | Reproduced on the local stack, searched for a draft's title both ways | local reproduction |
| 7 | `limit` defaults to 10 and is capped at 20. | verified | `DEFAULT_LIMIT` and `MAX_LIMIT` in source, covered by node tests | https://github.com/ivankristianto/ik2/blob/main/wp-content/themes/ik2/src/webmcp-rules.js |
| 8 | The pure helpers are covered by 20 node tests, all passing on Node 24. | verified | `node --test tests/webmcp-rules.test.mjs` printed `tests 20`, `pass 20` | local run |
| 9 | Anonymous REST still lists password-protected posts but withholds the body, flagging `content.protected`. | verified | Created a protected post locally, fetched it anonymously | https://developer.wordpress.org/rest-api/reference/posts/ |
| 10 | Most browser agents will support WebMCP within a year. | unverified | Opinion | none |

## 5. Snippets

Registering one tool (runs in the browser, tested in Chromium with the testing flag on):

```js
document.modelContext.registerTool( {
	name: 'search_articles',
	description: 'Searches published articles by keyword.',
	inputSchema: {
		type: 'object',
		properties: { query: { type: 'string' } },
		required: [ 'query' ],
	},
	annotations: { readOnlyHint: true },
	async execute( { query }, { signal } ) {
		const url = `/wp-json/wp/v2/posts?status=publish&search=${ encodeURIComponent( query ) }&_fields=link,title`;
		const res = await fetch( url, { credentials: 'omit', signal } );
		if ( ! res.ok ) {
			return { error: `The site answered with HTTP ${ res.status }.` };
		}
		return res.json();
	},
} );
```

Loading the module only where the API exists (webpack dynamic import, tested in Chrome and Firefox):

```js
if ( 'modelContext' in document ) {
	import( /* webpackChunkName: "webmcp" */ './webmcp.js' );
}
```

Racing a shared promise against one caller's signal (node 24, covered by the node tests):

```js
export function untilAborted( promise, signal ) {
	return new Promise( ( resolve, reject ) => {
		signal.throwIfAborted();
		const onAbort = () => reject( signal.reason );
		signal.addEventListener( 'abort', onAbort, { once: true } );
		promise
			.then( resolve, reject )
			.finally( () => signal.removeEventListener( 'abort', onAbort ) );
	} );
}
```

## 6. Related

- https://www.ivankristianto.com/chrome-modern-web-guidance/ : my earlier post on Chrome's web guidance; mentions agents but not WebMCP. Link from the intro.
- https://github.com/webmachinelearning/webmcp : the explainer. Covers the API, not how to keep a CMS's private content out of it.
- Existing write-ups found cover demo todo apps. None deal with authenticated cookies leaking drafts, which is the gap.

## 7. Suggested outline

Title candidate: "Read-only WebMCP tools for a WordPress blog, without leaking drafts"
Excerpt candidate: "How I gave browser AI agents search, list and read tools over the WordPress REST API, and the cookie bug that showed them my drafts."

1. What WebMCP is, in two sentences
2. The three tools and their inputs
3. The draft that leaked through my cookie
4. Errors the agent can read
5. Cancelling one call without failing the others
6. Loading it only where it exists
7. What I have not tested

## 8. Questions for Ivan

- Do you want to name the Chrome flag version, or leave it out until you confirm it?
- Would you ship this on a site with members-only content, or does `credentials: 'omit'` feel too thin there?

## 9. Leave out

- The commit-by-commit history of the branch. It does not help a reader.
- The smoke script's internals. Mention it exists and link the repo.
