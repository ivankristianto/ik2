#!/usr/bin/env node
/**
 * Crawl the site's main flows in headless Chrome and fail on any CSP violation,
 * or on a page whose raw HTML has a script without the header's nonce.
 * Usage: pnpm test:csp [url]   (default http://localhost:8080/)
 * Env: CHROME_PATH overrides the binary; CSP_COOKIE="name=value" adds a logged-in pass.
 */
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const SITE = new URL(process.argv[2] ?? 'http://localhost:8080/');
const CHROME =
	process.env.CHROME_PATH ??
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9334;
const SINGLE_POST = '/?p=1981'; // Code blocks and images linked to their media file.
const SETTLE_MS = 1500;

// Injected by Cloudflare at the edge without a nonce. Open items in the spec, reported but not failed.
const KNOWN = [/static\.cloudflareinsights\.com/, /\/cdn-cgi\//];

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

class Cdp {
	constructor(ws) {
		this.ws = ws;
		this.nextId = 1;
		this.pending = new Map();
		this.listeners = new Map();
		ws.addEventListener('message', (e) => this.dispatch(JSON.parse(e.data)));
	}

	static async connect(url) {
		const ws = new WebSocket(url);
		await new Promise((resolve, reject) => {
			ws.addEventListener('open', resolve);
			ws.addEventListener('error', reject);
		});
		return new Cdp(ws);
	}

	// Replies are matched by id: once a domain is enabled, events arrive in between.
	dispatch(msg) {
		if (msg.id && this.pending.has(msg.id)) {
			const { resolve, reject } = this.pending.get(msg.id);
			this.pending.delete(msg.id);
			if (msg.error) {
				reject(new Error(msg.error.message));
			} else {
				resolve(msg.result);
			}
			return;
		}
		(this.listeners.get(msg.method) ?? []).forEach((fn) => fn(msg.params));
	}

	send(method, params = {}) {
		const id = this.nextId++;
		this.ws.send(JSON.stringify({ id, method, params }));
		return new Promise((resolve, reject) =>
			this.pending.set(id, { resolve, reject }),
		);
	}

	on(method, fn) {
		this.listeners.set(method, [...(this.listeners.get(method) ?? []), fn]);
	}

	once(method, timeout = 30000) {
		return new Promise((resolve, reject) => {
			const timer = setTimeout(
				() => reject(new Error(`timed out waiting for ${method}`)),
				timeout,
			);
			const fns = this.listeners.get(method) ?? [];
			const fn = (params) => {
				clearTimeout(timer);
				this.listeners.set(
					method,
					(this.listeners.get(method) ?? []).filter((f) => f !== fn),
				);
				resolve(params);
			};
			this.listeners.set(method, [...fns, fn]);
		});
	}

	async evaluate(expression) {
		const { result, exceptionDetails } = await this.send('Runtime.evaluate', {
			expression,
			awaitPromise: true,
			returnByValue: true,
		});
		if (exceptionDetails) {
			throw new Error(
				exceptionDetails.exception?.description ?? exceptionDetails.text,
			);
		}
		return result.value;
	}

	close() {
		this.ws.close();
	}
}

const COLLECTOR = `document.addEventListener( 'securitypolicyviolation', ( e ) => {
	window.__cspReport( JSON.stringify( {
		page: e.documentURI,
		directive: e.effectiveDirective,
		blocked: e.blockedURI,
		source: e.sourceFile ? e.sourceFile + ':' + e.lineNumber : '',
		sample: e.sample,
		disposition: e.disposition,
	} ) );
} );`;

function waitFor(page, expression, label, timeout = 10000) {
	return page.evaluate(`new Promise( ( resolve, reject ) => {
		const end = Date.now() + ${timeout};
		const tick = () => {
			const value = ( () => ${expression} )();
			if ( value ) return resolve( value );
			if ( Date.now() > end ) return reject( new Error( ${JSON.stringify(`timed out waiting for ${label}`)} ) );
			setTimeout( tick, 100 );
		};
		tick();
	} )`);
}

// Report-Only must be on (or enforced), and every executable script in the raw response must carry its nonce.
async function checkRawHtml(url, cookie) {
	const res = await fetch(url, {
		headers: cookie ? { cookie } : {},
		redirect: 'follow',
	});
	const header =
		res.headers.get('content-security-policy') ??
		res.headers.get('content-security-policy-report-only');
	if (!header) {
		return [`${url}: no Content-Security-Policy header`];
	}
	const nonce = header.match(/'nonce-([^']+)'/)?.[1];
	if (!nonce) {
		return [`${url}: header has no nonce`];
	}
	const html = await res.text();
	const failures = [];
	for (const tag of html.match(/<script\b[^>]*>/gi) ?? []) {
		const type = tag.match(/\btype=["']?([^"'\s>]+)/i)?.[1]?.toLowerCase();
		// JSON data blocks never execute; importmap and speculationrules do need the nonce.
		if (!/json$/.test(type ?? '') && !tag.includes(`nonce="${nonce}"`)) {
			failures.push(`${url}: script without the nonce: ${tag.slice(0, 120)}`);
		}
	}
	return failures;
}

async function openPage(userAgent, cookie) {
	const res = await fetch(`http://localhost:${PORT}/json/new?about:blank`, {
		method: 'PUT',
	});
	const target = await res.json();
	const page = await Cdp.connect(target.webSocketDebuggerUrl);
	const violations = [];
	page.on('Runtime.bindingCalled', ({ name, payload }) => {
		if (name === '__cspReport') {
			violations.push(JSON.parse(payload));
		}
	});
	await page.send('Page.enable');
	await page.send('Runtime.enable');
	await page.send('Network.enable');
	await page.send('Runtime.addBinding', { name: '__cspReport' });
	await page.send('Page.addScriptToEvaluateOnNewDocument', {
		source: COLLECTOR,
	});
	await page.send('Network.setUserAgentOverride', { userAgent });
	await page.send('Emulation.setDeviceMetricsOverride', {
		width: 1920,
		height: 1200,
		deviceScaleFactor: 1,
		mobile: false,
	});
	if (cookie) {
		const [name, ...rest] = cookie.split('=');
		await page.send('Network.setCookie', {
			name,
			value: rest.join('='),
			url: SITE.origin,
		});
	}
	page.violations = violations;
	page.targetId = target.id;
	return page;
}

async function navigate(page, path) {
	const url = new URL(path, SITE).href;
	const loaded = page.once('Page.loadEventFired');
	await page.send('Page.navigate', { url });
	await loaded;
	await sleep(SETTLE_MS);
	return page.evaluate('location.href');
}

const FLOWS = [
	{
		name: 'home',
		run: (page) => navigate(page, '/'),
	},
	{
		name: 'single post, highlighter and lightbox',
		run: async (page) => {
			const url = await navigate(page, SINGLE_POST);
			await waitFor(
				page,
				`document.querySelector( '.ik-lightbox-trigger' )`,
				'a lightbox trigger',
			);
			await page.evaluate(
				`document.querySelector( '.ik-lightbox-trigger' ).click()`,
			);
			await waitFor(
				page,
				`document.querySelector( 'dialog[open]' )`,
				'the lightbox to open',
			);
			await sleep(SETTLE_MS);
			return url;
		},
	},
	{
		name: 'command palette searched',
		run: async (page) => {
			const url = await navigate(page, '/');
			await page.evaluate(
				`document.querySelector( '.ik-header__cmd' ).click()`,
			);
			await waitFor(
				page,
				`document.querySelector( '#ik-command-palette[open] input' )`,
				'the palette',
			);
			await page.evaluate(`( () => {
				const input = document.querySelector( '#ik-command-palette input' );
				input.value = 'wordpress';
				input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			} )()`);
			await sleep(SETTLE_MS);
			return url;
		},
	},
	{
		name: 'articles, filters through the router',
		run: async (page) => {
			const url = await navigate(page, '/articles/');
			// A full reload would also change the URL; the marker only survives client navigation.
			await page.evaluate('window.__ikSmoke = true');
			for (let i = 0; i < 2; i++) {
				const before = await page.evaluate('location.href');
				await page.evaluate(
					`document.querySelector( '.ik-articles-filters__pill:not(.is-active)' ).click()`,
				);
				await waitFor(
					page,
					`location.href !== ${JSON.stringify(before)}`,
					'router navigation',
				);
				await sleep(SETTLE_MS);
				if (!(await page.evaluate('window.__ikSmoke === true'))) {
					throw new Error(
						'filter click reloaded the page instead of client navigation',
					);
				}
			}
			return url;
		},
	},
	{
		name: '404',
		run: (page) => navigate(page, '/csp-smoke-missing-page/'),
	},
	{
		// Projects are non-public with no rewrite, so there is no single project page.
		name: 'projects',
		run: (page) => navigate(page, '/projects/'),
	},
];

const profile = mkdtempSync(join(tmpdir(), 'ik2-csp-'));
const chrome = spawn(
	CHROME,
	[
		'--headless=new',
		`--remote-debugging-port=${PORT}`,
		`--user-data-dir=${profile}`,
		'--no-first-run',
		'about:blank',
	],
	{ stdio: 'ignore' },
);

async function browserUserAgent() {
	for (let attempt = 0; attempt < 20; attempt++) {
		try {
			const res = await fetch(`http://localhost:${PORT}/json/version`);
			const { 'User-Agent': ua } = await res.json();
			// Cloudflare may skip its edge injections for headless clients, which would hide them.
			return ua.replace('HeadlessChrome', 'Chrome');
		} catch {
			await sleep(250);
		}
	}
	throw new Error(`Chrome did not open a DevTools port at ${CHROME}`);
}

async function runPass(label, userAgent, cookie) {
	const failures = [];
	const known = [];
	const visited = new Set();
	for (const flow of FLOWS) {
		const page = await openPage(userAgent, cookie);
		try {
			visited.add(await flow.run(page));
		} catch (err) {
			failures.push(`${label} ${flow.name}: ${err.message}`);
		}
		for (const v of page.violations) {
			const line = `${label} ${flow.name}: ${v.directive} blocked ${v.blocked || '(inline)'} on ${v.page}${v.source ? ` from ${v.source}` : ''}${v.sample ? ` "${v.sample}"` : ''}`;
			(KNOWN.some((re) => re.test(v.blocked)) ? known : failures).push(line);
		}
		page.close();
		await fetch(`http://localhost:${PORT}/json/close/${page.targetId}`);
	}
	for (const url of visited) {
		failures.push(
			...(await checkRawHtml(url, cookie)).map((f) => `${label} ${f}`),
		);
	}
	return { label, failures, known, pages: visited.size };
}

let exitCode = 0;
try {
	const userAgent = await browserUserAgent();
	const passes = [await runPass('anonymous', userAgent)];
	if (process.env.CSP_COOKIE) {
		passes.push(await runPass('logged-in', userAgent, process.env.CSP_COOKIE));
	} else {
		console.log(
			'- logged-in pass skipped: set CSP_COOKIE="name=value" to run it',
		);
	}
	for (const { label, failures, known, pages } of passes) {
		known.forEach((k) => console.warn(`! known Cloudflare injection: ${k}`));
		failures.forEach((f) => console.error(`✖ ${f}`));
		if (failures.length) {
			exitCode = 1;
		}
		console.log(
			`${failures.length ? '✖' : '✔'} ${label}: ${FLOWS.length} flows, ${pages} pages, ${failures.length} failures on ${SITE.origin}`,
		);
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
