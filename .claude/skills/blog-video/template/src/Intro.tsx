import React from 'react';
import { Audio, Sequence, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { Page, Rich, Sig, punchStyle } from './Brand';
import { L } from './theme';
import { fadeOut, reveal } from './motion';
import type { Spoken } from './types';

export const Intro: React.FC<{ punch: string; hook?: Spoken }> = ({ punch, hook }) => {
	const frame = useCurrentFrame();
	const { durationInFrames, fps } = useVideoConfig();
	const voiceAt = Math.round((hook?.start ?? 0) * fps);
	// The green half arrives as the voice reaches it, about halfway through the spoken hook.
	const emAt = hook?.end !== undefined ? Math.round((hook.start! + (hook.end - hook.start!) * 0.45) * fps) : 16;
	return (
		<Page opacity={fadeOut(frame, durationInFrames)}>
			{hook?.audio && (
				<Sequence from={voiceAt}>
					<Audio src={staticFile(hook.audio)} />
				</Sequence>
			)}
			<div style={{ position: 'absolute', left: L.left, right: L.right, top: L.top, bottom: L.sigBottom + 140, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
				<h1 style={{ ...punchStyle(L.punch), opacity: reveal(frame, 0) }}>
					<Rich text={punch} emOpacity={reveal(frame, emAt)} />
				</h1>
			</div>
			<Sig frame={frame} style={{ position: 'absolute', left: L.left, bottom: L.sigBottom }} />
		</Page>
	);
};
