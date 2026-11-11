import React from 'react';
import { useCurrentFrame, useVideoConfig } from 'remotion';
import { color, font, L, radius } from '../theme';
import { reveal, stagger } from '../motion';
import type { Visual } from '../types';

const BAR = 18;

export const Rank: React.FC<{ visual: Extract<Visual, { type: 'rank' }>; frames: number }> = ({ visual, frames }) => {
	const frame = useCurrentFrame();
	const { fps } = useVideoConfig();
	const n = visual.rows.length;
	const best = Math.max(...visual.rows.map((r) => r.score));
	const winnerAt = visual.winnerAt !== undefined ? Math.round(visual.winnerAt * fps) : Math.round(frames * 0.75);
	const cut = visual.threshold;
	return (
		<div style={{ height: '100%', display: 'flex', flexDirection: 'column', justifyContent: 'center', gap: L.vertical ? 44 : 22, fontFamily: font.mono, fontSize: L.mono }}>
			{visual.rows.map((r, i) => {
				const at = r.at !== undefined ? Math.round(r.at * fps) : stagger(i, n, frames * 0.8);
				const dropped = cut !== undefined && r.score < cut;
				const won = r.score === best && !dropped && frame >= winnerAt;
				const settled = frame >= winnerAt;
				const ink = won ? color['signal-deep'] : dropped && settled ? color.dust : color.ink;
				return (
					<div key={i} style={{ opacity: reveal(frame, at) }}>
						<div style={{ display: 'flex', justifyContent: 'space-between', gap: 24, color: ink, fontWeight: won ? 600 : 400, marginBottom: 10 }}>
							<span style={{ whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis', textDecoration: dropped && settled ? `line-through 2px ${color.dust}` : undefined }}>{r.label}</span>
							<span>{r.score}</span>
						</div>
						<div style={{ position: 'relative', height: BAR, background: color['code-paper'], borderRadius: radius.sm }}>
							<div style={{ width: `${r.score * 100 * reveal(frame, at + 4, 18)}%`, height: '100%', borderRadius: radius.sm, background: won ? color.signal : dropped && settled ? color.line : color.graphite }} />
							{cut !== undefined && <div style={{ position: 'absolute', left: `${cut * 100}%`, top: -6, bottom: -6, borderLeft: `2px dashed ${color.red}` }} />}
						</div>
					</div>
				);
			})}
			{cut !== undefined && (
				<div style={{ position: 'relative', height: L.mono * 1.3, color: color.red, fontSize: Math.round(L.mono * 0.8), opacity: reveal(frame, 6) }}>
					<span style={{ position: 'absolute', left: `${cut * 100}%`, paddingLeft: 12, whiteSpace: 'nowrap' }}>cut-off {cut}</span>
				</div>
			)}
		</div>
	);
};
