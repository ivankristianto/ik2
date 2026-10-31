---
name: blog-feature-image
description: Make the featured image for an ivankristianto.com post, a 1200x628 card with a big punch line and real material from the post, in the site's design system, rendered at 2x from HTML. Renders three or four punch-line options for Ivan to pick from. Makes the image files and alt text only; blog-wp-draft uploads them. Use when Ivan asks for a feature image, featured image, cover image, OG or social image, or post header image, says the current one "is not right", or types /blog-feature-image. Optional last step of the blog-ideas, blog-research, blog-draft chain.
user-invocable: true
---

# Feature image

Input is a post: a draft in `.claude/blog/drafts/<slug>.md`, a published URL, or a WordPress draft Ivan points you to. Output is `<name>.html` (the source), `<name>.png`, `<name>.webp` and `alt.txt` in `.claude/blog/drafts/<slug>/`, never only in the scratchpad: a source left there was gone when Ivan wanted a change.

The card shows up in feeds and link previews at about 500px wide, with the post title printed under it. Its job is to make a developer stop scrolling. It does that with one punch line and one piece of real evidence from the post.

## The punch line

The biggest thing on the card, and Ivan likes it that way. Pattern C is the one exception.

- It says the surprising fact or the question the post answers. It never repeats the title, because the preview already shows the title.
- Short: one thought, about 4 to 12 words, at 50 to 60px. It has to read at 500px wide.
- True to the post. Every number, name and claim comes from the post. No hype words, no exclamation marks, straight quotes, Ivan's first person where it fits ("my blog").
- One phrase may go accent green (`<em>` inside `.punch`), usually the payoff.

Examples that worked: "Ask your AI agent for a modal. Count the lines." / "AI agents can read my blog now. They can't touch it." / "Why is the word "injection" slow to draw?" / "The agent runs the command. It never sees the secret."

### Offer Ivan options

Ivan picks the punch line from rendered cards, not from text. The first line on the 1Password post missed, and seeing four side by side settled it in one round. So in an interactive run:

1. Build the card once with your best line as `feature.html`.
2. Write three more lines that each take a different angle on the post: the surprise, the question it answers, the payoff, the cost.
3. Copy the card to `option-a.html` to `option-d.html` in the same folder (they share `card.css` and `tokens.css`), changing only the punch line. Option A is your recommendation.
4. Render each one with `render.sh`.
5. Show the cards in chat first: the four `file:///` PNG links, each with its line. The question dialog can hide earlier text and its links may not be clickable. Then ask with `AskUserQuestion`, using labels "A" to "D" plus a few words and the full line in each description.
6. Copy his pick into `feature.html` and render it. Keep the option files; they're cheap, and nothing uploads them.

Skip the options when Ivan already gave the line, or when he's asking to fix a card he already chose. Eval and other unattended runs build one card only: the grader counts every PNG in `outputs/` as the image.

## The evidence

Next to the punch line, show something only this post has: its code, a command and its output, a measurement, a version, the one token it is about. Take it from the post or from a real run you do now (with telemetry off). If you sketch something the post only describes, like "fifty lines of JavaScript", say so in the report.

## Design system (fixed)

- 1200×628, `card.css` + `tokens.css`. Run `python3 -I .claude/skills/blog-feature-image/tokens.py <out-dir>` to write both next to the source. Colours are `var(--…)` from theme.json only, never hex in the source.
- Drafting white ground, ink text, bookcloth green as the one accent. Red, amber and green status colours only where they mean removed, partial or works.
- System font stacks only: sans for the punch line, mono for code, commands, numbers and the domain.
- Footer: the `ik.` badge and `ivankristianto.com` (`<div class="sig"><span class="badge">ik.</span>ivankristianto.com</div>`, add `sig--right` when the left is busy). Never Ivan's full name.
- Icons and logos: Lucide for marks (file, terminal, check, x), Simple Icons for products (Chrome, WordPress, PHP). Get them with `python3 -I .claude/skills/blog-feature-image/icon.py lucide square-terminal 24` or `... simple googlechrome 30` and paste the output inline. One subject logo or icon per card, small; it is an accessory, not the picture.
- Not allowed: gradients, shadows, glows, grain, photos, emoji, a small label above the punch line, and hand-drawn pictures (bandages, moons, people). Shapes that mean something exactly (a diff, a table, boxes and arrows, letter tiles) are fine.
- No client names or ticket keys anywhere on the card or in the alt text.

