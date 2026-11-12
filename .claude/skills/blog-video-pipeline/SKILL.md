---
name: blog-video-pipeline
description: Take one published ivankristianto.com post to a short video on every video host in one run. Runs blog-video for the MP4, makes the vertical cover (the video's feature image) from the post's card, writes the title, description and tags, then uploads to every registered provider (YouTube today, more later) with the cover as thumbnail and reads each upload back. Uploads private unless Ivan says otherwise, and stops for his yes before anything leaves the machine. Use when Ivan names a post and asks to turn it into a short or a video, make a Short of it, publish or upload a video, says "the video flow" or "video pipeline", or types /blog-video-pipeline. Use blog-video instead only when he asks for just the MP4 or to fix a video already made.
user-invocable: true
allowed-tools:
  - Bash(python3 -I .claude/skills/blog-video-pipeline/scripts/publish.py:*)
---

# Blog video pipeline: published post to every video host

Input is one published post, by URL or slug. Output, all in `.claude/blog/drafts/<slug>/` (gitignored):

- `video/out/video.mp4` and `video/out/video.srt`, from blog-video;
- `cover/feature-vertical.{html,png,jpg}`, the video's cover;
- `video/publish.json`, the metadata every provider reads;
- `video/out/<provider>-upload-receipt.json`, one per host it went to.

Each step that names a skill runs that skill in full, with its own rules and checks. This file adds the order, the gates, the cover and the upload.

## Order

| Step | What | Stops for Ivan |
|---|---|---|
| 0 | Preflight: post live, video toolchain, every provider's credentials | only on a failure |
| 1 | blog-video | **gate 1:** he watches and listens |
| 2 | Cover | no |
| 3 | `publish.json` | no |
| 4 | Dry run on every provider | **gate 2:** upload, privacy, AI disclosure |
| 5 | Upload, read back, report | no |

Start at the first step whose output is missing, and say which steps you skipped. A provider with a receipt is skipped by the script itself, so running the pipeline again after a new provider is added uploads only to that one.

## 0. Preflight

```bash
curl -sS -o /dev/null -w '%{http_code}\n' -A 'Mozilla/5.0 blog-video-pipeline' https://www.ivankristianto.com/<slug>/
.claude/skills/blog-video/preflight.sh --check
command -v magick playwright-cli
python3 -I .claude/skills/blog-video-pipeline/scripts/publish.py preflight
```

The post must return 200; if it isn't published, stop and say so. The cover's `render.sh` needs `magick` and `playwright-cli`; if one is missing, say so now and ask before `brew install imagemagick`, so it doesn't fail after Ivan has sat through gate 1. `publish.py` re-runs itself under `op run` with each provider's `op://Agents/...` references, so credentials only reach that child process. Never read, print or write them. Its output names the channel each provider will post to; check it's Ivan's.

A provider that fails preflight is reported now, not after the video is built. YouTube's usual failure is `invalid_grant`: the refresh token expired (Google ends them after 7 days while the OAuth app is in Testing) or was revoked. Ivan has to authorize again and save the new token JSON to the item; don't try to do the OAuth flow yourself. Go on with the other providers only if he says so.

## 1. Video (blog-video)

Run it in full. Its punch line source, in order:

1. `.claude/blog/drafts/<slug>/feature.html`, if blog-feature-image made the card locally.
2. The live featured image: `/wp-json/wp/v2/posts?slug=<slug>&_embed=wp:featuredmedia`, download `source_url` to the scratchpad and Read it. Its punch line is the one readers already saw.
3. Neither: run blog-feature-image's "Offer Ivan options" and ask which line, before writing the script. It makes the card files only; setting it on the post is a separate ask.

**Gate 1.** blog-video can't hear its own file. Give Ivan the `file:///` links to `video/out/video.mp4` and the contact sheet, plus its "what to listen for" list, and ask with `AskUserQuestion`: approve it, or say what to change. Offer the vault `Inbox/` copy from blog-video's step 6 when he's on his phone. Fix, re-render and ask again until he approves. Nothing below runs on an unapproved video.

## 2. Cover

The cover is the video's feature image: the post's card restacked for a 1080x1920 frame, so a viewer who saw the card in a feed sees the same post. YouTube uses it as the thumbnail, including the Shorts shelf.

```bash
python3 -I .claude/skills/blog-feature-image/tokens.py .claude/blog/drafts/<slug>/cover
cp .claude/skills/blog-video-pipeline/cover.html .claude/blog/drafts/<slug>/cover/feature-vertical.html
```

Fill the three slots, then delete the slot comments:

- **Subject:** the card's subject logo or icon at 96px (`blog-feature-image/icon.py`), or nothing if the card has none.
- **Punch line:** the same line as the video's intro, word for word, `<em>` included.
- **Evidence:** the card's evidence, cut to what reads at phone size. A receipt keeps two or three rows, a diff four lines, a path three boxes stacked top to bottom, a token one line. Same material as the card; don't add anything the card or post doesn't have.

Everything stays inside x 90 to 900 and y 210 to 1480: the Shorts title, buttons and channel line sit on the rest. blog-feature-image's design rules all apply (tokens only, system fonts, one green, no shadows, gradients or emoji, the `ik.` signature).

```bash
.claude/skills/blog-feature-image/render.sh .claude/blog/drafts/<slug>/cover/feature-vertical.html 1080 1920
cd .claude/blog/drafts/<slug>/cover
ffmpeg -v error -y -i feature-vertical.png -vf scale=1080:1920:flags=lanczos -q:v 2 feature-vertical.jpg
ffmpeg -v error -y -i feature-vertical.jpg -vf scale=405:720 phone.png
```

`render.sh` needs ImageMagick (`magick`); if it's missing, ask Ivan before `brew install imagemagick`. It writes a 2160x3840 PNG. The JPG is the upload, and must stay under 2 MB; ffmpeg does the resizing because blog-video's preflight already installs it. Read the PNG and `phone.png`: nothing clips, nothing crosses the safe zone, the punch line reads at phone size. Then `bash .claude/skills/blog-ideas/leak-check.sh feature-vertical.html`.

Horizontal videos (1920x1080, only when Ivan asked for one) need a 1280x720 cover. The template doesn't do that yet; build it from the card's own layout and say so in the report.

## 3. Metadata

Write `video/publish.json`. Paths are relative to the `video/` folder.

```json
{
  "slug": "<slug>",
  "url": "https://www.ivankristianto.com/<slug>/",
  "orientation": "vertical",
  "video": "out/video.mp4",
  "captions": "out/video.srt",
  "cover": "../cover/feature-vertical.jpg",
  "title": "...",
  "description": "...",
  "tags": ["..."],
  "language": "en",
  "privacy": "private",
  "syntheticMedia": null,
  "youtube": { "categoryId": "28", "notifySubscribers": false }
}
```

- **Title:** says what the viewer learns, in sentence case, under 70 characters so the Shorts feed doesn't cut it (100 is the hard limit). It may be the post title or the punch line, whichever is clearer without the picture. No hype, no exclamation marks, no `<` or `>`.
- **Description:** two or three plain sentences in the post's voice saying what the video shows and what the article adds, then a blank line, then `Full article: https://www.ivankristianto.com/<slug>/`, then at most three hashtags on the last line. Links in a Short's description aren't clickable, which is why the outro speaks the URL; it still belongs here for desktop viewers.
- **Tags:** 5 to 10, from the post's own tags (REST `tags`), lowercase. 500 characters in total.
- **`privacy`:** `private` until Ivan answers gate 2.
- **`syntheticMedia`:** stays `null` until gate 2. The script refuses to upload while it is.

Run `leak-check.sh video/publish.json`. The same rules as blog-video apply: no client names, no ticket keys, no mention of the AI assistant.

## 4. Dry run, then gate 2

```bash
python3 -I .claude/skills/blog-video-pipeline/scripts/publish.py plan .claude/blog/drafts/<slug>/video --report <scratchpad>/video-plan.json
```

It needs no credentials. It checks the files, probes the video (orientation, under a minute), confirms the post returns 200, validates the metadata against each provider's limits and says per provider what an upload would do. `problems` must be empty for everything except the `syntheticMedia` line.

Show Ivan, in a few lines: the video's duration, the cover's PNG link, the title with its character count, the description and tags, and what each provider would do (upload, skip with its existing link, or finish a half-done upload). Then **gate 2** is one `AskUserQuestion` call with two questions:

- **Privacy:** private (recommended, he flips it in YouTube Studio when he's ready), unlisted, or public. Also offer "don't upload".
- **AI disclosure:** YouTube's "altered or synthetic content" flag. Recommend yes when the narrator is a clone of Ivan's voice: it sounds like him saying a script he didn't speak. For a stock Kokoro voice, give both options and no recommendation.

Write his answers into `publish.json`, run `plan` again, and go on only if it's clean.

## 5. Upload, read back, report

```bash
python3 -I .claude/skills/blog-video-pipeline/scripts/publish.py upload .claude/blog/drafts/<slug>/video --apply --report <scratchpad>/video-upload.json
```

For each provider without a receipt it uploads the video, writes the receipt straight away, sets the cover as the thumbnail, then reads the upload back until processing finishes (up to three minutes). A failure after the upload leaves a receipt, and running the same command again finishes the remaining steps without uploading twice. `--only youtube` limits it to named providers. `--reupload youtube` ignores a receipt; use it only when Ivan asks for a second copy, and tell him the first one is still on the channel.

Every `readback.matches` value must be `true` and `uploadStatus` must be `processed`. `syntheticMedia` may be `null`: the API doesn't always return the flag, so check it in Studio and say which it was. If processing was still running when the wait ended, run `publish.py readback <video-dir>` a few minutes later.

Report:

- Each provider's link (the Shorts URL and the Studio edit URL for YouTube), privacy and processing state.
- `file:///` links to `video.mp4`, `feature-vertical.png` and `publish.json`.
- What was not uploaded and why. Captions: the YouTube token has only `youtube.upload` and `youtube.readonly`, and caption upload needs `youtube.force-ssl`. The captions are burned into the video, and `video.srt` can be added in Studio by hand.
- What Ivan still does: check the video in Studio, set it public when he's ready, and add the video to the post if he wants it embedded. Don't edit the post.

Never change a video's privacy or delete one after upload. Those are Ivan's, in Studio.

## When Ivan says "just report back"

- **Gate 1** still applies. A video nobody has heard doesn't go out.
- **Gate 2:** stop at the dry run and report it, unless his message asked for the upload. If it did, upload `private` with `notifySubscribers: false`, and ask the disclosure question anyway: it's a fact about his voice only he can answer.

## Providers

| Provider | Module | Credentials (Agents vault) | Notes |
|---|---|---|---|
| YouTube | `scripts/providers/youtube.py` | `Rei YouTube OAuth`: `oauth_client_json`, `oauth_token_json` | Desktop OAuth client. Scopes `youtube.upload`, `youtube.readonly`. Category 28 (Science & Technology). No caption upload. |

Every registered provider gets every video. The pipeline never picks a subset on its own; `--only` is for Ivan's asks and for back-filling.

### Adding a provider

1. Ivan creates the app or token on the host's side. Store it in the Agents vault by piping a JSON template to `op item create --vault Agents -`, so values stay out of argv.
2. Write `scripts/providers/<name>.py` with `NAME`, `CREDENTIALS` (`{env var: "op://Agents/<item>/<field>"}`) and five functions: `validate(meta, vdir)` returns a list of problems against the host's limits; `preflight()` makes one read-only call that proves the credentials and names the account; `plan(meta, vdir)` says what an upload would do without calling the host; `upload(meta, vdir, reupload)` uploads, writes the receipt as soon as the host has the file, then does the follow-up calls (cover, captions); `readback(receipt, meta)` fetches the upload and compares it with `publish.json`. Reuse `providers/common.py` for HTTP and receipts. Stdlib only.
3. Register it in `scripts/providers/__init__.py`.
4. Put host-only fields under a `publish.json` key with the provider's name, as `youtube` does. Shared fields stay shared.
5. Add a row to the table above, and tests next to the YouTube ones in `tests/test_publish.py`.
6. Run `preflight`, then `plan` on a video that already went to YouTube: the new provider should say "upload", YouTube "skip". Back-filling older videos is `upload --only <name>`, one video at a time, each with Ivan's yes.

## Tests

```bash
python3 -I -m unittest discover -s .claude/skills/blog-video-pipeline/tests
```

Offline only: the registry, metadata limits and receipt handling. They never call a host.
