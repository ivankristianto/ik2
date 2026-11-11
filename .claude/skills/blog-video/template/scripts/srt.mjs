// Write out/video.srt from the hook, the cues and the pointer in src/video.json, timed to the rendered MP4.
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';

const video = JSON.parse(readFileSync('src/video.json', 'utf8'));
const stamp = (s) => {
	const ms = Math.round(s * 1000);
	const p = (n, w = 2) => String(n).padStart(w, '0');
	return `${p(Math.floor(ms / 3600000))}:${p(Math.floor(ms / 60000) % 60)}:${p(Math.floor(ms / 1000) % 60)},${p(ms % 1000, 3)}`;
};

let offset = video.introFrames / video.fps;
let n = 0;
const out = [];
const cue = (start, end, text) => out.push(`${++n}\n${stamp(start)} --> ${stamp(end)}\n${text}\n`);
const { hook, pointer } = video.post;
if (hook?.end !== undefined) cue(hook.start, hook.end, hook.text);
for (const scene of video.scenes) {
	for (const c of scene.captions) {
		if (c.start === undefined || c.end === undefined) throw new Error(`${scene.id}: untimed caption, run npm run narrate first`);
		cue(offset + c.start, offset + c.end, c.text);
	}
	offset += (scene.frames ?? 150) / video.fps;
}
if (pointer?.end !== undefined) cue(offset + pointer.start, offset + pointer.end, pointer.text);
mkdirSync('out', { recursive: true });
writeFileSync('out/video.srt', out.join('\n'));
console.log(`out/video.srt: ${n} cues`);
