import assert from 'node:assert/strict';
import { test } from 'node:test';

import { DIMS, MODEL_NAME, inputFor, maxTokensFor, prefixFor } from '../src/levels.mjs';

test( 'documents use the clustering prefix and a 1024 token cap', () => {
	assert.equal( prefixFor( 'document' ), 'clustering: ' );
	assert.equal( maxTokensFor( 'document' ), 1024 );
} );

test( 'chunks use the search_document prefix and a 512 token cap', () => {
	assert.equal( prefixFor( 'chunk' ), 'search_document: ' );
	assert.equal( maxTokensFor( 'chunk' ), 512 );
} );

test( 'inputFor prepends the prefix', () => {
	assert.equal( inputFor( 'chunk', 'Title > Heading' ), 'search_document: Title > Heading' );
	assert.equal( inputFor( 'document', 'Title' ), 'clustering: Title' );
} );

test( 'an unknown level throws', () => {
	assert.throws( () => prefixFor( 'page' ), /Unknown vector level/ );
} );

test( 'model name and dims match the stored columns', () => {
	assert.equal( MODEL_NAME, 'nomic-ai/nomic-embed-text-v1.5:q8' );
	assert.equal( DIMS, 768 );
} );
