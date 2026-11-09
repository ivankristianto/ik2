import React from 'react';
import { useCurrentFrame } from 'remotion';
import { Page, Sig } from './Brand';
import { color, font, L } from './theme';
import { reveal } from './motion';

export const Outro: React.FC<{ slug: string; status: 'draft' | 'published' }> = ({ slug, status }) => {
	const frame = useCurrentFrame();
	const live = status === 'published';
	const width = L.W - L.left - L.right;
	const path = L.vertical ? `${slug}/` : `ivankristianto.com/${slug}/`;
	// Shrink a long URL to fit rather than wrap it mid-word; a mono glyph is about 0.6em wide.
	const urlSize = Math.min(36, Math.floor(width / (path.length * 0.6)));
	return (
		<Page opacity={reveal(frame, 0, 10)}>
			<div style={{ position: 'absolute', left: L.left, right: L.right, top: L.top, bottom: L.H - L.safeBottom, display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 48 }}>
				<Sig size={L.vertical ? 50 : 56} />
				<div style={{ fontSize: 44, fontWeight: 600, letterSpacing: '-0.01em', color: color.graphite, opacity: reveal(frame, 12) }}>
					{live ? 'Read the full article' : 'Full article coming soon'}
				</div>
				{live && (
					<div style={{ fontFamily: font.mono, fontSize: urlSize, color: color.signal, textAlign: 'center', whiteSpace: 'nowrap', opacity: reveal(frame, 18) }}>
						{L.vertical && <div>ivankristianto.com/</div>}
						{path}
					</div>
				)}
			</div>
		</Page>
	);
};
