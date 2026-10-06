/**
 * Which article images the lightbox is worth opening for. Pure, so the rules
 * run in node:test without a DOM; lightbox-loader.js feeds it measurements.
 */

// Below a 20% gain the lightbox shows nearly the picture already on the page.
const MIN_GAIN = 1.2;

// WordPress names resized copies `name-1024x576.ext` beside the original.
const SIZE_SUFFIX = /-\d+x\d+(?=\.\w+$)/;

// Mirrors the room _lightbox.scss leaves around the image: side buttons, top bar, caption.
const CHROME_X = 192;
const CHROME_Y = 160;
const SMALL_SCREEN = 600;

/**
 * The lightbox's image area for a viewport. Unbounded on small screens, where
 * the lightbox is for viewing and swiping images one at a time, not for size.
 *
 * @param {Object} viewport Viewport size, in CSS px.
 * @return {Object} Stage width and height.
 */
export function stageFor( viewport ) {
	if ( Math.min( viewport.width, viewport.height ) < SMALL_SCREEN ) {
		return { width: Infinity, height: Infinity };
	}
	return {
		width: viewport.width - CHROME_X,
		height: viewport.height - CHROME_Y,
	};
}

function linksToOwnFile( image ) {
	return (
		image.href.replace( SIZE_SUFFIX, '' ) ===
		image.src.replace( SIZE_SUFFIX, '' )
	);
}

/**
 * @param {Object} image Measurements of an article image.
 * @param {Object} stage The lightbox's image area, in CSS px.
 * @return {boolean} Whether the lightbox would show the image bigger.
 */
export function isEnlargeable( image, stage ) {
	if ( ! image.inFigure || ( image.href && ! linksToOwnFile( image ) ) ) {
		return false;
	}
	const scale = Math.min(
		1,
		stage.width / image.naturalWidth,
		stage.height / image.naturalHeight
	);
	return image.naturalWidth * scale >= image.renderedWidth * MIN_GAIN;
}
