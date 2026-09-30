#!/usr/bin/env node
/**
 * Cut a release: bump package.json, commit, tag vX.Y.Z, push, create the GitHub release.
 * Usage: pnpm release <patch|minor|major|X.Y.Z> [--dry-run]
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';

const PACKAGE_FILE = 'package.json';
const SEMVER = /^(\d+)\.(\d+)\.(\d+)$/;

function run( cmd, args, { allowFail = false } = {} ) {
	try {
		return execFileSync( cmd, args, {
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		} ).trim();
	} catch ( error ) {
		if ( allowFail ) {
			return null;
		}
		fail( `\`${ cmd } ${ args.join( ' ' ) }\` failed:\n${ error.stderr }` );
	}
}

function fail( message ) {
	process.stderr.write( `release: ${ message }\n` );
	process.exit( 1 );
}

function nextVersion( current, bump ) {
	if ( SEMVER.test( bump ) ) {
		return bump;
	}
	if ( ! [ 'major', 'minor', 'patch' ].includes( bump ) ) {
		fail( 'usage: pnpm release <patch|minor|major|X.Y.Z> [--dry-run]' );
	}
	const [ , major, minor, patch ] = current.match( SEMVER ).map( Number );
	const bumped = {
		major: `${ major + 1 }.0.0`,
		minor: `${ major }.${ minor + 1 }.0`,
		patch: `${ major }.${ minor }.${ patch + 1 }`,
	};
	return bumped[ bump ];
}

function isNewer( candidate, current ) {
	const a = candidate.match( SEMVER ).slice( 1 ).map( Number );
	const b = current.match( SEMVER ).slice( 1 ).map( Number );
	for ( let i = 0; i < 3; i++ ) {
		if ( a[ i ] !== b[ i ] ) {
			return a[ i ] > b[ i ];
		}
	}
	return false;
}

function assertReleasable( tag ) {
	if ( run( 'git', [ 'branch', '--show-current' ] ) !== 'main' ) {
		fail( 'releases are cut from main. Switch to main first.' );
	}
	if ( run( 'git', [ 'status', '--porcelain' ] ) !== '' ) {
		fail( 'working tree is not clean. Commit or stash first.' );
	}
	run( 'gh', [ 'auth', 'status' ] );
	run( 'git', [ 'fetch', '--quiet', '--tags', 'origin', 'main' ] );
	if (
		run( 'git', [ 'rev-parse', 'HEAD' ] ) !==
		run( 'git', [ 'rev-parse', 'origin/main' ] )
	) {
		fail( 'local main differs from origin/main. Pull or push first.' );
	}
	if (
		run( 'git', [ 'rev-parse', '--verify', '--quiet', tag ], {
			allowFail: true,
		} )
	) {
		fail( `tag ${ tag } already exists.` );
	}
	assertQualityPassed();
}

// Refuse to ship a commit that failed the lint gates on CI.
function assertQualityPassed() {
	const sha = run( 'git', [ 'rev-parse', 'HEAD' ] );
	const conclusion = run( 'gh', [
		'run',
		'list',
		'--workflow',
		'Quality',
		'--commit',
		sha,
		'--limit',
		'1',
		'--json',
		'status,conclusion',
		'--jq',
		'.[0] | if . == null then "none" elif .status != "completed" then "pending" else .conclusion end',
	] );
	if ( conclusion === 'success' ) {
		return;
	}
	if ( conclusion === 'none' ) {
		process.stdout.write(
			'warning: no Quality run found for HEAD, releasing anyway.\n'
		);
		return;
	}
	fail(
		`Quality workflow for HEAD is "${ conclusion }". Wait for it to pass, then retry.`
	);
}

function writeVersion( version ) {
	const source = readFileSync( PACKAGE_FILE, 'utf8' );
	// Regex instead of JSON round-trip so the file's tab indentation survives.
	writeFileSync(
		PACKAGE_FILE,
		source.replace( /"version": "[^"]+"/, `"version": "${ version }"` )
	);
}

function main() {
	const args = process.argv.slice( 2 );
	const dryRun = args.includes( '--dry-run' );
	const bump = args.find( ( arg ) => ! arg.startsWith( '--' ) );

	const current = JSON.parse( readFileSync( PACKAGE_FILE, 'utf8' ) ).version;
	const version = nextVersion( current, bump );
	const tag = `v${ version }`;

	if ( ! isNewer( version, current ) ) {
		fail( `${ version } is not newer than the current ${ current }.` );
	}
	assertReleasable( tag );

	if ( dryRun ) {
		process.stdout.write(
			`dry run: would release ${ current } -> ${ tag } from ${ run(
				'git',
				[ 'rev-parse', '--short', 'HEAD' ]
			) }\n`
		);
		return;
	}

	writeVersion( version );
	run( 'git', [ 'add', PACKAGE_FILE ] );
	run( 'git', [ 'commit', '-m', `chore(release): ${ tag }` ] );
	run( 'git', [ 'tag', '-a', tag, '-m', tag ] );
	run( 'git', [ 'push', '--atomic', 'origin', 'main', tag ] );
	run( 'gh', [
		'release',
		'create',
		tag,
		'--verify-tag',
		'--generate-notes',
		'--title',
		tag,
	] );

	const repo = run( 'gh', [
		'repo',
		'view',
		'--json',
		'url',
		'--jq',
		'.url',
	] );
	process.stdout.write(
		`Released ${ tag }.\n` +
			`Images build and Dokploy redeploys from: ${ repo }/actions/workflows/build.yml\n`
	);
}

main();
