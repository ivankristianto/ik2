import { Easing, interpolate } from 'remotion';
import type { Cue, Focus } from './types';

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

// Text appears this many frames after its sentence starts, so a new caption and new diagram text never land together.
export const LAG = 10;

/** When a visual's item appears and whether it is lit, from the captions whose `focus` names it. */
export const timing = (cues: Cue[], key: Focus, fps: number) => {
	const mine = cues.filter((c) => c.start !== undefined && [c.focus ?? []].flat().includes(key));
	const first = mine[0];
	let at: number | undefined;
	if (first) {
		const keys = [first.focus ?? []].flat();
		const span = ((first.spoken ?? first.end ?? first.start!) - first.start!) * 0.8;
		at = Math.round((first.start! + (keys.indexOf(key) * span) / keys.length) * fps) + LAG;
	}
	const lit = (frame: number) => mine.some((c) => frame >= c.start! * fps && frame < (c.spoken ?? c.end ?? 0) * fps);
	return { at, lit };
};