## Patterns to start from

References in `examples/`. Read them for structure and spacing, then make the card for this post. Never reuse their content, and don't copy one when the post's material wants another shape. Pick by the shape of the evidence: two states of one thing is A, one input and what came back is B, one value the whole post turns on is C.

When the post could fairly go two ways, ask Ivan before building. Name each pattern, say in one line what its card would show for this post, and lead with the one you recommend and why. Don't ask when the shape is clear. In an unattended run, such as an eval, build your recommendation and say in the report which other pattern you considered.

- **A, the diff** (`examples/diff.html`): punch line left, a code panel right with a file tab and subject logo, the old way struck out, the new way in green, the result in one mono line. For posts with a fix, an upgrade, a before and after.
- **B, the receipt** (`examples/receipt.html`): the punch line as a question across the top, the subject mark in the corner, a real command and its real output below, the answer row in green. For posts with a command, a test, a measurement.
  - **Path variant** (`examples/path.html`): the same top half, then 3 to 5 mono boxes joined by arrows. The step the post is about is the odd one: dashed and struck out in red when it's skipped, red when it breaks, green at the end when it works. One real value under each box. For mechanism posts: an auth flow, a request path, a pipeline.
- **C, the token** (`examples/token.html`): the punch line across the top, then one real value (an attribute, flag, header or config key) set huge in mono on a full-width code-paper band, with the meaningful part in green, and one mono caption line under the band with what surrounds or enforces it. This is the one pattern where the evidence may outsize the punch line. Keep the token on one line: at 92px a mono glyph is about 55px wide, so 18 characters is the most that fits; shrink the size for longer ones. Write the space after a colon as `&nbsp;`, or the tight letter-spacing swallows it. For single-gotcha posts where one setting explains everything.

A surprise is welcome as long as it stays inside the design system: real timestamps as a timeline, letter tiles, a table of three numbers. When you break from A, B and C, say in the report what you did and why the post asked for it.

## Render and check

```bash
.claude/skills/blog-feature-image/render.sh .claude/blog/drafts/<slug>/<name>.html 1200 628
```

It serves the folder over http (playwright-cli can't open `file://`), screenshots at 2x, and writes the PNG and a WebP at quality 88. Then look, fix everything one look shows, and render again:

1. The full PNG: nothing collides, nothing clips, the footer has room.
2. Four tiles at full size (`magick <name>.png -crop 2x2@ +repage tile-%d.png`), each one looked at: labels against lines, icons drawn whole. Tiles cover every pixel; a fixed crop missed the busy part on most layouts.
3. At feed size (`magick <name>.png -resize 500x feed.png`): the punch line reads without zooming.

In SVG attributes, quote font names with single quotes; `"Segoe UI"` inside `font-family="..."` breaks the file.

## Alt text

One sentence under about 250 characters that says what is on the card, literally, starting with the punch line. Describe the evidence; don't transcribe every row of it: "'Ask your AI agent for a modal. Count the lines.' beside a diff where five lines of a custom modal are struck out and replaced by dialog closedby any." Save it to `alt.txt`, then run `bash .claude/skills/blog-ideas/leak-check.sh <name>.html alt.txt` and fix anything it flags.

## Stop at the files

Don't upload, set the featured image or edit the post. blog-wp-draft uploads `feature.webp` or `feature.png` with `alt.txt` when Ivan asks for the WordPress draft. When he asks to set the card on a post that's already published, use blog-wp-draft's `feature` command (see "Featured image on its own" there). In the report:

- What the card shows and where its material came from, flagging anything sketched rather than real.
- The alt text.
- `file:///` links to the source, PNG, WebP and `alt.txt`, and which image to upload (the smaller of PNG and WebP; WordPress refuses SVG).
- If the same image is also in the post body, say it should come out of the body: `templates/single.html` already prints the featured image at the top.
