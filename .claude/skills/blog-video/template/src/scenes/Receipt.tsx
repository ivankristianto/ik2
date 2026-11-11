import React from 'react';
import { useCurrentFrame, useVideoConfig } from 'remotion';
import { color, font, L, radius } from '../theme';
import { reveal, stagger, timing } from '../motion';
import type { Cue, Visual } from '../types';

// Focus keys: 0 is the command, 1..n the output lines, n + 1 the answer row.
export const Receipt: React.FC<{ visual: Extract<Visual, { type: 'receipt' }>; cues: Cue[]; frames: number }> = ({ visual, cues, frames }) => {
	const frame = useCurrentFrame();
	const { fps } = useVideoConfig();
	const n = visual.output.length + 1;
	const cmd = timing(cues, 0, fps);
	const typed = cmd.at === undefined ? visual.command.length : Math.max(0, (frame - cmd.at) * 2);
	const answer = timing(cues, n, fps);
	return (
		<div style={{ height: '100%', display: 'flex', alignItems: 'center' }}>
			<div style={{ width: '100%', background: color['code-paper'], border: `2px solid ${color.line}`, borderRadius: radius.lg, fontFamily: font.mono, fontSize: Math.round(L.mono * 0.94), lineHeight: 1.55, padding: '28px 36px' }}>
				<div style={{ color: cmd.lit(frame) ? color['signal-deep'] : color.ink, whiteSpace: 'pre' }}>
					<span style={{ color: color.dust }}>$ </span>
					{visual.command.slice(0, typed)}
					<span style={{ opacity: 0 }}>{visual.command.slice(typed)}</span>
				</div>
				{visual.output.map((line, i) => {
					const t = timing(cues, i + 1, fps);
					return (
						<div key={i} style={{ color: t.lit(frame) ? color['signal-deep'] : color.graphite, whiteSpace: 'pre', opacity: reveal(frame, t.at ?? stagger(i, n, frames)) }}>
							{line}
						</div>
					);
				})}
				<div
					style={{
						margin: '16px -36px -28px',
						padding: '16px 36px',
						background: color['signal-soft'],
						color: color['signal-deep'],
						fontWeight: 600,
						whiteSpace: 'pre',
						borderRadius: `0 0 ${radius.lg}px ${radius.lg}px`,
						opacity: reveal(frame, answer.at ?? stagger(n - 1, n, frames)),
					}}
				>
					{visual.answer}
				</div>
			</div>
		</div>
	);
};
