/**
 * Which messages may resize a lab tool's frame. Pure, so node:test covers it
 * without a DOM; view.js feeds it the event and the frame's window.
 */

export const RESIZE_MESSAGE = 'ik2-lab:resize';

// Keep in step with MIN_HEIGHT / MAX_HEIGHT in inc/post-types/lab-rules.php.
const MIN_HEIGHT = 160;
const MAX_HEIGHT = 4000;

function originOf( candidate ) {
	try {
		return globalThis.Origin.from( candidate );
	} catch ( error ) {
		// Events built with `new MessageEvent()` carry only the origin string.
		if ( typeof candidate?.origin === 'string' ) {
			return globalThis.Origin.from( candidate.origin );
		}
		throw error;
	}
}

/**
 * Exact scheme, host and port match between a message and the lab origin.
 *
 * @param {MessageEvent|{origin: string}} event   Incoming message.
 * @param {string}                        trusted Lab origin.
 * @return {boolean} Whether the message came from the lab origin.
 */
export function isFromOrigin( event, trusted ) {
	try {
		if ( 'Origin' in globalThis ) {
			return originOf( event ).isSameOrigin(
				globalThis.Origin.from( trusted )
			);
		}

		const sender = new URL( event.origin );

		// An opaque origin serializes to "null" and must never equal anything.
		return (
			sender.origin !== 'null' &&
			sender.origin === new URL( trusted ).origin
		);
	} catch {
		return false;
	}
}

/**
 * The height a message asks for, or null when the message must be ignored.
 *
 * @param {MessageEvent|Object} event       Incoming message.
 * @param {Window|null}         frameWindow The tool frame's contentWindow.
 * @param {string}              labOrigin   Lab origin.
 * @return {number|null} Height in px, clamped.
 */
export function resizeHeight( event, frameWindow, labOrigin ) {
	if ( ! frameWindow || event.source !== frameWindow ) {
		return null;
	}

	if ( ! isFromOrigin( event, labOrigin ) ) {
		return null;
	}

	const data = event.data;

	if ( ! data || typeof data !== 'object' || data.type !== RESIZE_MESSAGE ) {
		return null;
	}

	if (
		typeof data.height !== 'number' ||
		! Number.isFinite( data.height ) ||
		data.height <= 0
	) {
		return null;
	}

	return Math.min( MAX_HEIGHT, Math.max( MIN_HEIGHT, Math.ceil( data.height ) ) );
}
