---
name: blog-video
description: Turn one idea from an ivankristianto.com post into a short narrated explainer video for YouTube Shorts, vertical 1080x1920 by default (horizontal 1920x1080 only when Ivan asks), under a minute, in the site's design system with a 2 second intro card and a 2 second outro. Narration is local Kokoro TTS (voice bm_george, Apache-2.0 weights), animation is Remotion built from a shared template, output is an MP4, an SRT and the editable source. Makes files for review only; never uploads, embeds or publishes. Use when Ivan asks for a video, explainer, narrated version, YouTube or social clip of a post, says "make a video for this post", or types /blog-video. Optional step after blog-draft and blog-feature-image.
user-invocable: true
allowed-tools:
  - Bash(.claude/skills/blog-video/preflight.sh:*)
  - Bash(.claude/skills/blog-video/setup.sh:*)
  - Bash(npm run:*)
  - Bash(npx remotion:*)
---

# Blog video

Input is a post: a draft in `.claude/blog/drafts/<slug>.md`, a published URL, or a WordPress draft Ivan points you to. Output goes in `.claude/blog/drafts/<slug>/video/` (gitignored): `out/video.mp4`, `out/video.srt` and the Remotion project that made them. Never only in the scratchpad.

The video is the feature card brought to life. Same ground, same ink, same green, same punch-line type, same `ik.` signature. Someone who saw the card in a feed should recognise the video as the same post.

## Set up

Run everything on the host. Kokoro, Whisper and Remotion's headless Chrome don't exist in the tools container.

```bash
.claude/skills/blog-video/setup.sh <slug>
```

It runs `preflight.sh` first, which installs or checks the whole toolchain and fails loudly if anything is wrong:

- `node`, `ffmpeg`/`ffprobe` and `uv`, installed with Homebrew when missing.
- A Python 3.12 venv in `~/.cache/blog-video/venv` with pinned `kokoro-onnx` and `soundfile`, plus `mlx-whisper` on Apple Silicon. Shared by every post, so a new video doesn't download anything.
- `kokoro-v1.0.onnx` and `voices-v1.0.bin` in `~/.cache/blog-video/models`, checked against pinned SHA-256 sums. A mismatch deletes the download and fails.
- A smoke test: George says one line.

`preflight.sh --check` only reports. Set `BLOG_VIDEO_HOME` to move the cache.

Then setup copies `template/` into the post's video folder, sets `post.slug` to the slug and `post.status` to `draft`, writes `src/tokens.ts` from `wp-content/themes/ik2/theme.json` (the same source as the card's `tokens.css`), and runs `npm install` plus `remotion browser ensure` there. Use npm, not pnpm: pnpm would walk up to the repo's workspace. Running it again on an existing folder only refreshes the tokens.

The voice is Kokoro `bm_george` (British English), Ivan's pick on 2026-11-09. It's recorded in `narrator` in `video.json`. Don't switch to macOS voices: their license is personal, non-commercial use only. Don't switch to Chatterbox or a paid API unless Ivan asks.

## Read the post

Read it in full. For a WordPress draft, use blog-review's REST fetch through 1Password: credentials only to the production HTTPS origin, a browser User-Agent, never printed. A preview 404 doesn't mean REST can't read the draft. Don't publish anything to make it readable.

If blog-feature-image already ran, open `.claude/blog/drafts/<slug>/feature.html`. Its punch line is the intro, word for word, `<em>` included. If there's no card yet, write the punch line by blog-feature-image's rules (4 to 12 words, the surprise or the question, never the title) and say so in the report.

## Choose the story

1. Pick one mechanism the viewer can watch happen: a request path, a state change, a before and after, a command and its output. Don't compress the whole post into a feature list.
2. Write the narration as problem, example, mechanism, what to do, and the limit that matters. About 45 to 50 seconds of speech, so with the 4 seconds of bookends the video stays under a minute. Measured audio decides the length, not word counts.
3. Stay faithful to the post. Keep deployed, proposed and experimental apart. Keep every qualification that changes a security or operational meaning.
4. Third person by default. Never invent Ivan's experiences. Never mention the AI assistant, on screen or in the script.
5. Every caption must be true of the picture under it. A line about Ivan's own terminal over a diagram of the agent's path tells the viewer the opposite of the post. Cut the line or change the picture.
6. Three to six scenes, each one shape from the list below, each with one to four caption sentences.

## Edit `src/video.json`

The single source for the composition, the narration script and the SRT exporter.

