import React from 'react';
import { useCurrentFrame, useVideoConfig } from 'remotion';
import { Page, Rich, Sig, punchStyle } from './Brand';
import { L } from './theme';
import { fadeOut, reveal } from './motion';

export const Intro: React.FC<{ punch: string }> = ({ punch }) => {
	const frame = useCurrentFrame();
	const { durationInFrames } = useVideoConfig();
	return (
		<Page opacity={fadeOut(frame, durationInFrames)}>
			<div style={{ position: 'absolute', left: L.left, right: L.right, top: L.top, bottom: L.sigBottom + 140, display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
				<h1 style={{ ...punchStyle(L.punch), opacity: reveal(frame, 0) }}>
					<Rich text={punch} emOpacity={reveal(frame, 16)} />
				</h1>
			</div>
			<Sig style={{ position: 'absolute', left: L.left, bottom: L.sigBottom }} />
		</Page>
	);
};
