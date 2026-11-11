import React from 'react';
import { Composition, Sequence } from 'remotion';
import data from './video.json';
import type { Video as VideoData } from './types';
import { Intro } from './Intro';
import { Outro } from './Outro';
import { Scene } from './Scene';
import { L } from './theme';

const video = data as VideoData;
const sceneFrames = (i: number) => video.scenes[i].frames ?? 150;
const body = video.scenes.reduce((sum, _s, i) => sum + sceneFrames(i), 0);
export const totalFrames = video.introFrames + body + video.outroFrames;

const Full: React.FC = () => {
	let at = video.introFrames;
	return (
		<>
			<Sequence durationInFrames={video.introFrames}>
				<Intro punch={video.post.punch} hook={video.post.hook} />
			</Sequence>
			{video.scenes.map((s, i) => {
				const from = at;
				at += sceneFrames(i);
				return (
					<Sequence key={s.id} from={from} durationInFrames={sceneFrames(i)}>
						<Scene scene={s} />
					</Sequence>
				);
			})}
			<Sequence from={at} durationInFrames={video.outroFrames}>
				<Outro slug={video.post.slug} pointer={video.post.pointer} />
			</Sequence>
		</>
	);
};

const IntroOnly: React.FC = () => <Intro punch={video.post.punch} hook={video.post.hook} />;
const OutroOnly: React.FC = () => <Outro slug={video.post.slug} pointer={video.post.pointer} />;

export const Root: React.FC = () => (
	<>
		<Composition id="Video" component={Full} durationInFrames={totalFrames} fps={video.fps} width={L.W} height={L.H} />
		<Composition id="Intro" component={IntroOnly} durationInFrames={video.introFrames} fps={video.fps} width={L.W} height={L.H} />
		<Composition id="Outro" component={OutroOnly} durationInFrames={video.outroFrames} fps={video.fps} width={L.W} height={L.H} />
	</>
);
