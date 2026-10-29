import assert from 'node:assert/strict';
import { execFileSync, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import {
	chmodSync,
	copyFileSync,
	existsSync,
	mkdirSync,
	mkdtempSync,
	readFileSync,
	rmSync,
	writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import test, { after } from 'node:test';
import { fileURLToPath } from 'node:url';

const ROOT = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const SCRIPT = join( ROOT, 'scripts', 'secret-scan.sh' );
const ZERO = '0'.repeat( 40 );

const scratch = mkdtempSync( join( tmpdir(), 'secret-scan-' ) );
after( () => rmSync( scratch, { recursive: true, force: true } ) );

// Keep the developer's git config and any inherited overrides out of the throwaway repos.
const env = {
	...process.env,
	GIT_CONFIG_GLOBAL: '/dev/null',
	GIT_CONFIG_NOSYSTEM: '1',
	GIT_AUTHOR_NAME: 'Test',
	GIT_AUTHOR_EMAIL: 'test@example.test',
	GIT_COMMITTER_NAME: 'Test',
	GIT_COMMITTER_EMAIL: 'test@example.test',
};
for ( const key of [
	'GIT_DIR',
	'GIT_WORK_TREE',
	'GIT_INDEX_FILE',
	'BETTERLEAKS_CONFIG',
	'GITLEAKS_CONFIG',
	'BETTERLEAKS_CONFIG_TOML',
	'GITLEAKS_CONFIG_TOML',
	'GITHUB_STEP_SUMMARY',
] ) {
	delete env[ key ];
}

// Built at runtime so no complete token-shaped string is ever committed.
function fakeToken() {
	const alphabet =
		'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	const body = Array.from(
		randomBytes( 36 ),
		( byte ) => alphabet[ byte % alphabet.length ]
	).join( '' );
	return [ 'gh', 'p_', body ].join( '' );
}

let repoCount = 0;

function git( cwd, ...args ) {
	return execFileSync( 'git', args, { cwd, env, encoding: 'utf8' } ).trim();
}

function commit( cwd, file, content, message = `edit ${ file }` ) {
	writeFileSync( join( cwd, file ), content );
	git( cwd, 'add', '--', file );
	git( cwd, 'commit', '-q', '-m', message );
	return git( cwd, 'rev-parse', 'HEAD' );
}

function repo() {
	const dir = join( scratch, `repo-${ ++repoCount }` );
	mkdirSync( dir );
	git( dir, 'init', '-q', '-b', 'main' );
	commit( dir, 'README.md', 'hello\n', 'init' );
	return dir;
}

function scan(
	cwd,
	args,
	{ input = '', script = SCRIPT, extraEnv = {} } = {}
) {
	const result = spawnSync( 'sh', [ script, ...args ], {
		cwd,
		env: { ...env, ...extraEnv },
		input,
		encoding: 'utf8',
	} );
	return { status: result.status, output: result.stdout + result.stderr };
}

function pushLine( localSha, remoteSha, ref = 'refs/heads/main' ) {
	return `${ ref } ${ localSha } ${ ref } ${ remoteSha }\n`;
}

// A copy of the wrapper next to a stand-in binary, for cases the real binary can't produce.
function fakeInstall( binaryScript ) {
	const dir = join( scratch, `fake-${ ++repoCount }` );
	mkdirSync( join( dir, 'scripts' ), { recursive: true } );
	copyFileSync( SCRIPT, join( dir, 'scripts', 'secret-scan.sh' ) );
	if ( binaryScript ) {
		mkdirSync( join( dir, '.tools' ) );
		writeFileSync( join( dir, '.tools', 'betterleaks' ), binaryScript );
		chmodSync( join( dir, '.tools', 'betterleaks' ), 0o755 );
	}
	return join( dir, 'scripts', 'secret-scan.sh' );
}

function assertRedacted( output, token ) {
	assert.ok( ! output.includes( token.slice( 4 ) ), 'token body leaked' );
	assert.ok(
		! output.includes( token.slice( 4, 16 ) ),
		'token prefix leaked'
	);
}

test( 'the pinned binary is installed', () => {
	assert.ok(
		existsSync( join( ROOT, '.tools', 'betterleaks' ) ),
		'run scripts/secret-scan.sh install first'
	);
} );

test( 'a secret in the range is reported with its value redacted', () => {
	const dir = repo();
	const base = git( dir, 'rev-parse', 'HEAD' );
	const token = fakeToken();
	const head = commit( dir, 'config.txt', `token = "${ token }"\n` );

	const { status, output } = scan( dir, [
		'ci',
		'pull_request',
		base,
		head,
	] );

	assert.equal( status, 1, output );
	assert.match( output, /github-pat/ );
	assert.match( output, /config\.txt/ );
	assertRedacted( output, token );
} );

test( 'a secret added and then deleted inside the range is still caught', () => {
	const dir = repo();
	const base = git( dir, 'rev-parse', 'HEAD' );
	commit( dir, 'config.txt', `token = "${ fakeToken() }"\n` );
	const head = commit( dir, 'config.txt', 'token = ""\n' );

	assert.equal( scan( dir, [ 'ci', 'pull_request', base, head ] ).status, 1 );
} );

test( 'a clean range passes', () => {
	const dir = repo();
	const base = git( dir, 'rev-parse', 'HEAD' );
	const head = commit( dir, 'notes.txt', 'nothing to see\n' );

	const { status, output } = scan( dir, [
		'ci',
		'pull_request',
		base,
		head,
	] );

	assert.equal( status, 0, output );
} );

test( 'pre-push scans only the commits being sent', () => {
	const dir = repo();
	git(
		dir,
		'init',
		'-q',
		'--bare',
		join( dir, '..', `remote-${ repoCount }.git` )
	);
	git(
		dir,
		'remote',
		'add',
		'origin',
		join( dir, '..', `remote-${ repoCount }.git` )
	);
	commit( dir, 'old.txt', `token = "${ fakeToken() }"\n` );
	git( dir, 'push', '-q', 'origin', 'main' );
	const pushed = git( dir, 'rev-parse', 'HEAD' );

	const clean = commit( dir, 'notes.txt', 'clean\n' );
	writeFileSync(
		join( dir, 'untracked.txt' ),
		`token = "${ fakeToken() }"\n`
	);
	writeFileSync( join( dir, 'notes.txt' ), `token = "${ fakeToken() }"\n` );
	let result = scan( dir, [ 'pre-push', 'origin', 'url' ], {
		input: pushLine( clean, pushed ),
	} );
	assert.equal( result.status, 0, result.output );

	git( dir, 'checkout', '-q', '--', 'notes.txt' );
	const leaky = commit( dir, 'new.txt', `token = "${ fakeToken() }"\n` );
	result = scan( dir, [ 'pre-push', 'origin', 'url' ], {
		input: pushLine( leaky, pushed ),
	} );
	assert.equal( result.status, 1, result.output );
} );

test( 'the pre-push hook blocks a real git push', () => {
	const dir = repo();
	const remote = join( dir, '..', `remote-${ repoCount }.git` );
	git( dir, 'init', '-q', '--bare', remote );
	git( dir, 'remote', 'add', 'origin', remote );
	git( dir, 'config', 'core.hooksPath', join( ROOT, '.githooks' ) );
	const push = () =>
		spawnSync( 'git', [ 'push', '-q', 'origin', 'main' ], {
			cwd: dir,
			env,
			encoding: 'utf8',
		} );

	let result = push();
	assert.equal( result.status, 0, result.stderr );

	commit( dir, 'config.txt', `token = "${ fakeToken() }"\n` );
	result = push();
	assert.notEqual( result.status, 0 );
	assert.match( result.stderr, /credentials found/ );
	assert.notEqual(
		git( remote, 'rev-parse', 'main' ),
		git( dir, 'rev-parse', 'HEAD' )
	);
} );

test( 'pre-push on a new branch skips commits the remote already has', () => {
	const dir = repo();
	git(
		dir,
		'init',
		'-q',
		'--bare',
		join( dir, '..', `remote-${ repoCount }.git` )
	);
	git(
		dir,
		'remote',
		'add',
		'origin',
		join( dir, '..', `remote-${ repoCount }.git` )
	);
	commit( dir, 'old.txt', `token = "${ fakeToken() }"\n` );
	git( dir, 'push', '-q', 'origin', 'main' );
	git( dir, 'checkout', '-q', '-b', 'feature' );
	const clean = commit( dir, 'notes.txt', 'clean\n' );

	let result = scan( dir, [ 'pre-push', 'origin', 'url' ], {
		input: pushLine( clean, ZERO, 'refs/heads/feature' ),
	} );
	assert.equal( result.status, 0, result.output );

	const leaky = commit( dir, 'new.txt', `token = "${ fakeToken() }"\n` );
	result = scan( dir, [ 'pre-push', 'origin', 'url' ], {
		input: pushLine( leaky, ZERO, 'refs/heads/feature' ),
	} );
	assert.equal( result.status, 1, result.output );

	result = scan( dir, [ 'pre-push', 'origin', 'url' ], {
		input: `(delete) ${ ZERO } refs/heads/feature ${ leaky }\n`,
	} );
	assert.equal( result.status, 0, result.output );
} );

test( 'an inline allow comment does not silence a finding', () => {
	const dir = repo();
	const base = git( dir, 'rev-parse', 'HEAD' );
	const marker = [ 'betterleaks', 'allow' ].join( ':' );
	const head = commit(
		dir,
		'config.txt',
		`token = "${ fakeToken() }" # ${ marker }\n`
	);

	assert.equal( scan( dir, [ 'ci', 'pull_request', base, head ] ).status, 1 );
} );

test( 'a -diff .gitattributes entry does not hide a secret', () => {
	const dir = repo();
	const base = git( dir, 'rev-parse', 'HEAD' );
	commit( dir, '.gitattributes', 'hidden.txt -diff\n' );
	const head = commit( dir, 'hidden.txt', `token = "${ fakeToken() }"\n` );

	assert.equal( scan( dir, [ 'ci', 'pull_request', base, head ] ).status, 1 );
} );

for ( const file of [
	'.betterleaks.toml',
	'.gitleaks.toml',
	'.gitleaksignore',
] ) {
	test( `a ${ file } in the repo stops the scan`, () => {
		const dir = repo();
		const base = git( dir, 'rev-parse', 'HEAD' );
		const head = commit( dir, file, '\n' );

		const { status, output } = scan( dir, [
			'ci',
			'pull_request',
			base,
			head,
		] );

		assert.equal( status, 2, output );
		assert.match( output, new RegExp( file.replace( '.', '\\.' ) ) );
	} );
}

test( 'a config override in the environment stops the scan', () => {
	const dir = repo();
	const base = git( dir, 'rev-parse', 'HEAD' );
	const head = commit( dir, 'notes.txt', 'clean\n' );

	const { status, output } = scan(
		dir,
		[ 'ci', 'pull_request', base, head ],
		{
			extraEnv: {
				BETTERLEAKS_CONFIG_TOML: '[extend]\nuseDefault = false\n',
			},
		}
	);

	assert.equal( status, 2, output );
	assert.match( output, /BETTERLEAKS_CONFIG_TOML/ );
} );

test( '.betterleaksignore accepts only commit-scoped entries with a reason', () => {
	const dir = repo();
	const base = git( dir, 'rev-parse', 'HEAD' );
	const head = commit( dir, 'config.txt', `token = "${ fakeToken() }"\n` );
	const fingerprint = `${ head }:config.txt:github-pat:1`;
	const ignore = join( dir, '.betterleaksignore' );
	const range = [ 'ci', 'pull_request', base, head ];

	writeFileSync( ignore, '# test fixture\nconfig.txt:github-pat:1\n' );
	assert.equal( scan( dir, range ).status, 2 );

	writeFileSync( ignore, `${ fingerprint }\n` );
	assert.equal( scan( dir, range ).status, 2 );

	writeFileSync( ignore, `# test fixture\n${ fingerprint }\n` );
	const { status, output } = scan( dir, range );
	assert.equal( status, 0, output );
} );

test( 'the push and dispatch fallbacks scan the full history', () => {
	const dir = repo();
	commit( dir, 'old.txt', `token = "${ fakeToken() }"\n` );
	commit( dir, 'old.txt', 'gone\n' );
	const head = commit( dir, 'notes.txt', 'clean\n' );

	assert.equal( scan( dir, [ 'ci', 'push', ZERO, head ] ).status, 1 );
	assert.equal(
		scan( dir, [ 'ci', 'push', 'f'.repeat( 40 ), head ] ).status,
		1
	);
	assert.equal( scan( dir, [ 'ci', 'workflow_dispatch' ] ).status, 1 );
} );

test( 'the job summary names changes to files that control the scan', () => {
	const dir = repo();
	const summary = join( dir, '..', `summary-${ repoCount }.md` );
	const base = git( dir, 'rev-parse', 'HEAD' );
	const head = commit( dir, '.gitattributes', '*.bin binary\n' );

	const { status, output } = scan( dir, [ 'ci', 'push', base, head ], {
		extraEnv: { GITHUB_STEP_SUMMARY: summary },
	} );

	assert.equal( status, 0, output );
	assert.match( readFileSync( summary, 'utf8' ), /\.gitattributes/ );
} );

test( 'an empty pull request range fails instead of passing', () => {
	const dir = repo();
	const head = git( dir, 'rev-parse', 'HEAD' );

	assert.equal( scan( dir, [ 'ci', 'pull_request', head, head ] ).status, 2 );
} );

test( 'an unknown revision fails', () => {
	const dir = repo();
	const head = git( dir, 'rev-parse', 'HEAD' );

	assert.equal(
		scan( dir, [ 'ci', 'pull_request', 'f'.repeat( 40 ), head ] ).status,
		2
	);
	assert.equal(
		scan( dir, [ 'ci', 'push', head, 'f'.repeat( 40 ) ] ).status,
		2
	);
} );

test( 'a shallow checkout fails', () => {
	const dir = repo();
	commit( dir, 'notes.txt', 'clean\n' );
	const shallow = join( scratch, `shallow-${ ++repoCount }` );
	git( scratch, 'clone', '-q', '--depth', '1', `file://${ dir }`, shallow );
	const head = git( shallow, 'rev-parse', 'HEAD' );

	assert.equal( scan( shallow, [ 'ci', 'push', ZERO, head ] ).status, 2 );
} );

test( 'running outside a git work tree fails', () => {
	const dir = join( scratch, `plain-${ ++repoCount }` );
	mkdirSync( dir );

	assert.equal( scan( dir, [ 'ci', 'workflow_dispatch' ] ).status, 2 );
} );

test( 'a missing binary fails and says how to install it', () => {
	const dir = repo();
	const { status, output } = scan( dir, [ 'ci', 'workflow_dispatch' ], {
		script: fakeInstall( null ),
	} );

	assert.equal( status, 2 );
	assert.match( output, /secret-scan\.sh install/ );
} );

test( 'a binary of the wrong version fails', () => {
	const dir = repo();
	const script = fakeInstall( '#!/bin/sh\necho 0.0.1\n' );

	assert.equal(
		scan( dir, [ 'ci', 'workflow_dispatch' ], { script } ).status,
		2
	);
} );

test( 'a scanner error is not reported as clean', () => {
	const dir = repo();
	const script = fakeInstall(
		'#!/bin/sh\n[ "$1" = version ] && echo 1.9.0 && exit 0\nexit 1\n'
	);

	const { status, output } = scan( dir, [ 'ci', 'workflow_dispatch' ], {
		script,
	} );

	assert.equal( status, 2 );
	assert.match( output, /did not complete/ );
} );

test( 'the scanner runs redacted, with validation and report files off', () => {
	const dir = repo();
	const argsFile = join( scratch, `args-${ ++repoCount }.txt` );
	const script = fakeInstall(
		'#!/bin/sh\n[ "$1" = version ] && echo 1.9.0 && exit 0\nprintf "%s\\n" "$@" > "$ARGS_FILE"\n'
	);

	const { status } = scan( dir, [ 'ci', 'workflow_dispatch' ], {
		script,
		extraEnv: { ARGS_FILE: argsFile },
	} );
	const args = readFileSync( argsFile, 'utf8' ).split( '\n' );

	assert.equal( status, 0 );
	for ( const flag of [
		'--redact',
		'--ignore-gitleaks-allow',
		'--no-color',
	] ) {
		assert.ok( args.includes( flag ), `missing ${ flag }` );
	}
	assert.ok( args.some( ( arg ) => arg.startsWith( '--log-opts=--text ' ) ) );
	for ( const arg of args ) {
		assert.doesNotMatch(
			arg,
			/^(--validation|--report-path|-r$|--baseline-path|-b$)/
		);
	}
} );
