// Speak every caption as its own Kokoro clip, join each scene's clips with a fixed pause, and write
// audio paths, frames and exact caption times back to src/video.json.
// One clip per caption so cue edges come from measured clip lengths, not from guessing at pauses.
// Usage: node scripts/narrate.mjs [scene-id ...]   Needs .claude/skills/blog-video/preflight.sh to have passed.
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { homedir } from 'node:os';
import { join } from 'node:path';

const HOME = process.env.BLOG_VIDEO_HOME ?? join(process.env.XDG_CACHE_HOME ?? join(homedir(), '.cache'), 'blog-video');
const PYTHON = join(HOME, 'venv/bin/python');
const GAP = 0.35;
const TAIL = 12;
const file = 'src/video.json';
const video = JSON.parse(readFileSync(file, 'utf8'));
const narrator = { voice: 'bm_george', lang: 'en-gb', speed: 1.0, ...video.narrator };
video.narrator = narrator;
const only = process.argv.slice(2);

const run = (cmd, args) => execFileSync(cmd, args, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
const duration = (f) => Number(JSON.parse(run('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'json', f])).format.duration);
const round = (n) => Number(n.toFixed(3));

if (!existsSync(PYTHON) || !existsSync(join(HOME, 'models/kokoro-v1.0.onnx'))) {
	console.error(`Kokoro is not set up in ${HOME}; run .claude/skills/blog-video/preflight.sh`);
	process.exit(1);
}
mkdirSync('narration', { recursive: true });
mkdirSync('public/audio', { recursive: true });
run('ffmpeg', ['-y', '-v', 'error', '-f', 'lavfi', '-i', 'anullsrc=r=48000:cl=mono', '-t', String(GAP), '-c:a', 'pcm_s16le', 'narration/gap.wav']);

const scenes = video.scenes.filter((s) => !only.length || only.includes(s.id));
const clip = (scene, i) => `narration/${scene.id}-${String(i + 1).padStart(2, '0')}`;

const jobs = scenes.flatMap((scene) =>
	scene.captions.map((cue, i) => {
		const text = (cue.say ?? cue.text).trim();
		writeFileSync(`${clip(scene, i)}.txt`, text + '\n');
		return { text, out: `${clip(scene, i)}.raw.wav`, ...narrator };
	})
);
writeFileSync('narration/jobs.json', JSON.stringify(jobs, null, '\t'));
run(PYTHON, ['-I', 'scripts/kokoro.py', join(HOME, 'models'), 'narration/jobs.json']);

for (const scene of scenes) {
	const list = [];
	let t = 0;
	scene.captions.forEach((cue, i) => {
		const base = clip(scene, i);
		run('ffmpeg', ['-y', '-v', 'error', '-i', `${base}.raw.wav`, '-ar', '48000', '-ac', '1', '-c:a', 'pcm_s16le', `${base}.wav`]);
		const d = duration(`${base}.wav`);
		if (i > 0) {
			list.push("file 'gap.wav'");
			t += GAP;
		}
		list.push(`file '${base.replace('narration/', '')}.wav'`);
		cue.start = round(t);
		t += d;
		cue.end = round(t);
	});
	writeFileSync(`narration/${scene.id}.list`, list.join('\n') + '\n');
	const wav = `public/audio/${scene.id}.wav`;
	run('ffmpeg', ['-y', '-v', 'error', '-f', 'concat', '-safe', '0', '-i', `narration/${scene.id}.list`, '-c', 'copy', wav]);

	const seconds = duration(wav);
	scene.audio = `audio/${scene.id}.wav`;
	scene.seconds = round(seconds);
	scene.frames = Math.ceil(seconds * video.fps) + TAIL;
	// Each caption holds through the pause after it; the last one holds through the scene's tail.
	scene.captions.forEach((cue, i, all) => {
		cue.end = i < all.length - 1 ? all[i + 1].start : round(scene.frames / video.fps);
	});
	console.log(`${scene.id}: ${scene.seconds}s, ${scene.frames} frames, cues at ${scene.captions.map((c) => c.start).join(', ')}s`);
}

writeFileSync(file, JSON.stringify(video, null, '\t') + '\n');
const body = video.scenes.reduce((n, s) => n + (s.frames ?? 0), 0);
const total = video.introFrames + body + video.outroFrames;
console.log(`voice ${narrator.voice} (${narrator.lang}, speed ${narrator.speed})`);
console.log(`total: ${total} frames, ${(total / video.fps).toFixed(2)}s (intro ${video.introFrames}, outro ${video.outroFrames})`);
