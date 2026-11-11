import React from 'react';
import { AbsoluteFill } from 'remotion';
import { color, font, L } from './theme';
import { reveal } from './motion';

export const Page: React.FC<{ children: React.ReactNode; opacity?: number }> = ({ children, opacity = 1 }) => (
	<AbsoluteFill style={{ background: color.paper }}>
		<AbsoluteFill style={{ color: color.ink, fontFamily: font.sans, WebkitFontSmoothing: 'antialiased', opacity }}>
			{children}
		</AbsoluteFill>
	</AbsoluteFill>
);

const DOMAIN = 'ivankristianto.com';
const typed = (frame: number, start: number, length: number) => Math.max(0, Math.min(length, (frame - start) * 2));

/** Frames the signature animation needs from its first frame, with an optional path typed after the domain. */
export const sigFrames = (path = '') => SIG.path + Math.ceil(path.length / 2);
const SIG = { ik: 12, dot: 20, domain: 24, path: 24 + DOMAIN.length / 2 + 4 };

const Typed: React.FC<{ text: string; shown: number }> = ({ text, shown }) => (
	<>
		{text.slice(0, shown)}
		<span style={{ opacity: 0 }}>{text.slice(shown)}</span>
	</>
);

/**
 * The `ik.` badge and domain. With `frame` it plays the standard signature: the outline draws, fills green,
 * the dot blinks once like a cursor, the domain types out, then `path` types beneath it. Without, it's static.
 */
export const Sig: React.FC<{ size?: number; text?: string; path?: string; frame?: number; style?: React.CSSProperties }> = ({
	size = 34,
	text = DOMAIN,
	path,
	frame,
	style,
}) => {
	const f = frame ?? Infinity;
	const box = size * 2.1;
	const stroke = Math.max(2, size * 0.09);
	const blinkOff = f >= SIG.dot + 5 && f < SIG.dot + 9;
	const width = (L.W - L.left - L.right) / 0.6;
	const pathSize = path ? Math.min(size, Math.floor(width / path.length)) : size;
	return (
		<div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: size * 0.5, fontFamily: font.mono, fontWeight: 600, ...style }}>
			<div style={{ display: 'flex', alignItems: 'center', gap: size * 0.66, fontSize: size }}>
				<span style={{ position: 'relative', display: 'inline-grid', placeItems: 'center', width: box, height: box, flex: 'none' }}>
					<svg width={box} height={box} style={{ position: 'absolute', inset: 0 }} aria-hidden>
						<rect
							x={stroke / 2}
							y={stroke / 2}
							width={box - stroke}
							height={box - stroke}
							rx={size * 0.48 - stroke / 2}
							fill={color.signal}
							fillOpacity={reveal(f, 8, 10)}
							stroke={color.signal}
							strokeWidth={stroke}
							pathLength={1}
							strokeDasharray={1}
							strokeDashoffset={1 - reveal(f, 0, 14)}
						/>
					</svg>
					<span style={{ position: 'relative', color: color.paper, fontFamily: font.sans, fontWeight: 700, fontSize: size * 0.95, letterSpacing: '-0.04em' }}>
						<span style={{ opacity: reveal(f, SIG.ik, 8) }}>ik</span>
						<span style={{ opacity: f >= SIG.dot && !blinkOff ? 1 : 0 }}>.</span>
					</span>
				</span>
				{text && (
					<span>
						<Typed text={text} shown={typed(f, SIG.domain, text.length)} />
					</span>
				)}
			</div>
			{path && (
				<div style={{ fontSize: pathSize, color: color.signal, whiteSpace: 'nowrap' }}>
					<Typed text={path} shown={typed(f, SIG.path, path.length)} />
				</div>
			)}
		</div>
	);
};

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