- `post.slug`, `post.status` (`draft` or `published`) and `post.punch` (the card's line, `<em>` around the green part, `\n` for a forced break).
- `orientation`: `vertical` (the default, for YouTube Shorts) or `horizontal`. Use horizontal only when Ivan asks for it in so many words ("horizontal", "landscape", "16:9", "for the post body"). A mention of YouTube alone means a Short, so vertical.
- `narrator`: `{ "voice": "bm_george", "lang": "en-gb", "speed": 1.0 }`. Leave it alone unless Ivan picks another voice.
- `introFrames` and `outroFrames` stay at 60 (2 s at 30 fps) unless Ivan asks.
- Per scene: `id`, optional `heading` (a short punch-style line, `<em>` allowed), `captions`, `visual`.
- Each caption is one sentence: `text` is what's shown and spoken. Add `say` only when George needs a different spelling to pronounce it. Known ones: `WP-CLI` as `W P C L I`, `env file` as `E N V file` (he says "end file"). The words must otherwise match.

### Scene shapes

Same patterns as the feature card, now revealed in step with the voice. Pick by the shape of the evidence.

- `steps` (the card's path variant): 3 to 5 mono boxes joined by arrows that draw in. `state` is `skip` (dashed red, struck out), `break` (red), `end` (green), or omitted. One real value per box in `note`.
- `diff` (pattern A): a `file` tab, `lines` with `kind` `ctx`, `del` (turns red and strikes through) or `add` (fades in on green), and an optional `result` line.
- `receipt` (pattern B): a real `command`, its real `output` lines one at a time, and the `answer` row in green.
- `token` (pattern C): one value huge in mono on a full-width band, `mark` turns green, `caption` below. At the default 140 px, keep `before + mark + after` under about 20 characters; set `size` (96 fits about 30).

Items appear evenly over the first 70% of the scene. When an item belongs to a sentence, give it `at` (seconds into the scene) using the cue times narration writes, so the box appears as George says it. New shapes go in `src/scenes/` using `reveal()` from `src/motion.ts`.

## Narrate

```bash
npm run narrate               # all scenes
npm run narrate scene-03      # one scene after editing its text
```

Kokoro speaks each caption as its own clip (one model load per run, about 5x faster than real time on the CPU, raw 24 kHz WAVs kept in `narration/`). Each clip is converted to 48 kHz mono, measured, and joined to the scene's other clips with a 0.35 s pause into `public/audio/<id>.wav`. Cue start and end times come straight from those measurements, and `frames` becomes `ceil(seconds * 30) + 12`. It prints each scene's cue starts and the total including the bookends. Over 60 s, cut words; don't speed the voice up. George runs about 170 words a minute, so 130 to 140 words of script lands near 50 s.

Captions are spoken separately on purpose. Pauses at commas can be longer than pauses between sentences, so silence detection on a whole paragraph can't find where one caption ends.

Then check the words:

```bash
npm run hear                  # Whisper transcript of each scene, printed under the script
```

Any word that comes back different is a mispronunciation to fix with `say`, or Whisper mishearing an accent. On the 1Password sample it caught "end file" for "env file"; "full path" heard as "full pass" was just the British vowel. Fix the first kind, list the second for Ivan.

A changed sentence means re-running narrate for that scene, then moving any `at` values that point at its cues.

## Design system (fixed)

- 30 fps. Both layouts live in `L` in `src/theme.ts`; components read sizes from it, never from their own numbers.
  - **Vertical, 1080x1920 (default).** Built for the Shorts player, which draws its title, channel name and buttons over the video. Everything that matters stays between x=90 and x=900 (the right-hand button column) and between y=210 and y=1480 (the top bar and the bottom title area). The bottom 440 px stay empty on purpose. Punch line 96 px, scene headings 64 px, captions 52 px, panel mono 30 px. Steps stack top to bottom; a token wraps after its slashes.
  - **Horizontal, 1920x1080.** The 1200x628 card scaled by 1.6: 100 px margins, punch line 96 px, scene headings 68 px, captions 50 px.
- Colours come only from `src/tokens.ts`. Never type a hex value into a component. Drafting white ground, ink text, bookcloth green as the one accent. Red, amber and green only where they mean removed, partial or works.
- System font stacks only: sans for the punch line, headings and captions; mono for code, commands, values and the domain.
- The caption band has a 2 px rule above it, at y=1230 (vertical) or y=850 (horizontal). No diagram crosses that line.
- In vertical, keep scene content short: a step label up to about 22 mono characters, a diff or receipt line up to about 45, a heading of two short lines.
- Signature: full `ik.` badge plus `ivankristianto.com` on the intro and outro, the badge alone top right on scenes. Never Ivan's full name.
- Motion is opacity fades, arrows drawing in and colour changes, through `reveal()` (ease-out, clamped). No `spring()`: its overshoot is a bounce, which the design rules ban. No scale, no slide, no zoom, no camera moves.
- Not allowed, same as the card: gradients, shadows, glows, grain, photos, stock footage, emoji, music, a label above the punch line, hand-drawn pictures. Real browser footage is fine when it's evidence.
- Proposed work looks proposed: a dashed box or a "proposed" note, never drawn the same as what's deployed. A config screenshot is not proof something ran.
- No client names or ticket keys anywhere: screen, captions, narration, file names.

## Intro and outro

Both are built into the template. Don't restyle them per post.

- **Intro, 2 s, silent:** the punch line, the `<em>` part fading in after the rest, the signature bottom left. Fades to the first scene over its last 8 frames.
- **Outro, 2 s, silent:** the signature large and centred. Published posts get "Read the full article" and `ivankristianto.com/<slug>/`. Drafts get "Full article coming soon" and no URL, so nothing points at a page that doesn't exist yet. Set `post.status` correctly.

No captions on either. The SRT starts at 00:00:02,000 because `npm run srt` offsets every cue by `introFrames`.

## Render

```bash
npm run typecheck
npm run bookends     # out/intro.mp4 and out/outro.mp4, 4 s total, a cheap first look
npm run render       # writes out/video.srt, then out/video.mp4
npm run studio       # scrub the timeline in a browser, when something looks off
```

`npx remotion still src/index.ts Video still.png --frame=<n>` renders one frame without encoding. Use it while placing things.

## Verify

1. **Faithfulness.** Reread every caption (and any `say`) against the post: triggers, current versus proposed, security boundaries. From the video folder, run `bash "$(git rev-parse --show-toplevel)/.claude/skills/blog-ideas/leak-check.sh" src/video.json out/video.srt` and fix anything it flags.
2. **The file.** `ffprobe -v error -count_frames -select_streams v:0 -show_entries stream=width,height,r_frame_rate,nb_read_frames,pix_fmt -show_entries format=duration out/video.mp4`. Expect 1080x1920 (or 1920x1080 for horizontal), 30/1, yuv420p, and a frame count equal to the total narrate printed (60 + scene frames + 60). Check the audio stream is AAC at 48 kHz. Then decode the whole thing: `ffmpeg -v error -i out/video.mp4 -f null -` must print nothing.
3. **Silent bookends.** `ffmpeg -hide_banner -nostats -i out/video.mp4 -af silencedetect=noise=-50dB:d=1 -f null - 2>&1 | rg 'silence_(start|end)'` should show silence from 0 to 2.0 s and from the end of the last sentence to the end of the file, with no long silence in between.
4. **Look at it.** Extract stills with `ffmpeg -ss <t> -i out/video.mp4 -frames:v 1 stills/<t>.png`: mid-intro, the last frame of every scene with all its items revealed, one mid-reveal, and mid-outro. Montage them (`magick montage stills/*.png -tile 2x -geometry 960x540+8+8 stills/sheet.png`) and Read the sheet, then Read single stills at full size where the sheet is busy. Check for clipping, labels against arrows, text crossing the caption rule, and readability at phone size (`magick <still> -resize 480x phone.png`). In vertical, also check nothing sits right of x=900 or below y=1480, where the Shorts overlays go. Montage vertical stills with `-tile 4x -geometry 405x720+6+6`.
5. **Captions.** `out/video.srt` cues are in order, don't overlap, start at or after 2 s and end before the outro. Every cue matches the final script.
6. **Listening.** `npm run hear` checks the words, not how they sound. You can't hear the file: say so, and ask Ivan to check pacing and tone. Ivan is often on his phone, where `file:///` links don't open and a video embedded in a claude.ai artifact didn't play. Offer to copy `out/video.mp4` and `out/video.srt` to the vault's `Inbox/` with a short note embedding the video (`![[name.mp4]]`), and copy only on a yes: vault writes need his ask.

Fix what the checks find and render again. Stop iterating on looks once these pass.

## Stop at the files

Don't upload, embed, post or edit the article. Each of those needs Ivan's explicit yes. Kokoro's weights are Apache-2.0, so the narration itself can be published.

Rendering was verified on macOS with system fonts. Other platforms will fall back to other faces.

## Report

- What the video shows, scene by scene in one line each, and where each visual came from (flag anything sketched rather than taken from the post).
- Measured duration, frame count, and which checks passed.
- `file:///` links to `out/video.mp4`, `out/video.srt`, the contact sheet and `src/video.json`.
- What Ivan should listen for: anything `npm run hear` got wrong that you left as an accent, plus names, acronyms and version numbers.
- The vault note's path, if you copied the video there.
