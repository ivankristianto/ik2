import assert from 'node:assert/strict';
import { test } from 'node:test';

import { readConfig } from '../src/config.mjs';

const ENV = {
	CORPUS_DB_HOST: 'db',
	CORPUS_DB_USER: 'ik2_corpus',
	CORPUS_DB_PASSWORD: 'secret',
	CORPUS_DB_NAME: 'wordpress',
};

test( 'port defaults to 3306 and the prefix to wp_', () => {
	const config = readConfig( ENV );
	assert.equal( config.port, 3306 );
	assert.equal( config.tables.documents, 'wp_ik2_corpus_documents' );
	assert.equal( config.tables.runs, 'wp_ik2_corpus_runs' );
} );

test( 'port and prefix come from the environment', () => {
	const config = readConfig( { ...ENV, CORPUS_DB_PORT: '3307', CORPUS_TABLE_PREFIX: 'ik_' } );
	assert.equal( config.port, 3307 );
	assert.equal( config.tables.related, 'ik_ik2_corpus_related' );
} );

test( 'missing variables are named', () => {
	assert.throws( () => readConfig( { CORPUS_DB_HOST: 'db' } ), /CORPUS_DB_USER, CORPUS_DB_PASSWORD, CORPUS_DB_NAME/ );
} );

test( 'a prefix that could break out of an identifier is refused', () => {
	assert.throws( () => readConfig( { ...ENV, CORPUS_TABLE_PREFIX: 'wp_`; DROP' } ), /CORPUS_TABLE_PREFIX/ );
} );

test( 'a bad port is refused', () => {
	assert.throws( () => readConfig( { ...ENV, CORPUS_DB_PORT: 'abc' } ), /CORPUS_DB_PORT/ );
} );

test( 'a port in the host is split off, as WordPress allows in DB_HOST', () => {
	const config = readConfig( { ...ENV, CORPUS_DB_HOST: 'ik2-db:3307' } );
	assert.equal( config.host, 'ik2-db' );
	assert.equal( config.port, 3307 );
} );

test( 'a bracketed IPv6 host keeps its address and gives up its port', () => {
	const config = readConfig( { ...ENV, CORPUS_DB_HOST: '[::1]:3307' } );
	assert.equal( config.host, '::1' );
	assert.equal( config.port, 3307 );
} );

test( 'CORPUS_DB_PORT wins over a port in the host', () => {
	const config = readConfig( { ...ENV, CORPUS_DB_HOST: 'ik2-db:3307', CORPUS_DB_PORT: '3308' } );
	assert.equal( config.host, 'ik2-db' );
	assert.equal( config.port, 3308 );
} );
