---
name: blog-video
description: "Use when turning blog articles into narrated videos."
version: 0.1.0
author: Ivan Kristianto, Hermes Agent
platforms: [macos]
user-invocable: true
---

# Blog video

Turn one idea from an ivankristianto.com article into a short narrated explainer. Produce a playable MP4, separate captions and editable Remotion source. Use local macOS speech synthesis with **Jamie (Premium)**. No paid narration API, voice cloning, avatar or music by default.

## When to use

Use when Ivan asks for an article explainer, a narrated blog video, or `/blog-video`. This produces review files. It does not authorize uploading, embedding, publishing or changing the article.

## Prerequisites and working files

- macOS with `/usr/bin/say` and the installed `Jamie (Premium)` English voice; verify the exact name with `terminal(command="say -v '?'", timeout=20)`. VoiceOver is the screen reader, not the narration interface.
- Node.js, FFmpeg and ffprobe. Remotion, React and TypeScript are project-local dependencies. Discover compatible versions rather than guessing them; pin the resolved dependencies and keep the lockfile.
- Read the source article in full. Use the existing `blog-review` REST-fetch workflow for drafts. Send credentials only to the production HTTPS origin, include a browser User-Agent, and never print credentials. A preview returning 404 does not establish that authenticated REST cannot read the draft. Do not publish it to make it readable.
- Keep reusable source and assets in a durable, task-owned project folder, not only a scratchpad. Keep per-article outputs gitignored, for example `.claude/blog/drafts/<slug>/video/`; check ignore rules first. Never commit draft snapshots, audio, renders, browser downloads or node_modules as part of saving this skill.
- Read project guidance before implementation. When operating through Hermes, use Ivan's current approved coding harness workflow, not a substitute implementation agent. Leave source uncommitted unless asked otherwise.

## Choose the story

1. Pick one concrete mechanism the viewer can see: a request path, a state change, a release pipeline or a before/after backed by evidence. Avoid compressing the whole article into a feature list.
2. Write a short spoken story: problem, example, mechanism, practical response and consequential limit. Target roughly 50–55 seconds, below one minute unless Ivan asks otherwise. Word counts are estimates; measured audio controls duration.
3. Keep every claim faithful to the article. Distinguish deployed behavior, proposed work and isolated experiments. Preserve qualifications that change security or operational meaning. For example, a writable location already handled must not become an unsolved prerequisite in the summary.
4. Use third-person narration by default. Never invent Ivan's experiences or describe agent tests as his personal actions. Do not mention the assistant in the public script or on screen.
5. Plan about five scenes with short sentence-level captions. Technical spelling stays correct on screen; phonetic narration wording may differ for pronunciation. Preserve separate spoken and displayed text when necessary.

## Generate narration before final timing

Use the same selected voice and rate across scenes, starting at 155 words per minute. Render one clip per scene so a correction only requires regenerating that clip.

Through `terminal`, with the video project as working directory:

```bash
say -v 'Jamie (Premium)' -r 155 \
  -f narration/scene-01.txt -o public/audio/scene-01.aiff
ffmpeg -y -i public/audio/scene-01.aiff \
  -ar 48000 -ac 1 -c:a pcm_s16le public/audio/scene-01.wav
ffprobe -v error -show_entries format=duration \
  -of json public/audio/scene-01.wav
ffmpeg -hide_banner -nostats -i public/audio/scene-01.wav \
  -af silencedetect=noise=-40dB:d=0.25 -f null -
```

Retain raw AIFF and normalized WAV files. Store each scene's ID, text, audio path, measured duration and frame count in `scenes.json`. At 30 fps, `ceil(durationSeconds * 30) + 12` gives a short 0.4-second tail; compute this with a tool. Include opening/closing holds in the total duration.

Use silence boundaries to inform sentence cues, not comma pauses. Silence detection is sentence-level timing assistance, not speech recognition or word alignment. Check all cues against the script. A changed line requires updating its audio, duration, frames, caption cues and animation timing together.

