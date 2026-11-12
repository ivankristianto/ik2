---
name: blog-video
description: Turn one idea from an ivankristianto.com post into a short narrated explainer video for YouTube Shorts, vertical 1080x1920 by default (horizontal 1920x1080 only when Ivan asks), under a minute, in the site's design system with a spoken hook over the intro card and a spoken pointer over an outro that gives the post's URL, both carrying the animated `ik.` signature. Narration is local Kokoro TTS (voice bm_george, Apache-2.0 weights), animation is Remotion built from a shared template, output is an MP4, an SRT and the editable source. Makes files for review only; never uploads, embeds or publishes. Use when Ivan asks for only the MP4, a narrated version or an explainer file of a post, wants to fix or re-render a video already made, or types /blog-video. When he asks to turn a post into a short video or to publish it, use blog-video-pipeline, which runs this skill and then makes the cover and uploads. Runs after the post is published.
user-invocable: true
allowed-tools:
  - Bash(.claude/skills/blog-video/preflight.sh:*)
  - Bash(.claude/skills/blog-video/setup.sh:*)
  - Bash(npm run:*)
  - Bash(npx remotion:*)
---

# Blog video

Input is a published post, by URL or slug. The video always points at the live article, so the outro shows its URL and `npm run render` refuses to run until `https://www.ivankristianto.com/<slug>/` returns 200. If the post isn't published yet, stop and say so. Output goes in `.claude/blog/drafts/<slug>/video/` (gitignored): `out/video.mp4`, `out/video.srt` and the Remotion project that made them. Never only in the scratchpad.

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

Then setup copies `template/` into the post's video folder, sets `post.slug` to the slug, writes `src/tokens.ts` from `wp-content/themes/ik2/theme.json` (the same source as the card's `tokens.css`), and runs `npm install` plus `remotion browser ensure` there. Use npm, not pnpm: pnpm would walk up to the repo's workspace. Running it again on an existing folder only refreshes the tokens.

The voice is Kokoro `bm_george` (British English), Ivan's pick on 2026-11-09. It's recorded in `narrator` in `video.json`. Don't switch to macOS voices: their license is personal, non-commercial use only. Don't switch to Chatterbox or a paid API unless Ivan asks.

## Read the post

Read it in full, from the public REST API (`/wp-json/wp/v2/posts?slug=<slug>`) or the page itself. Take commands and output from the post, not from a fresh run: when a tool's output has changed since the post was written, the video follows the post, and the difference goes in the report.

If blog-feature-image already ran, open `.claude/blog/drafts/<slug>/feature.html`. Its punch line is the intro, word for word, `<em>` included. If there's no card yet, write the punch line by blog-feature-image's rules (4 to 12 words, the surprise or the question, never the title) and say so in the report.

## Choose the story

1. Pick one mechanism the viewer can watch happen: a request path, a state change, a before and after, a command and its output. Don't compress the whole post into a feature list.
2. Write the narration in five beats (And, But, Therefore, from `docs/research/short-explainer-video.md`). The whole video, bookends included, stays under a minute. Measured audio decides the length, not word counts.
   - **Hook**, spoken over the intro card: the punch line or its question.
   - **But**, starting before 8 s: why the obvious answer fails, or what is missing. An `and` sentence of setup may come first if the 8 s still holds; often the hook is setup enough.
   - **Mechanism**, most of the video: one thing happening per sentence, each with its own change on screen.
   - **Therefore**, exactly one sentence: the line to remember.
   - **Pointer**, spoken over the outro: where the full article is.
3. Stay faithful to the post. Keep deployed, proposed and experimental apart. Keep every qualification that changes a security or operational meaning.
4. Third person by default. Never invent Ivan's experiences. Never mention the AI assistant, on screen or in the script.
5. Every caption must be true of the picture under it. A line about Ivan's own terminal over a diagram of the agent's path tells the viewer the opposite of the post. Cut the line or change the picture.
6. Four to seven scenes of at most 12 s each, each one shape from the list below with one to three caption sentences. A scene that runs long gets split, each half with its own heading.
7. Nothing is said over a frozen picture. Every sentence names the item it is about with `focus`, and no stretch of a scene goes more than about 4 s without a change.

