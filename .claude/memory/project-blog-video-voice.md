---
name: project-blog-video-voice
description: blog-video narrates with Kokoro voice bm_george (Apache-2.0); Apple voices like Jamie are personal-use only, Chatterbox was tried and dropped
metadata:
  type: project
---

On 2026-11-09 Ivan picked Kokoro (`kokoro-v1.0.onnx`, voice `bm_george`, lang `en-gb`) as the narration voice for the blog-video skill, after comparing it with three other Kokoro voices and Chatterbox original and Turbo.

**Why:** macOS system voices (Jamie Premium, the first pilot) are licensed only for personal, non-commercial projects, so their audio can't go on a public video. Kokoro's weights are Apache-2.0. Chatterbox (MIT) took 40 to 90 s per clip, needed 3 GB and a setuptools pin, and a Whisper check caught it saying "emit" for "omit" and garbling "v2 slash posts". Kokoro got every word right except "WP-CLI", read as "wp-cly".

**How to apply:** don't suggest Apple voices or Chatterbox again unless Ivan asks to clone his own voice (Chatterbox's one real advantage). Fix Kokoro mispronunciations with a caption's `say` override (`"say": "W P C L I"`), not a different voice. The toolchain lives in `~/.cache/blog-video/` and `.claude/skills/blog-video/preflight.sh` sets it up.