## Animate in Remotion

- Default delivery: 1920×1080 landscape, 30 fps, H.264 video and AAC audio. Build scene timing from the measured clips; never fit narration to an assumed video duration.
- Use the site's current design tokens: drafting-white ground, ink text, bookcloth-green accent, system sans and monospace. Status colors only when they convey status. Read the current theme tokens rather than preserving the pilot's incidental colors as a new design system.
- Prefer animated diagrams, short code highlights and genuine browser footage. No decorative stock footage, neon, gradients or visual claims without evidence.
- Use progressive disclosure and large labels. Reserve a bottom caption band, keep diagrams outside it, and keep captions to about two lines. At 1080p, the pilot used 50px captions in a band starting at y=850; treat that as a checked starting point, not a universal layout.
- Keep scene/caption data shared between the composition and SRT exporter. Provide typecheck, render and preview/studio commands in package.json and reproduction instructions in README.md.
- Make proposed work visibly proposed. An uploads mount marked read-only does not make the whole application filesystem read-only. A configuration screenshot is not an execution test.
- Attribute the article with the domain. For an unpublished draft, use upcoming-article wording and do not imply a public URL exists.

Run actual typechecking and a full render through `terminal`. For the pilot composition these commands were exercised:

```bash
npm run typecheck
npm run render
```

The render script generated SRT from shared cues, then ran:

```bash
npx remotion render src/index.ts Pilot output/pilot.mp4 --codec h264
```

Adapt the entry point and composition ID to the actual project. Stop cosmetic iteration once correctness and delivery gates pass.

## Verification

1. Independently compare the final script with the article, especially deployment triggers, current versus proposed state and security boundaries. Review feedback must reach the actual final render.
2. Run `ffprobe` on the exact delivery MP4 to confirm dimensions, fps, frame count, duration, pixel format, color range, audio codec and sample rate. A render flag alone does not prove the encoded result.
3. Decode the complete file with `terminal(command="ffmpeg -v error -i output/pilot.mp4 -f null -", timeout=120)` from the project directory. Verify narration is non-silent and each clip appears at the expected timeline position. Waveform comparison can establish audio preservation, not pronunciation quality.
4. Extract representative stills from the final MP4, including late scene states, captions, transitions and the ending. Inspect them with `vision_analyze`; check clipping, label/arrow collisions and phone-size readability. Build a contact sheet for review. Do not use obsolete draft stills as evidence for the final file.
5. Listen to narration when an audio-capable review path is available. Otherwise state that pronunciation and pacing still need Ivan's listening review; never claim hearing a file based on ffprobe or waveform checks.
6. Verify captions match the final script, stay inside the timeline and are ordered without overlap. Keep a separately usable `.srt` even when captions are burned in.
7. Retain editable source, pinned dependencies, narration text, audio, timing data and reproduction instructions. A source ZIP must exclude node_modules, caches, credentials and private source snapshots; test the archive before delivery.
8. Close only task-owned agents, preview servers and browser sessions. Preserve source and deliverables for revisions. Do not delete another session's resources.

## Pitfalls and boundaries

- macOS system fonts may fall back differently elsewhere. Label macOS as the verified rendering environment unless another platform is actually tested.
- Downloading premium voices is separate from exporting audio. Confirm the exact voice is available and render a sample before doing the full project. Do not silently substitute Daniel, Zoe or an API provider.
- Successful voice export does not establish redistribution rights. Check applicable voice/OS terms before public publication; do not represent a local pilot as a licensing review.
- Project artifacts belong in the project or configured scratch directory, never task-created paths under system temporary directories.
- Browser preview, upload, WordPress embedding and public distribution are separate approvals. A request to render or save this skill authorizes none of them.

## Deliver

Attach the playable MP4, SRT and editable-source ZIP on Telegram using `MEDIA:` paths. Give the measured duration, checks actually passed and any material limitation. Ask for feedback on clarity, pacing and pronunciation. Keep the article unchanged until Ivan explicitly approves its publication or embedding.
