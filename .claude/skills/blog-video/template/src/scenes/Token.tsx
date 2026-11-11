import React from 'react';
import { useCurrentFrame, useVideoConfig } from 'remotion';
import { color, font, L } from '../theme';
import { reveal, timing } from '../motion';
import type { Cue, Visual } from '../types';

// About 0.6em per mono glyph. Horizontal keeps the token on one line: under ~20 characters at the default 140px,
// or set `size` (96 fits ~30). Vertical defaults to 88px and wraps after slashes.
const breakable = (s = '') => (L.vertical ? s.replace(/\//g, '/​') : s);

export const Token: React.FC<{ visual: Extract<Visual, { type: 'token' }>; cues: Cue[]; frames: number }> = ({ visual, cues, frames }) => {
	const frame = useCurrentFrame();
	const size = visual.size ?? L.token;
	const { fps } = useVideoConfig();
	// Focus keys: 0 is the mark, 1 the caption under the band.
	const markAt = timing(cues, 0, fps).at ?? Math.round(frames * 0.3);
	const captionAt = timing(cues, 1, fps).at ?? markAt + 6;
	return (
		<div style={{ height: '100%', display: 'flex', flexDirection: 'column', justifyContent: 'center', gap: 36 }}>
			<div
				style={{
					background: color['code-paper'],
					margin: `0 -${L.right}px 0 -${L.left}px`,
					padding: `48px ${L.right}px 48px ${L.left}px`,
					fontFamily: font.mono,
					fontSize: size,
					lineHeight: 1.15,
					letterSpacing: '-0.04em',
					whiteSpace: L.vertical ? 'pre-wrap' : 'pre',
					opacity: reveal(frame, 0),
				}}
			>
				{breakable(visual.before)}
				<span style={{ color: frame >= markAt ? color.signal : color.ink }}>{breakable(visual.mark)}</span>
				{breakable(visual.after)}
			</div>
			{visual.caption && (
				<div style={{ fontFamily: font.mono, fontSize: Math.round(L.mono * 0.94), color: color.graphite, opacity: reveal(frame, captionAt) }}>{visual.caption}</div>
			)}
		</div>
	);
};
