import assert from 'node:assert/strict';
import test from 'node:test';

import {
	isFromOrigin,
	resizeHeight,
} from '../wp-content/plugins/ik2/src/lab-tool/rules.js';

const LAB = 'https://lab.ivankristianto.com';
const frame = { name: 'tool frame' };
const message = ( overrides = {} ) => ( {
	origin: LAB,
	source: frame,
	data: { type: 'ik2-lab:resize', height: 612.4 },
	...overrides,
} );

test( 'a resize from the tool frame on the lab origin is accepted and rounded up', () => {
	assert.equal( resizeHeight( message(), frame, LAB ), 613 );
} );

test( 'the same message from another window is ignored', () => {
	assert.equal( resizeHeight( message( { source: {} } ), frame, LAB ), null );
	assert.equal( resizeHeight( message(), null, LAB ), null );
} );

test( 'the same message from another origin is ignored', () => {
	for ( const origin of [
		'https://www.ivankristianto.com',
		'https://lab.ivankristianto.com.evil.test',
		'http://lab.ivankristianto.com',
		'https://lab.ivankristianto.com:8443',
		'null',
		'',
	] ) {
		assert.equal(
			resizeHeight( message( { origin } ), frame, LAB ),
			null,
			origin
		);
	}
} );

test( 'an explicit default port still matches', () => {
	assert.equal( isFromOrigin( { origin: `${ LAB }:443` }, LAB ), true );
} );

test( 'other message shapes are ignored', () => {
	for ( const data of [
		null,
		'ik2-lab:resize',
		{ type: 'resize', height: 400 },
		{ type: 'ik2-lab:resize' },
		{ type: 'ik2-lab:resize', height: '400' },
		{ type: 'ik2-lab:resize', height: Number.NaN },
		{ type: 'ik2-lab:resize', height: -5 },
	] ) {
		assert.equal( resizeHeight( message( { data } ), frame, LAB ), null );
	}
} );

test( 'heights are clamped to the allowed range', () => {
	const at = ( height ) =>
		resizeHeight(
			message( { data: { type: 'ik2-lab:resize', height } } ),
			frame,
			LAB
		);

	assert.equal( at( 20 ), 160 );
	assert.equal( at( 99999 ), 4000 );
} );
