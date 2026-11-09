import React from 'react';
import { Audio, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { Page, Rich, Sig, punchStyle } from './Brand';
import { color, L } from './theme';
import { reveal } from './motion';
import type { Cue, Scene as SceneData } from './types';
import { Steps } from './scenes/Steps';
import { Diff } from './scenes/Diff';
import { Receipt } from './scenes/Receipt';
import { Token } from './scenes/Token';

const Caption: React.FC<{ cues: Cue[] }> = ({ cues }) => {
	const frame = useCurrentFrame();
	const { fps } = useVideoConfig();
	const t = frame / fps;
	const cue = cues.find((c) => c.start !== undefined && c.end !== undefined && t >= c.start && t < c.end);
	return (
		<div
			style={{
				position: 'absolute',
				left: L.left,
				right: L.right,
				top: L.captionTop,
				bottom: L.H - L.safeBottom,
				borderTop: `2px solid ${color.line}`,
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				paddingBottom: L.vertical ? 0 : 24,
			}}
		>
			{cue && (
				<p style={{ margin: 0, maxWidth: 1500, textAlign: 'center', fontSize: L.caption, lineHeight: 1.25, fontWeight: 500, textWrap: 'balance' }}>
					{cue.text}
				</p>
			)}
		</div>
	);
};

export const Scene: React.FC<{ scene: SceneData }> = ({ scene }) => {
	const frame = useCurrentFrame();
	const { durationInFrames } = useVideoConfig();
	const v = scene.visual;
	return (
		<Page>
			{scene.audio && <Audio src={staticFile(scene.audio)} />}
			<div style={{ position: 'absolute', left: L.left, right: L.right, top: L.top, bottom: L.H - L.captionTop + 40, display: 'flex', flexDirection: 'column', gap: 48 }}>
				<div style={{ display: 'flex', alignItems: 'flex-start', gap: 32 }}>
					{scene.heading && (
						<h2 style={{ ...punchStyle(L.heading), flex: 1, minWidth: 0, opacity: reveal(frame, 0) }}>
							<Rich text={scene.heading} />
						</h2>
					)}
					<Sig size={24} text="" style={{ marginLeft: 'auto', paddingTop: 4 }} />
				</div>
				<div style={{ flex: 1, minHeight: 0 }}>
					{v.type === 'steps' && <Steps visual={v} frames={durationInFrames} />}
					{v.type === 'diff' && <Diff visual={v} frames={durationInFrames} />}
					{v.type === 'receipt' && <Receipt visual={v} frames={durationInFrames} />}
					{v.type === 'token' && <Token visual={v} frames={durationInFrames} />}
				</div>
			</div>
			<Caption cues={scene.captions} />
		</Page>
	);
};
