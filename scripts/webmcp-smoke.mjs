#!/usr/bin/env node
/**
 * Load the site in headless Chrome with WebMCP on and call each article tool.
 * Usage: pnpm test:webmcp [url]   (default http://localhost:8080/; CHROME_PATH overrides the binary)
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const SITE = process.argv[2] ?? 'http://localhost:8080/';
const CHROME =
	process.env.CHROME_PATH ??
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9333;

const profile = mkdtempSync(join(tmpdir(), 'ik2-webmcp-'));
const chrome = spawn(
	CHROME,
	[
		'--headless=new',
		`--remote-debugging-port=${PORT}`,
		`--user-data-dir=${profile}`,
		'--enable-features=WebMCPTesting',
		'--no-first-run',
		'about:blank',
	],
	{ stdio: 'ignore' }
);

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

async function devtoolsTarget() {
	for (let attempt = 0; attempt < 20; attempt++) {
		try {
			const res = await fetch(
				`http://localhost:${PORT}/json/new?${SITE}`,
				{ method: 'PUT' }
			);
			return await res.json();
		} catch {
			await sleep(250);
		}
	}
	throw new Error(`Chrome did not open a DevTools port at ${CHROME}`);
}

async function evaluate(wsUrl, expression) {
	const ws = new WebSocket(wsUrl);
	await new Promise((resolve) => ws.addEventListener('open', resolve));
	const reply = new Promise((resolve) =>
		ws.addEventListener('message', (e) => resolve(JSON.parse(e.data)))
	);
	ws.send(
		JSON.stringify({
			id: 1,
			method: 'Runtime.evaluate',
			params: { expression, awaitPromise: true, returnByValue: true },
		})
	);
	const { result } = await reply;
	ws.close();
	if (result.exceptionDetails) {
		throw new Error(result.exceptionDetails.exception?.description);
	}
	return result.result.value;
}

// Runs in the page, so it can only use what the browser gives it.
const PAGE_CHECKS = `( async () => {
	if ( ! ( 'modelContext' in document ) ) {
		return { failures: [ 'document.modelContext is missing: is the WebMCPTesting feature on?' ] };
	}
	await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );
	const failures = [];
	const expect = ( ok, message ) => ok || failures.push( message );
	const tools = await document.modelContext.getTools();
	const byName = Object.fromEntries( tools.map( ( t ) => [ t.name, t ] ) );
	const call = async ( name, input, options ) =>
		JSON.parse( await document.modelContext.executeTool( byName[ name ], input, options ) );

	for ( const name of [ 'search_articles', 'list_articles', 'get_article' ] ) {
		expect( byName[ name ], name + ' is not registered' );
		expect( byName[ name ]?.annotations?.readOnlyHint === true, name + ' is not marked read-only' );
	}
	if ( failures.length ) {
		return { failures, tools: [] };
	}

	const listed = await call( 'list_articles', { limit: 2 } );
	expect( listed.articles?.length === 2, 'list_articles did not return 2 articles' );

	const url = listed.articles?.[ 0 ]?.url;
	const words = listed.articles?.[ 0 ]?.title.split( ' ' ).slice( 0, 2 ).join( ' ' );
	const found = await call( 'search_articles', { query: words } );
	expect( found.articles?.some( ( a ) => a.url === url ), 'search_articles did not find "' + words + '"' );

	const article = await call( 'get_article', { article: url } );
	expect( article.content?.length > 0, 'get_article returned no content for ' + url );

	expect( ( await call( 'get_article', { article: 'https://example.com/x/' } ) ).error, 'get_article accepted another origin' );
	expect( ( await call( 'get_article', { article: location.origin + '/category/wordpress/' } ) ).error, 'get_article accepted an archive URL' );
	expect( ( await call( 'list_articles', { topic: 'nope' } ) ).error, 'list_articles accepted an unknown topic' );
	expect( ( await call( 'search_articles', {} ) ).error, 'search_articles accepted an empty query' );

	const cancelled = new AbortController();
	cancelled.abort();
	const rejected = await call( 'list_articles', {}, { signal: cancelled.signal } ).then( () => false, () => true );
	expect( rejected, 'a cancelled list_articles call still resolved' );

	const posts = performance.getEntriesByType( 'resource' ).map( ( e ) => e.name ).filter( ( n ) => n.includes( '/wp/v2/posts' ) );
	expect( posts.length > 0, 'no wp/v2/posts requests were made' );
	expect( posts.every( ( n ) => n.includes( 'status=publish' ) ), 'a posts request did not ask for status=publish' );

	return { failures, tools: tools.map( ( t ) => t.name ) };
} )()`;

let exitCode = 0;
try {
	const target = await devtoolsTarget();
	await sleep(2000);
	const { failures, tools } = await evaluate(
		target.webSocketDebuggerUrl,
		PAGE_CHECKS
	);
	if (failures.length) {
		exitCode = 1;
		failures.forEach((f) => console.error(`✖ ${f}`));
	} else {
		console.log(`✔ ${tools.join(', ')} pass on ${SITE}`);
	}
} catch (err) {
	exitCode = 1;
	console.error(`✖ ${err.message}`);
} finally {
	chrome.kill();
	await sleep(300);
	rmSync(profile, { recursive: true, force: true });
}
process.exit(exitCode);
