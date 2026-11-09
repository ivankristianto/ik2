import React from 'react';
import { AbsoluteFill } from 'remotion';
import { color, font } from './theme';

export const Page: React.FC<{ children: React.ReactNode; opacity?: number }> = ({ children, opacity = 1 }) => (
	<AbsoluteFill style={{ background: color.paper }}>
		<AbsoluteFill style={{ color: color.ink, fontFamily: font.sans, WebkitFontSmoothing: 'antialiased', opacity }}>
			{children}
		</AbsoluteFill>
	</AbsoluteFill>
);

export const Sig: React.FC<{ size?: number; text?: string; style?: React.CSSProperties }> = ({
	size = 34,
	text = 'ivankristianto.com',
	style,
}) => (
	<div style={{ display: 'flex', alignItems: 'center', gap: size * 0.66, fontFamily: font.mono, fontSize: size, fontWeight: 600, ...style }}>
		<span
			style={{
				display: 'inline-grid',
				placeItems: 'center',
				width: size * 2.1,
				height: size * 2.1,
				borderRadius: size * 0.48,
				background: color.signal,
				color: color.paper,
				fontFamily: font.sans,
				fontWeight: 700,
				fontSize: size * 0.95,
				letterSpacing: '-0.04em',
			}}
		>
			ik.
		</span>
		{text}
	</div>
);

/** Renders "plain <em>accent</em>" with \n as line breaks, the same markup the feature card's .punch uses. */
export const Rich: React.FC<{ text: string; emOpacity?: number }> = ({ text, emOpacity = 1 }) => (
	<>
		{text.split('\n').map((line, i) => (
			<React.Fragment key={i}>
				{i > 0 && <br />}
				{line.split(/<em>(.*?)<\/em>/).map((part, j) =>
					j % 2 ? (
						<span key={j} style={{ color: color.signal, opacity: emOpacity }}>
							{part}
						</span>
					) : (
						part
					)
				)}
			</React.Fragment>
		))}
	</>
);

export const punchStyle = (size: number): React.CSSProperties => ({
	fontWeight: 700,
	letterSpacing: '-0.03em',
	lineHeight: 1.06,
	fontSize: size,
	textWrap: 'balance',
	margin: 0,
});
