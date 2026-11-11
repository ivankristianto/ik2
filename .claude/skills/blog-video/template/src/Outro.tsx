import React from 'react';
import { Audio, Sequence, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { Page, Sig, sigFrames } from './Brand';
import { color, L } from './theme';
import { reveal } from './motion';
import type { Spoken } from './types';

const START = 6;

export const Outro: React.FC<{ slug: string; pointer?: Spoken }> = ({ slug, pointer }) => {
	const frame = useCurrentFrame();
	const { fps } = useVideoConfig();
	const path = `/${slug}/`;
	return (
		<Page opacity={reveal(frame, 0, 10)}>
			{pointer?.audio && (
				<Sequence from={Math.round((pointer.start ?? 0) * fps)}>
					<Audio src={staticFile(pointer.audio)} />
				</Sequence>
			)}
			<div style={{ position: 'absolute', left: L.left, right: L.right, top: L.top, bottom: L.H - L.safeBottom, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 56 }}>
				<Sig size={L.vertical ? 50 : 56} path={path} frame={frame - START} />
				<div style={{ fontSize: 44, fontWeight: 600, letterSpacing: '-0.01em', color: color.graphite, opacity: reveal(frame, START + sigFrames(path)) }}>
					Read the full article
				</div>
			</div>
		</Page>
	);
};
