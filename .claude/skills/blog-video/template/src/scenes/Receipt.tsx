import React from 'react';
import { useCurrentFrame } from 'remotion';
import { color, font, L, radius } from '../theme';
import { reveal, stagger } from '../motion';
import type { Visual } from '../types';

export const Receipt: React.FC<{ visual: Extract<Visual, { type: 'receipt' }>; frames: number }> = ({ visual, frames }) => {
	const frame = useCurrentFrame();
	const n = visual.output.length + 1;
	return (
		<div style={{ height: '100%', display: 'flex', alignItems: 'center' }}>
			<div style={{ width: '100%', background: color['code-paper'], border: `2px solid ${color.line}`, borderRadius: radius.lg, fontFamily: font.mono, fontSize: Math.round(L.mono * 0.94), lineHeight: 1.55, padding: '28px 36px' }}>
				<div style={{ color: color.ink, whiteSpace: 'pre' }}>
					<span style={{ color: color.dust }}>$ </span>
					{visual.command}
				</div>
				{visual.output.map((line, i) => (
					<div key={i} style={{ color: color.graphite, whiteSpace: 'pre', opacity: reveal(frame, stagger(i, n, frames)) }}>
						{line}
					</div>
				))}
				<div
					style={{
						margin: '16px -36px -28px',
						padding: '16px 36px',
						background: color['signal-soft'],
						color: color['signal-deep'],
						fontWeight: 600,
						whiteSpace: 'pre',
						borderRadius: `0 0 ${radius.lg}px ${radius.lg}px`,
						opacity: reveal(frame, stagger(n - 1, n, frames)),
					}}
				>
					{visual.answer}
				</div>
			</div>
		</div>
	);
};
