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

for (const scene of video.scenes) {
	if ((only.length && !only.includes(scene.id)) || !scene.audio) continue;
	execFileSync(WHISPER, [`public/${scene.audio}`, '--model', 'mlx-community/whisper-large-v3-turbo', '--language', 'en', '--output-format', 'txt', '--output-dir', 'narration/heard', '--verbose', 'False'], {
		stdio: 'ignore',
		env: { ...process.env, HF_HOME: join(HOME, 'hf') },
	});
	console.log(`== ${scene.id}`);
	console.log(`script: ${scene.captions.map((c) => c.text).join(' ')}`);
	console.log(`heard:  ${readFileSync(`narration/heard/${scene.id}.txt`, 'utf8').replace(/\s+/g, ' ').trim()}\n`);
}
