import { color, font } from './tokens';
import data from './video.json';

export { color, font };
export const radius = { sm: 4, md: 6, lg: 8 };

export type Layout = {
	vertical: boolean;
	W: number;
	H: number;
	/** Safe margins: nothing that matters goes outside left, right, top and safeBottom. */
	left: number;
	right: number;
	top: number;
	safeBottom: number;
	captionTop: number;
	punch: number;
	heading: number;
	caption: number;
	mono: number;
	token: number;
	sigBottom: number;
};

// Horizontal is the 1200x628 feature card scaled by 1.6. Vertical keeps clear of the Shorts overlays: the button
// column on the right and the title and channel name in the bottom ~440px.
const layouts: Record<'vertical' | 'horizontal', Layout> = {
	horizontal: { vertical: false, W: 1920, H: 1080, left: 100, right: 100, top: 96, safeBottom: 1080, captionTop: 850, punch: 96, heading: 68, caption: 50, mono: 36, token: 140, sigBottom: 84 },
	vertical: { vertical: true, W: 1080, H: 1920, left: 90, right: 180, top: 210, safeBottom: 1480, captionTop: 1230, punch: 96, heading: 64, caption: 52, mono: 30, token: 88, sigBottom: 470 },
};

export const L = layouts[(data as { orientation?: string }).orientation === 'horizontal' ? 'horizontal' : 'vertical'];
