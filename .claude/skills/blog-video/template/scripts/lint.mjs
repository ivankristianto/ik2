// Check src/video.json against the script and pacing rules in SKILL.md. Errors stop the render; warnings don't.
import { readFileSync } from 'node:fs';

const video = JSON.parse(readFileSync('src/video.json', 'utf8'));
const { fps } = video;
const LAG = 10;
const errors = [];
const warnings = [];
const { hook, pointer } = video.post;

if (!hook?.text) errors.push('post.hook is missing: the intro needs a spoken hook');
else if (hook.start === undefined) errors.push('post.hook has no timing, run npm run narrate');
else if (hook.start * fps > 15) errors.push(`the hook starts at frame ${Math.round(hook.start * fps)}, later than 15`);
if (!pointer?.text) errors.push('post.pointer is missing: the outro needs a spoken pointer to the post');
else if (pointer.end !== undefined && video.outroFrames / fps - pointer.end > 1.5) errors.push('the pointer ends more than 1.5 s before the last frame');

const beats = video.scenes.flatMap((s) => s.captions.map((c) => c.beat));
if (beats.some((b) => !b)) errors.push('every caption needs a beat: and, but, mechanism or therefore');
if (beats.filter((b) => b === 'therefore').length !== 1) errors.push('the script needs exactly one therefore caption');
if (!beats.includes('but')) errors.push('the script needs a but caption');

let offset = video.introFrames / fps;
for (const scene of video.scenes) {
	if (scene.frames === undefined) {
		errors.push(`${scene.id}: not narrated`);
		continue;
	}
	if (scene.frames > 360) errors.push(`${scene.id}: ${(scene.frames / fps).toFixed(1)} s, over the 12 s cap. Split it`);
	const v = scene.visual;
	const events = (v.steps ?? v.rows ?? v.lines ?? []).filter((i) => i.at >= 0).map((i) => i.at);
	for (const cue of scene.captions) {
		const keys = [cue.focus ?? []].flat();
		if (cue.beat === 'but' && offset + cue.start >= 8) errors.push(`${scene.id}: the but caption starts at ${(offset + cue.start).toFixed(1)} s, not before 8 s`);
		if (!keys.length) errors.push(`${scene.id}: "${cue.text.slice(0, 40)}" has no focus, so nothing changes on screen as it's said`);
		const span = (cue.spoken - cue.start) * 0.8;
		keys.forEach((_k, i) => events.push(cue.start + (i * span) / keys.length + (i ? LAG / fps : 0)));
		for (const part of cue.chunks ?? []) if (part.text.length > 42) warnings.push(`${scene.id}: caption phrase over 42 characters: "${part.text}"`);
	}
	const times = [0, ...events.sort((a, b) => a - b), scene.frames / fps];
	times.forEach((t, i) => {
		if (i && t - times[i - 1] > 4) warnings.push(`${scene.id}: nothing changes on screen from ${times[i - 1].toFixed(1)} s to ${t.toFixed(1)} s`);
	});
	offset += scene.frames / fps;
}

const total = (video.introFrames + video.scenes.reduce((n, s) => n + (s.frames ?? 0), 0) + video.outroFrames) / fps;
if (total > 60) errors.push(`${total.toFixed(1)} s is over a minute. Cut words`);

for (const w of warnings) console.log(`warn   ${w}`);
for (const e of errors) console.error(`error  ${e}`);
if (errors.length) process.exit(1);
console.log(`lint passed, ${total.toFixed(1)} s, ${warnings.length} warning(s)`);
