#!/usr/bin/env node
/**
 * Crawl the main flows in headless Chrome; fail on CSP violations or scripts without the header's nonce.
 * Usage: pnpm test:csp [url]. Env: CHROME_PATH, CSP_COOKIE="name=value", CSP_ALLOW_EDGE=1 (see EDGE_INJECTIONS).
 */
import { execFileSync, spawn } from 'node:child_process';
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const SITE = new URL(process.argv[2] ?? 'http://localhost:8080/');
const CHROME =
	process.env.CHROME_PATH ??
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PORT = 9334;
// A slug, not an ID: local and prod IDs differ. Code blocks and images linked to their media file.
const SINGLE_POST = '/chrome-modern-web-guidance/';
const SETTLE_MS = 1500;
const REPO = join(dirname(fileURLToPath(import.meta.url)), '..');

// Cloudflare injects these without a nonce. Until the spec's open rows are settled, CSP_ALLOW_EDGE=1 reports them as warnings.
const EDGE_INJECTIONS = [
	/^https:\/\/static\.cloudflareinsights\.com\//,
	/\/cdn-cgi\/scripts\/[^/]+\/cloudflare-static\/email-decode\.min\.js/,
];
const ALLOW_EDGE = process.env.CSP_ALLOW_EDGE === '1';
const isAllowedEdge = (url) =>
	ALLOW_EDGE && EDGE_INJECTIONS.some((re) => re.test(url));

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

	once(method) {
		return new Promise((resolve, reject) => {
			const timer = setTimeout(
				() => reject(new Error(`timed out waiting for ${method}`)),
				30000,
			);
			const fn = (params) => {
				clearTimeout(timer);
				this.listeners.set(
					method,
					(this.listeners.get(method) ?? []).filter((f) => f !== fn),
				);
				resolve(params);
			};
			this.on(method, fn);
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
	} ) );
} );`;

function waitFor(page, expression, label) {
	return page.evaluate(`new Promise( ( resolve, reject ) => {
		const end = Date.now() + 10000;
		const tick = () => {
			const value = ( () => ${expression} )();
			if ( value ) return resolve( value );
			if ( Date.now() > end ) return reject( new Error( ${JSON.stringify(`timed out waiting for ${label}`)} ) );
			setTimeout( tick, 100 );
		};
		tick();
	} )`);
}

async function checkHeaderAndNonces(url, cookie) {
	const res = await fetch(url, {
		headers: cookie ? { cookie } : {},
		redirect: 'follow',
	});
	const header =
		res.headers.get('content-security-policy') ??
		res.headers.get('content-security-policy-report-only');
	if (!header) {
		return {
			failures: [`${url}: no Content-Security-Policy header`],
			warnings: [],
		};
	}
	const nonce = header.match(/'nonce-([^']+)'/)?.[1];
	if (!nonce) {
		return { failures: [`${url}: header has no nonce`], warnings: [] };
	}
	const html = await res.text();
	const failures = [];
	const warnings = [];
	for (const tag of html.match(/<script\b[^>]*>/gi) ?? []) {
		const type = tag.match(/\btype=["']?([^"'\s>]+)/i)?.[1]?.toLowerCase();
		// JSON data blocks never execute; importmap and speculationrules do need the nonce.
		if (!/json$/.test(type ?? '') && !tag.includes(`nonce="${nonce}"`)) {
			const src = new URL(tag.match(/\bsrc="([^"]+)"/)?.[1] ?? '', url).href;
			(isAllowedEdge(src) ? warnings : failures).push(
				`${url}: script without the nonce: ${tag.slice(0, 120)}`,
			);
		}
	}
	return { failures, warnings };
}

async function openPage(session) {
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
	await page.send('Runtime.addBinding', { name: '__cspReport' });
	await page.send('Page.addScriptToEvaluateOnNewDocument', {
		source: COLLECTOR,
	});
	await page.send('Network.enable');
	await page.send('Network.setUserAgentOverride', {
		userAgent: session.userAgent,
	});
	await page.send('Emulation.setDeviceMetricsOverride', {
		width: 1920,
		height: 1200,
		deviceScaleFactor: 1,
		mobile: false,
	});
	if (session.cookie) {
		const [name, ...rest] = session.cookie.split('=');
		await page.send('Network.setCookie', {
			name,
			value: rest.join('='),
			url: SITE.origin,
		});
	}
	return {
		page,
		violations,
		close: async () => {
			page.close();
			await fetch(`http://localhost:${PORT}/json/close/${target.id}`);
		},
	};
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
		run: ({ page }) => navigate(page, '/'),
	},
	{
		name: 'single post, highlighter and lightbox',
		run: async ({ page }) => {
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
		run: async ({ page }) => {
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
		run: async ({ page }) => {
			const url = await navigate(page, '/articles/');
			// Under enforcement a refused preload never fetches, so it has no resource entry.
			const unfetched = await page.evaluate(
				`[ ...document.querySelectorAll( 'link[rel=modulepreload]' ) ].map( ( l ) => l.href ).filter( ( h ) => ! performance.getEntriesByName( h ).length )`,
			);
			if (unfetched.length) {
				throw new Error(`modulepreload never fetched: ${unfetched.join(', ')}`);
			}
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
		run: ({ page }) => navigate(page, '/csp-smoke-missing-page/'),
	},
	{
		name: 'projects',
		run: ({ page }) => navigate(page, '/projects/'),
	},
	{
		// Pages with an email address: if one reaches the HTML unencoded, Cloudflare's email obfuscation injects a script without the nonce.
		name: 'contact',
		run: ({ page }) => navigate(page, '/contact/'),
	},
	{
		name: 'resume',
		run: ({ page }) => navigate(page, '/resume/'),
	},
	{
		name: 'accessibility',
		run: ({ page }) => navigate(page, '/accessibility/'),
	},
];

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

// On the local stack, a short-lived admin cookie from wp-cli, so the logged-in pass always runs.
function localLoginCookie() {
	if (!['localhost', '127.0.0.1'].includes(SITE.hostname)) {
		return null;
	}
	try {
		const cookie = execFileSync(
			'docker',
			[
				'compose',
				'exec',
				'-T',
				'wp-cli',
				'wp',
				'eval',
				'echo LOGGED_IN_COOKIE . "=" . wp_generate_auth_cookie( 1, time() + 900, "logged_in" );',
			],
			{ cwd: REPO, encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] },
		).trim();
		return cookie.includes('=') ? cookie : null;
	} catch {
		return null;
	}
}

function describe(v) {
	const source = v.source ? ` from ${v.source}` : '';
	const sample = v.sample ? ` "${v.sample}"` : '';
	return `${v.directive} blocked ${v.blocked || '(inline)'} on ${v.page}${source}${sample}`;
}

async function runPass(session) {
	const failures = [];
	const warnings = [];
	const visited = new Set();
	for (const flow of FLOWS) {
		const tab = await openPage(session);
		try {
			visited.add(await flow.run(tab));
		} catch (err) {
			failures.push(`${session.label} ${flow.name}: ${err.message}`);
		}
		for (const v of tab.violations) {
			const line = `${session.label} ${flow.name}: ${describe(v)}`;
			(isAllowedEdge(v.blocked) ? warnings : failures).push(line);
		}
		await tab.close();
	}
	for (const url of visited) {
		const found = await checkHeaderAndNonces(url, session.cookie);
		failures.push(...found.failures.map((f) => `${session.label} ${f}`));
		warnings.push(...found.warnings.map((w) => `${session.label} ${w}`));
	}
	return {
		...session,
		failures,
		warnings,
		flows: FLOWS.length,
		pages: visited.size,
	};
}

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

let exitCode = 0;
try {
	const userAgent = await browserUserAgent();
	const cookie = process.env.CSP_COOKIE || localLoginCookie();
	const passes = [await runPass({ label: 'anonymous', userAgent })];
	if (cookie) {
		passes.push(await runPass({ label: 'logged-in', userAgent, cookie }));
	} else {
		exitCode = 1;
		console.error('✖ logged-in pass skipped: set CSP_COOKIE="name=value"');
	}
	for (const { label, failures, warnings, flows, pages } of passes) {
		warnings.forEach((w) => console.warn(`! Cloudflare edge injection: ${w}`));
		failures.forEach((f) => console.error(`✖ ${f}`));
		if (failures.length) {
			exitCode = 1;
		}
		console.log(
			`${failures.length ? '✖' : '✔'} ${label}: ${flows} flows, ${pages} pages, ${failures.length} failures on ${SITE.origin}`,
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
