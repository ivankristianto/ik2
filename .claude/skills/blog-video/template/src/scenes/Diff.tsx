import React from 'react';
import { useCurrentFrame, useVideoConfig } from 'remotion';
import { color, font, L, radius } from '../theme';
import { reveal, stagger, timing } from '../motion';
import type { Cue, Visual } from '../types';

export const Diff: React.FC<{ visual: Extract<Visual, { type: 'diff' }>; cues: Cue[]; frames: number }> = ({ visual, cues, frames }) => {
	const frame = useCurrentFrame();
	const { fps } = useVideoConfig();
	const changes = visual.lines.filter((l) => l.kind === 'del' || l.kind === 'add').length + (visual.result ? 1 : 0);
	let k = 0;
	return (
		<div style={{ height: '100%', display: 'flex', alignItems: 'center' }}>
			<div style={{ width: '100%', background: color['code-paper'], border: `2px solid ${color.line}`, borderRadius: radius.lg, fontFamily: font.mono }}>
				<div style={{ padding: '18px 32px', borderBottom: `2px solid ${color.line}`, fontSize: Math.round(L.mono * 0.78), color: color.graphite }}>{visual.file}</div>
				<div style={{ padding: '24px 32px', fontSize: L.mono, lineHeight: 1.6 }}>
					{visual.lines.map((l, i) => {
						const kind = l.kind ?? 'ctx';
						const t = timing(cues, i, fps);
						const at = l.at !== undefined ? Math.round(l.at * fps) : (t.at ?? stagger(k, changes, frames));
						if (kind !== 'ctx') k++;
						const p = kind === 'ctx' ? 1 : reveal(frame, at);
						if (kind === 'del') {
							return (
								<div key={i} style={{ whiteSpace: 'pre', color: p > 0.5 ? color.red : color.ink, textDecoration: p > 0.5 ? `line-through 3px ${color.red}` : 'none' }}>
									{l.text}
								</div>
							);
						}
						if (kind === 'add') {
							return (
								<div key={i} style={{ whiteSpace: 'pre', opacity: p, color: color['signal-deep'], background: color['signal-soft'], margin: '0 -32px', padding: '0 32px' }}>
									{l.text}
								</div>
							);
						}
						return (
							<div key={i} style={{ whiteSpace: 'pre', color: t.lit(frame) ? color.signal : color.graphite }}>
								{l.text}
							</div>
						);
					})}
				</div>
				{visual.result && (
					<div style={{ padding: '18px 32px', borderTop: `2px solid ${color.line}`, fontSize: Math.round(L.mono * 0.89), fontWeight: 600, color: color.signal, opacity: reveal(frame, stagger(k, changes, frames)) }}>
						{visual.result}
					</div>
				)}
			</div>
		</div>
	);
};
