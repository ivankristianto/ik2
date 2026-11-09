import { Easing, interpolate } from 'remotion';

// Opacity and line-draw only: the site bans scale, bounce and overshoot, so no spring().
export const reveal = (frame: number, start: number, duration = 12) =>
	interpolate(frame, [start, start + duration], [0, 1], {
		extrapolateLeft: 'clamp',
		extrapolateRight: 'clamp',
		easing: Easing.out(Easing.cubic),
	});

export const fadeOut = (frame: number, total: number, duration = 8) =>
	interpolate(frame, [total - duration, total], [1, 0], { extrapolateLeft: 'clamp', extrapolateRight: 'clamp' });

/** Frame at which item i of n appears, spread over the first 70% of the scene. */
export const stagger = (i: number, n: number, frames: number, lead = 12) =>
	lead + Math.round((i * (frames * 0.7 - lead)) / Math.max(n, 1));
