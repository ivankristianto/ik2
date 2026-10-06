import assert from 'node:assert/strict';
import test from 'node:test';

import {
	isEnlargeable,
	stageFor,
} from '../wp-content/themes/ik2/src/lightbox-rules.js';

const desktop = stageFor( { width: 1440, height: 900 } );
const phone = stageFor( { width: 390, height: 844 } );

const image = ( overrides ) => ( {
	inFigure: true,
	naturalWidth: 1600,
	naturalHeight: 900,
	renderedWidth: 960,
	href: null,
	src: 'https://example.test/wp-content/uploads/2026/06/shot.png',
	...overrides,
} );

test( 'an image shown smaller than the lightbox can show it is enlargeable', () => {
	assert.equal( isEnlargeable( image(), desktop ), true );
} );

test( 'an image already near its full size is not enlargeable', () => {
	assert.equal(
		isEnlargeable( image( { naturalWidth: 1000, naturalHeight: 560 } ), desktop ),
		false
	);
} );

test( 'a tall screenshot the lightbox would shrink to fit the screen is not enlargeable', () => {
	assert.equal(
		isEnlargeable( image( { naturalWidth: 1400, naturalHeight: 3000 } ), desktop ),
		false
	);
} );

test( 'an image that failed to load is not enlargeable', () => {
	assert.equal(
		isEnlargeable( image( { naturalWidth: 0, naturalHeight: 0 } ), desktop ),
		false
	);
} );

test( 'an image inline in running text is not enlargeable', () => {
	assert.equal( isEnlargeable( image( { inFigure: false } ), desktop ), false );
} );

test( 'an image linked somewhere else keeps its link and is not enlargeable', () => {
	assert.equal(
		isEnlargeable(
			image( { href: 'https://example.test/?attachment_id=2976' } ),
			desktop
		),
		false
	);
} );

test( 'an image linked to its own full-size file is enlargeable', () => {
	assert.equal(
		isEnlargeable(
			image( {
				src: 'https://example.test/wp-content/uploads/2026/06/shot-1024x576.png',
				href: 'https://example.test/wp-content/uploads/2026/06/shot.png',
			} ),
			desktop
		),
		true
	);
} );

test( 'on a phone, a screenshot with more pixels than it is shown at is enlargeable', () => {
	assert.equal( isEnlargeable( image( { renderedWidth: 358 } ), phone ), true );
} );

test( 'on a phone, an image shown at its natural size is not enlargeable', () => {
	assert.equal(
		isEnlargeable(
			image( { naturalWidth: 358, naturalHeight: 200, renderedWidth: 358 } ),
			phone
		),
		false
	);
} );
