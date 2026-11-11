// Transcribe each scene's narration with Whisper and print it under the script, so mispronunciations show up as wrong words.
// Usage: node scripts/hear.mjs [scene-id ...]   Apple Silicon only; the model (about 1.5 GB) downloads on first use.
import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readFileSync } from 'node:fs';
import { homedir } from 'node:os';
import { join } from 'node:path';

const HOME = process.env.BLOG_VIDEO_HOME ?? join(process.env.XDG_CACHE_HOME ?? join(homedir(), '.cache'), 'blog-video');
const WHISPER = join(HOME, 'venv/bin/mlx_whisper');
if (!existsSync(WHISPER)) {
	console.error(`mlx_whisper is not in ${HOME}/venv; run .claude/skills/blog-video/preflight.sh on an Apple Silicon Mac`);
	process.exit(1);
}
const video = JSON.parse(readFileSync('src/video.json', 'utf8'));
const only = process.argv.slice(2);
mkdirSync('narration/heard', { recursive: true });

const clips = [
	{ id: 'intro', audio: video.post.hook?.audio, lines: [video.post.hook] },
	...video.scenes.map((s) => ({ id: s.id, audio: s.audio, lines: s.captions })),
	{ id: 'outro', audio: video.post.pointer?.audio, lines: [video.post.pointer] },
];
for (const { id, audio, lines } of clips) {
	if ((only.length && !only.includes(id)) || !audio) continue;
	execFileSync(WHISPER, [`public/${audio}`, '--model', 'mlx-community/whisper-large-v3-turbo', '--language', 'en', '--output-format', 'txt', '--output-dir', 'narration/heard', '--verbose', 'False'], {
		stdio: 'ignore',
		env: { ...process.env, HF_HOME: join(HOME, 'hf') },
	});
	console.log(`== ${id}`);
	console.log(`script: ${lines.map((c) => c.text).join(' ')}`);
	console.log(`heard:  ${readFileSync(`narration/heard/${id}.txt`, 'utf8').replace(/\s+/g, ' ').trim()}\n`);
}
