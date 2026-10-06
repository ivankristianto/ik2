/**
 * Which article images the lightbox is worth opening for. Pure, so node:test
 * covers it without a DOM; lightbox-loader.js feeds it measurements.
 */

// Below a 20% gain the lightbox shows nearly the picture already on the page.
const MIN_GAIN = 1.2;

// Mirrors the room _lightbox.scss leaves around the image on wide screens.
const CHROME_X = 192;
const CHROME_Y = 160;
const SMALL_WIDTH = 640;
const SMALL_HEIGHT = 480;

// WordPress derives copies of an upload by suffix: `-1024x576` (resized),
// `-scaled` (big-image cap), `-e1717171717171` (edited in the media library).
const DERIVED_SUFFIX = /(?:-\d+x\d+|-scaled|-e\d{13})+(?=\.\w+$)/;

// Unbounded on small screens: there the lightbox is for one-at-a-time viewing and swiping, not size.
export function stageFor( viewport ) {
	if ( viewport.width < SMALL_WIDTH || viewport.height < SMALL_HEIGHT ) {
		return { width: Infinity, height: Infinity };
	}
	return {
		width: viewport.width - CHROME_X,
		height: viewport.height - CHROME_Y,
	};
}

// Browsers report a srcset image's naturalWidth per CSS px of its slot, not the file's.
export function largestSrcsetWidth( srcset ) {
	const widths = ( srcset || '' )
		.split( ',' )
		.map( ( candidate ) => candidate.trim().match( /\s(\d+)w$/ ) )
		.filter( Boolean )
		.map( ( match ) => Number( match[ 1 ] ) );
	return widths.length ? Math.max( ...widths ) : 0;
}

function linksToOwnFile( image ) {
	return (
		image.href.replace( DERIVED_SUFFIX, '' ) ===
		image.src.replace( DERIVED_SUFFIX, '' )
	);
}

export function isEnlargeable( image, stage ) {
	if ( ! image.inFigure || ! image.naturalWidth || ! image.renderedWidth ) {
		return false;
	}
	if ( image.href && ! linksToOwnFile( image ) ) {
		return false;
	}
	const scale = Math.min(
		1,
		stage.width / image.naturalWidth,
		stage.height / image.naturalHeight
	);
	return image.naturalWidth * scale >= image.renderedWidth * MIN_GAIN;
}