## Edit `src/video.json`

The single source for the composition, the narration script and the SRT exporter.

- `post.slug` (the outro URL is built from it) and `post.punch` (the card's line, `<em>` around the green part, `\n` for a forced break; close and reopen `<em>` around a `\n`).
- `orientation`: `vertical` (the default, for YouTube Shorts) or `horizontal`. Use horizontal only when Ivan asks for it in so many words ("horizontal", "landscape", "16:9", "for the post body"). A mention of YouTube alone means a Short, so vertical.
- `narrator`: `{ "voice": "bm_george", "lang": "en-gb", "speed": 1.0 }`. Leave it alone unless Ivan picks another voice.
- `post.hook` and `post.pointer`: `{ "text", "say" }`, spoken over the intro and the outro. The pointer is "Full article at ivankristianto.com" with `say` "Full article at Eevahn Kristianto dot com." unless Ivan asks for another line.
- `introFrames` and `outroFrames` are written by narrate from the measured hook and pointer (at least 60 and 90). Don't set them by hand.
- Per scene: `id`, optional `heading` (a short punch-style line, `<em>` allowed), `captions`, `visual`.
- Each caption is one sentence with a `beat` (`and`, `but`, `mechanism`, `therefore`) and a `focus`: `text` is what's shown and spoken. Add `say` only when George needs a different spelling to pronounce it. Known ones: `WP-CLI` as `W P C L I`, `env file` as `E N V file` (he says "end file"). Ivan's name is said ee-vahn, never eye-van: write `Eevahn` in `say` wherever the name or the domain is spoken. The words must otherwise match.

### Scene shapes

Same patterns as the feature card, now revealed in step with the voice. Pick by the shape of the evidence.

- `steps` (the card's path variant): 3 to 5 mono boxes joined by arrows that draw in. `state` is `skip` (dashed red, struck out), `break` (red), `end` (green), or omitted. One real value per box in `note`.
- `diff` (pattern A): a `file` tab, `lines` with `kind` `ctx`, `del` (turns red and strikes through) or `add` (fades in on green), and an optional `result` line.
- `receipt` (pattern B): a real `command`, its real `output` lines one at a time, and the `answer` row in green.
- `rank`: up to five `rows` of `label` and `score` (0 to 1), each with a bar that draws to its score. `threshold` draws the cut-off; rows under it go grey and struck out, and the best row turns green at `winnerAt` (seconds). Use real scores from the post.
- `token` (pattern C): one value huge in mono on a full-width band, `mark` turns green, `caption` below. At the default 140 px, keep `before + mark + after` under about 20 characters; set `size` (96 fits about 30).

`focus` ties a sentence to the item or items it is about. The item appears a third of a second after the sentence starts (so new caption text and new diagram text don't land in the same instant), stays green while the sentence is spoken, then settles. A list of items spreads across the sentence in order. Focus keys:

- `steps`, `diff`, `rank`: the index of the step, line or row, from 0. `rank` also takes `"threshold"`, and a sentence focused on the best row alone is when it turns green.
- `receipt`: 0 is the command (it types out), 1 to n the output lines, n + 1 the answer row.
- `token`: 0 is the mark, 1 the caption under the band.

An item no sentence names takes `at` (seconds into the scene, or a negative number to be there from the first frame); with neither, items spread over the first 70% of the scene. New shapes go in `src/scenes/` using `reveal()` and `timing()` from `src/motion.ts`.

## Narrate

```bash
npm run narrate               # hook, all scenes, pointer
npm run narrate scene-03      # one scene after editing its text (also: intro, outro)
npm run lint                  # the script and pacing rules; render runs it too
```

Kokoro speaks each caption as its own clip (one model load per run, about 5x faster than real time on the CPU, raw 24 kHz WAVs kept in `narration/`). Each clip is converted to 48 kHz mono, measured, and joined to the scene's other clips with a 0.35 s pause into `public/audio/<id>.wav`. Cue start and end times come straight from those measurements, and `frames` becomes `ceil(seconds * 30) + 12`. It also splits each caption into phrases of at most 42 characters for the screen, timed across the clip by spoken length (the SRT keeps whole sentences). It prints each scene's cue starts and the total including the bookends. Over 60 s, cut words; don't speed the voice up. George runs about 170 words a minute, so 130 to 140 words of script lands near 50 s.

Captions are spoken separately on purpose. Pauses at commas can be longer than pauses between sentences, so silence detection on a whole paragraph can't find where one caption ends.

Then check the words:

```bash
npm run hear                  # Whisper transcript of each scene, printed under the script
```

Any word that comes back different is a mispronunciation to fix with `say`, or Whisper mishearing an accent. On the 1Password sample it caught "end file" for "env file"; "full path" heard as "full pass" was just the British vowel. Fix the first kind, list the second for Ivan.

`npm run lint` fails on: no hook or pointer, a caption without `beat` or `focus`, no `but` before 8 s, anything other than one `therefore`, a scene over 12 s, a pointer that ends more than 1.5 s before the last frame, and a total over 60 s. It warns when nothing changes on screen for more than 4 s; fix those by splitting the sentence or giving it a second focus item, and list any you leave in the report.

A changed sentence means re-running narrate for that scene, then moving any `at` values that point at its cues.

## Design system (fixed)

- 30 fps. Both layouts live in `L` in `src/theme.ts`; components read sizes from it, never from their own numbers.
  - **Vertical, 1080x1920 (default).** Built for the Shorts player, which draws its title, channel name and buttons over the video. Everything that matters stays between x=90 and x=900 (the right-hand button column) and between y=210 and y=1480 (the top bar and the bottom title area). The bottom 440 px stay empty on purpose. Punch line 96 px, scene headings 64 px, captions 52 px, panel mono 30 px. Steps stack top to bottom; a token wraps after its slashes.
  - **Horizontal, 1920x1080.** The 1200x628 card scaled by 1.6: 100 px margins, punch line 96 px, scene headings 68 px, captions 50 px.
- Colours come only from `src/tokens.ts`. Never type a hex value into a component. Drafting white ground, ink text, bookcloth green as the one accent. Red, amber and green only where they mean removed, partial or works.
- System font stacks only: sans for the punch line, headings and captions; mono for code, commands, values and the domain.
- The caption band has a 2 px rule above it, at y=1230 (vertical) or y=850 (horizontal). No diagram crosses that line.
- In vertical, keep scene content short: a step label up to about 22 mono characters, a diff or receipt line up to about 45, a heading of two short lines.
- Signature: `Sig` in `src/Brand.tsx`, the one standard for every video. On the intro and outro it plays the signature animation (pass `frame`): the badge outline draws, fills green, `ik` fades in, the dot blinks once like a cursor, then `ivankristianto.com` types out two characters a frame. On scenes it is the static badge alone, top right. Don't restyle or retime it per post. Never Ivan's full name.
- Motion is opacity fades, lines and bars drawing in, typed text and colour changes, through `reveal()` (ease-out, clamped). No `spring()`: its overshoot is a bounce, which the design rules ban. No scale, no slide, no zoom, no camera moves.
- Not allowed, same as the card: gradients, shadows, glows, grain, photos, stock footage, emoji, music, a label above the punch line, hand-drawn pictures. Real browser footage is fine when it's evidence.
- Proposed work looks proposed: a dashed box or a "proposed" note, never drawn the same as what's deployed. A config screenshot is not proof something ran.
- No client names or ticket keys anywhere: screen, captions, narration, file names.

## Intro and outro

Both are built into the template. Don't restyle them per post.

- **Intro, as long as the hook:** George starts the hook at frame 6. The punch line fades in under him, the `<em>` part arriving about halfway through, while the signature animates bottom left. Fades to the first scene over its last 8 frames.
- **Outro, as long as the pointer plus a second:** George says the pointer over the same signature animation, large and centred. After the domain, `/<slug>/` types out in green on the line below, then "Read the full article" fades in. A long slug shrinks to fit on one line. There is no draft variant. Links in a Short's description aren't clickable, so the typed and spoken URL is how a viewer finds the post.

No caption band on either: the intro's punch line and the outro's URL are the text. The SRT carries the hook and the pointer as its first and last cues.

## Render

```bash
npm run typecheck
npm run bookends     # out/intro.mp4 and out/outro.mp4, a cheap first look
npm run render       # lints, checks the post URL is live, writes out/video.srt, then out/video.mp4
npm run studio       # scrub the timeline in a browser, when something looks off
```

`npx remotion still src/index.ts Video still.png --frame=<n>` renders one frame without encoding. Use it while placing things.

## Verify

1. **Faithfulness.** Reread every caption (and any `say`) against the post: triggers, current versus proposed, security boundaries. From the video folder, run `bash "$(git rev-parse --show-toplevel)/.claude/skills/blog-ideas/leak-check.sh" src/video.json out/video.srt` and fix anything it flags.
2. **The file.** `ffprobe -v error -count_frames -select_streams v:0 -show_entries stream=width,height,r_frame_rate,nb_read_frames,pix_fmt -show_entries format=duration out/video.mp4`. Expect 1080x1920 (or 1920x1080 for horizontal), 30/1, yuv420p, and a frame count equal to the total narrate printed (`introFrames` + scene frames + `outroFrames`). Check the audio stream is AAC at 48 kHz. Then decode the whole thing: `ffmpeg -v error -i out/video.mp4 -f null -` must print nothing.
3. **No dead air.** `ffmpeg -hide_banner -nostats -i out/video.mp4 -af silencedetect=noise=-50dB:d=1 -f null - 2>&1 | rg 'silence_(start|end)'` should show one silence only, the second or so after the pointer at the very end. Nothing at the start: the hook begins at 0.2 s.
4. **Look at it.** Extract stills with `ffmpeg -ss <t> -i out/video.mp4 -frames:v 1 stills/<t>.png`: mid-intro, the last frame of every scene with all its items revealed, one mid-reveal, and mid-outro. Also take three or four stills inside the first second of the intro and of the outro, to see the signature mid-animation and the URL fully typed. Montage them (`ffmpeg -pattern_type glob -i 'stills/*.png' -vf 'scale=960:540,tile=2x4:padding=8' -frames:v 1 stills/sheet.png`, adjusting the grid to the count) and Read the sheet, then Read single stills at full size where the sheet is busy. Check for clipping, labels against arrows, text crossing the caption rule, and readability at phone size (`ffmpeg -i <still> -vf scale=480:-1 phone.png`). In vertical, also check nothing sits right of x=900 or below y=1480, where the Shorts overlays go. Scale vertical stills to `405:720` in the montage.
5. **Captions.** `out/video.srt` cues are in order, don't overlap, the first is the hook and the last the pointer. Every cue matches the final script.
6. **Listening.** `npm run hear` checks the words, not how they sound. You can't hear the file: say so, and ask Ivan to check pacing and tone. Ivan is often on his phone, where `file:///` links don't open and a video embedded in a claude.ai artifact didn't play. Offer to copy `out/video.mp4` and `out/video.srt` to the vault's `Inbox/` with a short note embedding the video (`![[name.mp4]]`), and copy only on a yes: vault writes need his ask.

Fix what the checks find and render again. Stop iterating on looks once these pass.

## Stop at the files

Don't upload, embed, post or edit the article. Each of those needs Ivan's explicit yes. The cover and the upload to every video host are blog-video-pipeline's job. Kokoro's weights are Apache-2.0, so the narration itself can be published.

Rendering was verified on macOS with system fonts. Other platforms will fall back to other faces.

## Report

- What the video shows, scene by scene in one line each, and where each visual came from (flag anything sketched rather than taken from the post).
- Measured duration, frame count, and which checks passed.
- `file:///` links to `out/video.mp4`, `out/video.srt`, the contact sheet and `src/video.json`.
- What Ivan should listen for: anything `npm run hear` got wrong that you left as an accent, plus names, acronyms and version numbers.
- The vault note's path, if you copied the video there.
