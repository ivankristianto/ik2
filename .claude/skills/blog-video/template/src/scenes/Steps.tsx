import React from 'react';
import { useCurrentFrame, useVideoConfig } from 'remotion';
import { color, font, L, radius } from '../theme';
import { reveal, stagger } from '../motion';
import type { StepState, Visual } from '../types';

// Horizontal runs left to right; vertical stacks top to bottom because four boxes don't fit across 810px.
const GAP = L.vertical ? 56 : 90;

const box: Record<StepState, React.CSSProperties> = {
	normal: { border: `3px solid ${color.ink}`, background: color.surface, color: color.ink },
	skip: { border: `3px dashed ${color.red}`, background: color.paper, color: color.dust },
	break: { border: `3px solid ${color.red}`, background: color.surface, color: color.red },
	end: { border: `3px solid ${color.signal}`, background: color['signal-soft'], color: color['signal-deep'], fontWeight: 600 },
};

const noteColor: Record<StepState, string> = { normal: color.graphite, skip: color.red, break: color.red, end: color.signal };

const Arrow: React.FC<{ progress: number }> = ({ progress }) => {
	const head = progress >= 1 ? 1 : 0;
	if (L.vertical) {
		return (
			<svg width={24} height={GAP} viewBox={`0 0 24 ${GAP}`} style={{ flex: 'none', alignSelf: 'center' }} aria-hidden>
				<line x1={12} y1={6} x2={12} y2={GAP - 14} stroke={color.ink} strokeWidth={3} pathLength={1} strokeDasharray={1} strokeDashoffset={1 - progress} />
				<path d={`M4 ${GAP - 20} L12 ${GAP - 6} L20 ${GAP - 20} z`} fill={color.ink} opacity={head} />
			</svg>
		);
	}
	return (
		<svg width={GAP} height={24} viewBox={`0 0 ${GAP} 24`} style={{ flex: 'none', marginTop: 48 }} aria-hidden>
			<line x1={10} y1={12} x2={GAP - 14} y2={12} stroke={color.ink} strokeWidth={3} pathLength={1} strokeDasharray={1} strokeDashoffset={1 - progress} />
			<path d={`M${GAP - 22} 4 L${GAP - 8} 12 L${GAP - 22} 20 z`} fill={color.ink} opacity={head} />
		</svg>
	);
};

export const Steps: React.FC<{ visual: Extract<Visual, { type: 'steps' }>; frames: number }> = ({ visual, frames }) => {
	const frame = useCurrentFrame();
	const { fps } = useVideoConfig();
	const n = visual.steps.length;
	return (
		<div style={{ height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
			<div style={{ display: 'flex', flexDirection: L.vertical ? 'column' : 'row', width: '100%' }}>
				{visual.steps.map((s, i) => {
					const state = s.state ?? 'normal';
					const at = s.at !== undefined ? Math.round(s.at * fps) : stagger(i, n, frames);
					return (
						<React.Fragment key={i}>
							{i > 0 && <Arrow progress={reveal(frame, at - 10, 10)} />}
							<div style={{ flex: L.vertical ? 'none' : 1, minWidth: 0, opacity: reveal(frame, at) }}>
								<div
									style={{
										height: L.vertical ? 96 : 120,
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'center',
										borderRadius: radius.md,
										fontFamily: font.mono,
										fontSize: 34,
										whiteSpace: 'nowrap',
										...box[state],
									}}
								>
									<span style={state === 'skip' ? { textDecoration: `line-through 3px ${color.red}` } : undefined}>{s.label}</span>
								</div>
								{s.note && (
									<div style={{ marginTop: L.vertical ? 10 : 24, textAlign: 'center', fontFamily: font.mono, fontSize: L.vertical ? 26 : 28, color: noteColor[state], whiteSpace: 'nowrap' }}>
										{s.note}
									</div>
								)}
							</div>
						</React.Fragment>
					);
				})}
			</div>
		</div>
	);
};
