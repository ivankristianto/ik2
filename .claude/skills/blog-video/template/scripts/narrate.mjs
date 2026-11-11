// Speak the hook, every caption and the pointer as separate Kokoro clips, join each scene's clips with a fixed pause,
// and write audio paths, frames, caption times and phrase chunks back to src/video.json.
// One clip per sentence so cue edges come from measured clip lengths, not from guessing at pauses.
// Usage: node scripts/narrate.mjs [intro|outro|scene-id ...]   Needs .claude/skills/blog-video/preflight.sh to have passed.
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { homedir } from 'node:os';
import { join } from 'node:path';

const HOME = process.env.BLOG_VIDEO_HOME ?? join(process.env.XDG_CACHE_HOME ?? join(homedir(), '.cache'), 'blog-video');
const PYTHON = join(HOME, 'venv/bin/python');
const GAP = 0.35;
const TAIL = 12;
// The hook starts 6 frames in, well inside the first half second. The pointer waits for the outro's fade-in.
const BOOKEND = { intro: { lead: 6, tail: 8, min: 60 }, outro: { lead: 10, tail: 30, min: 90 } };
const CHUNK = 42;
const WEAK = /^(a|an|the|of|to|for|and|or|in|on|at|with|its|is)$/i;
const file = 'src/video.json';
const video = JSON.parse(readFileSync(file, 'utf8'));
const narrator = { voice: 'bm_george', lang: 'en-gb', speed: 1.0, ...video.narrator };
video.narrator = narrator;
const only = process.argv.slice(2);
const wanted = (id) => !only.length || only.includes(id);

const run = (cmd, args) => execFileSync(cmd, args, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
const duration = (f) => Number(JSON.parse(run('ffprobe', ['-v', 'error', '-show_entries', 'format=duration', '-of', 'json', f])).format.duration);
const round = (n) => Number(n.toFixed(3));
const to48k = (from, to) => run('ffmpeg', ['-y', '-v', 'error', '-i', from, '-ar', '48000', '-ac', '1', '-c:a', 'pcm_s16le', to]);

// A digit takes about as long to say as a short word, so it weighs more than a letter when splitting a clip's time.
const weight = (word) => word.length + 5 * (word.match(/\d/g) ?? []).length;

/** Split a sentence into phrases of at most CHUNK characters, timed across the clip by spoken weight. */
const chunk = (cue) => {
	const words = cue.text.trim().split(/\s+/);
	const parts = Math.ceil(cue.text.length / CHUNK);
	const target = cue.text.length / parts;
	const groups = [[]];
	for (const word of words) {
		const current = groups[groups.length - 1];
		const length = current.join(' ').length;
		const full = length + word.length + 1 > CHUNK;
		const clause = length >= target * 0.6 && /[,;:]$/.test(current[current.length - 1] ?? '');
		if (current.length && (full || clause || length >= target)) {
			// A phrase that ends on "the" or "of" reads as cut off, so those words start the next one.
			const dangling = current.length > 1 && WEAK.test(current[current.length - 1]) ? [current.pop()] : [];
			groups.push([...dangling, word]);
		}
		else current.push(word);
	}
	const total = words.reduce((n, w) => n + weight(w), 0);
	let seen = 0;
	return groups.map((group, i) => {
		const start = cue.start + ((cue.spoken - cue.start) * seen) / total;
		seen += group.reduce((n, w) => n + weight(w), 0);
		const end = i === groups.length - 1 ? cue.end : cue.start + ((cue.spoken - cue.start) * seen) / total;
		return { text: group.join(' '), start: round(start), end: round(end) };
	});
};

if (!existsSync(PYTHON) || !existsSync(join(HOME, 'models/kokoro-v1.0.onnx'))) {
	console.error(`Kokoro is not set up in ${HOME}; run .claude/skills/blog-video/preflight.sh`);
	process.exit(1);
}
mkdirSync('narration', { recursive: true });
mkdirSync('public/audio', { recursive: true });
run('ffmpeg', ['-y', '-v', 'error', '-f', 'lavfi', '-i', 'anullsrc=r=48000:cl=mono', '-t', String(GAP), '-c:a', 'pcm_s16le', 'narration/gap.wav']);

const scenes = video.scenes.filter((s) => wanted(s.id));
const bookends = [
	['intro', video.post.hook],
	['outro', video.post.pointer],
].filter(([id, line]) => line && wanted(id));
const clip = (scene, i) => `narration/${scene.id}-${String(i + 1).padStart(2, '0')}`;
const job = (line, base) => {
	const text = (line.say ?? line.text).trim();
	writeFileSync(`${base}.txt`, text + '\n');
	return { text, out: `${base}.raw.wav`, ...narrator };
};

const jobs = [
	...bookends.map(([id, line]) => job(line, `narration/${id}`)),
	...scenes.flatMap((scene) => scene.captions.map((cue, i) => job(cue, clip(scene, i)))),
];
writeFileSync('narration/jobs.json', JSON.stringify(jobs, null, '\t'));
run(PYTHON, ['-I', 'scripts/kokoro.py', join(HOME, 'models'), 'narration/jobs.json']);

for (const [id, line] of bookends) {
	const { lead, tail, min } = BOOKEND[id];
	to48k(`narration/${id}.raw.wav`, `public/audio/${id}.wav`);
	const seconds = duration(`public/audio/${id}.wav`);
	line.audio = `audio/${id}.wav`;
	line.start = round(lead / video.fps);
	line.end = round(line.start + seconds);
	video[`${id}Frames`] = Math.max(min, lead + Math.ceil(seconds * video.fps) + tail);
	console.log(`${id}: ${round(seconds)}s spoken from frame ${lead}, ${video[`${id}Frames`]} frames`);
}

for (const scene of scenes) {
	const list = [];
	let t = 0;
	scene.captions.forEach((cue, i) => {
		const base = clip(scene, i);
		to48k(`${base}.raw.wav`, `${base}.wav`);
		const d = duration(`${base}.wav`);
		if (i > 0) {
			list.push("file 'gap.wav'");
			t += GAP;
		}
		list.push(`file '${base.replace('narration/', '')}.wav'`);
		cue.start = round(t);
		t += d;
		cue.spoken = round(t);
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
		cue.chunks = chunk(cue);
	});
	console.log(`${scene.id}: ${scene.seconds}s, ${scene.frames} frames, cues at ${scene.captions.map((c) => c.start).join(', ')}s`);
}

writeFileSync(file, JSON.stringify(video, null, '\t') + '\n');
const body = video.scenes.reduce((n, s) => n + (s.frames ?? 0), 0);
const total = video.introFrames + body + video.outroFrames;
console.log(`voice ${narrator.voice} (${narrator.lang}, speed ${narrator.speed})`);
console.log(`total: ${total} frames, ${(total / video.fps).toFixed(2)}s (intro ${video.introFrames}, outro ${video.outroFrames})`);
